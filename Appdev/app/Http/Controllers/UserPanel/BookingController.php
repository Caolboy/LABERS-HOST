<?php

namespace App\Http\Controllers\UserPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\Lab;
use App\Models\Room;
use App\Models\Equipment;
use App\Models\TimeSlot;
use App\Models\LabBooking;
use App\Models\EquipmentBooking;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingMadeMail;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{

    public function getCategories()
    {
        return response()->json(Category::all());
    }

    public function getItems(Request $request)
    {
        $labIds = Lab::where('category_id', $request->category_id)
                     ->pluck('id')->toArray();

        $rooms = Room::with('lab')
                     ->whereIn('lab_id', $labIds)
                     ->get();

        $equipment = Equipment::where('category_id', $request->category_id)
                              ->get();

        $roomItems = $rooms->map(fn($r) => [
            'id'              => $r->id,
            'name'            => $r->room_number,
            'type'            => 'room',
            'lab_name'        => $r->lab->name,
            'lab_description' => $r->lab->description,
            'description'     => null,
        ]);

        $equipItems = $equipment->map(fn($e) => [
            'id'              => $e->id,
            'name'            => $e->name,
            'type'            => 'equipment',
            'lab_name'        => null,
            'lab_description' => null,
            'description'     => $e->description,
            'available_quantity' => $e->quantity,
        ]);

        return response()->json($roomItems->concat($equipItems)->values());
    }

    /**
     * Get booked time slots for a specific room and date
     */
    public function getBookedSlots(Request $request)
    {
        try {
            $roomId = $request->query('room_id');
            $date = $request->query('date');

            // Get all existing time slots for this room on the specified date
            $bookedSlots = TimeSlot::where('room_id', $roomId)
                ->whereHas('bookings', function($query) use ($date) {
                    $query->where('booking_date', $date)
                          ->where('status', '!=', 'cancelled');
                })
                ->get()
                ->map(function($slot) {
                    return [
                        'id' => $slot->id,
                        'slot' => $slot->slot
                    ];
                });

            return response()->json($bookedSlots);

        } catch (\Throwable $e) {
            Log::error("getBookedSlots error: {$e->getMessage()}");
            return response()->json([
                'error' => 'Could not load booked slots',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function makeBooking(Request $request)
    {
        $userId = auth()->id();
        $bookings = [];
        
        // Check if we have any room bookings
        $hasRoomBookings = collect($request->selected_items)->contains('type', 'room');
        
        // Validate request
        $validationRules = [
            'selected_items' => 'required|array|min:1',
            'selected_items.*.id' => 'required|integer',
            'selected_items.*.type' => 'required|in:room,equipment',
            'date' => 'required|date|after_or_equal:today',
        ];
        
        // Add time validation only if we have room bookings
        if ($hasRoomBookings) {
            $validationRules['start_time'] = 'nullable|date_format:H:i';
            $validationRules['end_time'] = 'nullable|date_format:H:i|after:start_time';
            $validationRules['room_time_selections'] = 'nullable|array';
            $validationRules['room_time_selections.*.startTime'] = 'required_with:room_time_selections|date_format:H:i';
            $validationRules['room_time_selections.*.endTime'] = 'required_with:room_time_selections|date_format:H:i|after:room_time_selections.*.startTime';
        }
        
        $request->validate($validationRules);

        DB::transaction(function() use ($request, $userId, &$bookings) {
            foreach ($request->selected_items as $item) {
                if ($item['type'] === 'room') {
                    $this->createRoomBooking($item, $request, $userId, $bookings);
                } else {
                    $this->createEquipmentBooking($item, $request, $userId, $bookings);
                }
            }
        });
        
        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ], 201);
    }

    private function createRoomBooking($item, $request, $userId, &$bookings)
    {
        $roomId = $item['id'];
        $date = $request->date;
        
        // Handle both single room and multiple room scenarios
        $startTime = null;
        $endTime = null;
        
        // Check if room-specific time selections exist
        if (isset($request->room_time_selections) && isset($request->room_time_selections[$roomId])) {
            $timeSelection = $request->room_time_selections[$roomId];
            $startTime = $timeSelection['startTime'] ?? null;
            $endTime = $timeSelection['endTime'] ?? null;
        }
        
        // Fallback to global start_time and end_time for backward compatibility
        if (!$startTime || !$endTime) {
            $startTime = $request->start_time;
            $endTime = $request->end_time;
        }
        
        // Validate that we have both start and end times
        if (!$startTime || !$endTime) {
            throw new \Exception("Start time and end time are required for room booking");
        }
        
        // Ensure times are in HH:MM format
        $startTime = $this->formatTime($startTime);
        $endTime = $this->formatTime($endTime);
        
        $timeSlot = $startTime . '-' . $endTime;
        
        // Check if this exact time slot already exists for this room
        $existingTimeSlot = TimeSlot::where('room_id', $roomId)
            ->where('slot', $timeSlot)
            ->first();
            
        if ($existingTimeSlot) {
            // Check if this time slot is already booked for the given date
            $existingBooking = LabBooking::where('room_id', $roomId)
                ->where('time_slot_id', $existingTimeSlot->id)
                ->where('booking_date', $date)
                ->where('status', '!=', 'cancelled')
                ->exists();
                
            if ($existingBooking) {
                throw new \Exception("Time slot {$timeSlot} is already booked for room {$roomId} on {$date}");
            }
            
            $timeSlotId = $existingTimeSlot->id;
        } else {
            // Check for time conflicts with existing slots
            $conflictingSlots = TimeSlot::where('room_id', $roomId)
                ->whereHas('bookings', function($query) use ($date) {
                    $query->where('booking_date', $date)
                        ->where('status', '!=', 'cancelled');
                })
                ->get();
                
            foreach ($conflictingSlots as $conflictSlot) {
                if ($this->timeSlotsOverlap($timeSlot, $conflictSlot->slot)) {
                    throw new \Exception("Time slot {$timeSlot} conflicts with existing booking {$conflictSlot->slot}");
                }
            }
            
            // Create new time slot
            $newTimeSlot = TimeSlot::create([
                'room_id' => $roomId,
                'slot' => $timeSlot
            ]);
            
            $timeSlotId = $newTimeSlot->id;
        }
        
        // Create the booking
        $booking = LabBooking::create([
            'user_id' => $userId,
            'room_id' => $roomId,
            'time_slot_id' => $timeSlotId,
            'booking_date' => $date,
            'status' => 'pending',
        ]);
        
        $bookings[] = $booking;
    }

    private function createEquipmentBooking($item, $request, $userId, &$bookings)
    {
        $equipmentId = $item['id'];
        $quantity = $item['quantity'] ?? 1; // Default to 1 if quantity not specified
        $date = $request->date;
        
        // Get the equipment to check available quantity
        $equipment = Equipment::findOrFail($equipmentId);
        
        // Check if enough quantity is available
        if ($equipment->quantity < $quantity) {
            throw new \Exception("Not enough quantity available for {$equipment->name}. Available: {$equipment->quantity}, Requested: {$quantity}");
        }
        
        // Check if there's already a booking for this equipment on the same date by the same user
        $existingBooking = EquipmentBooking::where('user_id', $userId)
            ->where('equipment_id', $equipmentId)
            ->where('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->first();
            
        if ($existingBooking) {
            throw new \Exception("You already have a booking for {$equipment->name} on {$date}");
        }
        
        // Reduce the equipment quantity
        $equipment->decrement('quantity', $quantity);
        
        // Create the equipment booking
        $booking = EquipmentBooking::create([
            'user_id' => $userId,
            'equipment_id' => $equipmentId,
            'quantity' => $quantity,
            'booking_date' => $date,
            'status' => 'pending',
        ]);
        
        $bookings[] = $booking;
    }

    private function formatTime($time)
    {
        if (!$time) {
            return null;
        }
        
        // If time is already in HH:MM format, return as is
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        
        // If time is in H:MM format, pad with zero
        if (preg_match('/^\d{1}:\d{2}$/', $time)) {
            return str_pad($time, 5, '0', STR_PAD_LEFT);
        }
        
        // Try to parse and format the time
        try {
            $datetime = \DateTime::createFromFormat('H:i', $time);
            if ($datetime) {
                return $datetime->format('H:i');
            }
        } catch (\Exception $e) {
            // If parsing fails, return the original time
            return $time;
        }
        
        return $time;
    }

    /**
     * Check if two time slots overlap
     */
    private function timeSlotsOverlap($slot1, $slot2)
    {
        list($start1, $end1) = explode('-', $slot1);
        list($start2, $end2) = explode('-', $slot2);
        
        return (
            ($start1 >= $start2 && $start1 < $end2) ||
            ($end1 > $start2 && $end1 <= $end2) ||
            ($start1 <= $start2 && $end1 >= $end2)
        );
    }

    public function getUserBookings(Request $request)
    {
        $userId = $request->user()->id;

        // Get lab bookings
        $bookings = LabBooking::where('user_id', $userId)
            ->with(['room.lab', 'timeSlot'])
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'type' => 'room',
                    'lab_name' => $booking->room->lab->name,
                    'room_number' => $booking->room->room_number,
                    'date' => $booking->booking_date,
                    'time' => $booking->timeSlot->slot,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at->toDateTimeString(),
                ];
            });

        $eqBookings = EquipmentBooking::where('user_id', $userId)
            ->with('equipment')
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'type' => 'equipment',
                'equipment' => $b->equipment->name,
                'quantity' => $b->quantity,
                'date' => $b->booking_date,
                'time' => null,
                'status' => $b->status,
                'created_at' => $b->created_at->toDateTimeString(),
            ]);

        $all = $bookings->concat($eqBookings)
            ->sortByDesc('date')
            ->values();

        return response()->json($all);
    }

    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        
        $pendingRoomBookings = LabBooking::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        $pendingEquipmentBookings = EquipmentBooking::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        $totalPendingBookings = $pendingRoomBookings + $pendingEquipmentBookings;

        return response()->json([
            'user_name' => $user->name,
            'pending_bookings_count' => $totalPendingBookings
        ]);
    }

    public function cancelBooking(Request $request, $id)
    {
        $userId = $request->user()->id;
        $type = $request->input('type');

        DB::transaction(function() use ($id, $userId, $type) {
            if ($type === 'room') {
                $booking = LabBooking::where('id', $id)
                    ->where('user_id', $userId)
                    ->first();
                
                if ($booking) {
                    $booking->status = 'cancelled';
                    $booking->save();
                }
            } elseif ($type === 'equipment') {
                $eq = EquipmentBooking::where('id', $id)
                    ->where('user_id', $userId)
                    ->first();
                    
                if ($eq) {
                    // Return the equipment quantity back to inventory
                    Equipment::where('id', $eq->equipment_id)
                        ->increment('quantity', $eq->quantity);
                    $eq->status = 'cancelled';
                    $eq->save();
                }
            }
        });

        return response()->json(['success' => true]);
    }
}
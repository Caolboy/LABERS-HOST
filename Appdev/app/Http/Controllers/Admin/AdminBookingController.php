<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabBooking;
use App\Models\EquipmentBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingApprovedMail;
use App\Mail\BookingCancelledMail;
use Illuminate\Support\Facades\DB;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today('Asia/Manila');
        
        LabBooking::where('status', 'pending')
            ->where('booking_date', '<', $today)
            ->update(['status' => 'cancelled']);

        EquipmentBooking::where('status', 'pending')
            ->where('booking_date', '<', $today)
            ->update(['status' => 'cancelled']);

        // === LAB BOOKINGS ===
        $labQuery = LabBooking::with('user', 'room.lab', 'timeSlot');

        if ($search = $request->input('lab_search')) {
            $labQuery->where(function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                })->orWhereHas('room.lab', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                })->orWhereHas('room', function ($q) use ($search) {
                    $q->where('room_number', 'like', "%$search%");
                });
            });
        }

        if ($sort = $request->input('lab_sort')) {
            switch ($sort) {
                case 'user_name':
                    $labQuery->join('users', 'lab_bookings.user_id', '=', 'users.id')
                        ->orderBy('users.name')
                        ->select('lab_bookings.*');
                    break;
                case 'lab_type':
                    $labQuery->join('rooms', 'lab_bookings.room_id', '=', 'rooms.id')
                        ->join('labs', 'rooms.lab_id', '=', 'labs.id')
                        ->orderBy('labs.id')
                        ->select('lab_bookings.*');
                    break;
                case 'room_number':
                    $labQuery->join('rooms', 'lab_bookings.room_id', '=', 'rooms.id')
                        ->orderBy('rooms.room_number')
                        ->select('lab_bookings.*');
                    break;
                case 'booking_date':
                    $labQuery->orderByDesc('created_at');
                    break;
                case 'status':
                    $labQuery->orderBy('status');
                    break;
            }
        }

        $labBookings = $labQuery->get()->map(function ($booking) use ($today) {
            $booking->is_actionable = $booking->booking_date >= $today;
            return $booking;
        });

        // === EQUIPMENT BOOKINGS ===
        $equipmentQuery = EquipmentBooking::with('user', 'equipment');

        if ($search = $request->input('equipment_search')) {
            $equipmentQuery->where(function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                })->orWhereHas('equipment', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            });
        }

        if ($sort = $request->input('equipment_sort')) {
            switch ($sort) {
                case 'equipment_name':
                    $equipmentQuery->join('equipment', 'equipment_bookings.equipment_id', '=', 'equipment.id')
                        ->orderBy('equipment.name')
                        ->select('equipment_bookings.*');
                    break;
                case 'booking_date':
                    $equipmentQuery->orderByDesc('created_at');
                    break;
                case 'status':
                    $equipmentQuery->orderBy('status');
                    break;
                case 'user_name':
                    $equipmentQuery->join('users', 'equipment_bookings.user_id', '=', 'users.id')
                        ->orderBy('users.name')
                        ->select('equipment_bookings.*');
                    break;
            }
        }

        $equipmentBookings = $equipmentQuery->get()->map(function ($booking) use ($today) {
            $booking->is_actionable = $booking->booking_date >= $today;
            return $booking;
        });

        return response()->json([
            'lab_bookings' => $labBookings,
            'equipment_bookings' => $equipmentBookings,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:lab,equipment',
        ]);

        $type = $request->input('type');

        if ($type === 'lab') {
            $request->validate([
                'status' => 'required|in:pending,approved,cancelled',
            ]);
            $booking = LabBooking::with('user')->findOrFail($id);
        } else {
            $request->validate([
                'status' => 'required|in:pending,approved,cancelled,returned',
            ]);
            $booking = EquipmentBooking::with('user', 'equipment')->findOrFail($id);

            $oldStatus = $booking->status;
            $newStatus = $request->input('status');

            if (in_array($newStatus, ['cancelled', 'returned']) && !in_array($oldStatus, ['cancelled', 'returned'])) {
                $equipment = $booking->equipment;
                $equipment->quantity += $booking->quantity;
                $equipment->save();
            }
        }

        $oldStatus = $booking->status;
        $newStatus = $request->input('status');
        
        $booking->status = $newStatus;
        $booking->save();

        // Send email notifications
        if ($newStatus === 'approved') {
            Mail::to($booking->user->email)->send(new BookingApprovedMail($booking));
        } elseif ($newStatus === 'cancelled') {
            Mail::to($booking->user->email)->send(new BookingCancelledMail($booking));
        }

        return response()->json(['message' => 'Booking status updated successfully.']);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'type' => 'required|in:lab,equipment',
            'status' => 'required|string',
        ]);

        $ids = $request->input('ids');
        $type = $request->input('type');
        $newStatus = $request->input('status');

        if ($type === 'lab') {
            $request->validate([
                'status' => 'required|in:pending,approved,cancelled',
            ]);
        } else {
            $request->validate([
                'status' => 'required|in:pending,approved,cancelled,returned',
            ]);
        }

        DB::beginTransaction();

        try {
            if ($type === 'lab') {
                $bookings = LabBooking::with('user')->whereIn('id', $ids)->get();
                
                foreach ($bookings as $booking) {
                    $booking->status = $newStatus;
                    $booking->save();

                    // Send email notifications
                    if ($newStatus === 'approved') {
                        Mail::to($booking->user->email)->send(new BookingApprovedMail($booking));
                    } elseif ($newStatus === 'cancelled') {
                        Mail::to($booking->user->email)->send(new BookingCancelledMail($booking));
                    }
                }
            } else {
                $bookings = EquipmentBooking::with('user', 'equipment')->whereIn('id', $ids)->get();
                
                foreach ($bookings as $booking) {
                    $oldStatus = $booking->status;
                    
                    if (in_array($newStatus, ['cancelled', 'returned']) && !in_array($oldStatus, ['cancelled', 'returned'])) {
                        $equipment = $booking->equipment;
                        $equipment->quantity += $booking->quantity;
                        $equipment->save();
                    }
                    
                    $booking->status = $newStatus;
                    $booking->save();

                    // Send email notifications
                    if ($newStatus === 'approved') {
                        Mail::to($booking->user->email)->send(new BookingApprovedMail($booking));
                    } elseif ($newStatus === 'cancelled') {
                        Mail::to($booking->user->email)->send(new BookingCancelledMail($booking));
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($bookings) . ' booking(s) updated successfully.',
                'updated_count' => count($bookings)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating bookings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

use App\Http\Controllers\UserPanel\BookingController;
use App\Http\Controllers\UserPanel\LabEquipmentController;
use App\Http\Controllers\UserPanel\UserCalendarController;
use App\Http\Controllers\Admin\LabBookingController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\LabController;
use App\Http\Controllers\Admin\EquipmentController;

Route::get('/categories', [BookingController::class, 'getCategories']);
Route::get('/items', [BookingController::class, 'getItems']);
Route::get('/available-slots', [BookingController::class, 'getAvailableSlots']);
Route::post('/bookings', [BookingController::class, 'makeBooking']);
Route::get  ('/bookings', [BookingController::class, 'getUserBookings']);
Route::post ('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);
Route::get('/dashboard-data', [BookingController::class, 'getDashboardData']);
Route::get('/rooms', [BookingController::class, 'getRooms']);
Route::get('/booked-slots', [BookingController::class, 'getBookedSlots']);
Route::get('/calendar-availability', [UserCalendarController::class, 'availability']);
Route::get('/labsandequipment', [LabEquipmentController::class, 'index'])->name('labsandequipment');

Route::get('/admin/dashboard-data', [DashboardController::class, 'data']);
Route::get('/admin/room-booking-chart', [DashboardController::class, 'roomBookingChart']);
Route::get('/admin/equipment-booking-chart', [DashboardController::class, 'equipmentBookingChart']);

Route::get('/admin/bookings', [AdminBookingController::class, 'index']);
Route::put('/admin/bookings/{id}/status', [AdminBookingController::class, 'update']);
Route::get('/admin/lab-bookings', [LabBookingController::class, 'index']);

Route::get('/admin/users', [UserController::class, 'index']);
Route::put('/admin/users/{user}', [UserController::class, 'update']);
Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
Route::get('/admin/users/{user}/bookings', [UserController::class, 'bookings']);
Route::post('/admin/users', [UserController::class, 'store']);

Route::get('/admin/rooms', [RoomController::class, 'index']);
Route::put('/admin/rooms/{room}', [RoomController::class, 'update']);
Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy']);
Route::post('/admin/rooms', [RoomController::class, 'store']);

Route::get('/admin/labs', [LabController::class, 'index']);
Route::put('/admin/labs/{lab}', [LabController::class, 'update']);
Route::delete('/admin/labs/{lab}', [LabController::class, 'destroy']);
Route::post('/admin/labs', [LabController::class, 'store']);

Route::get('/admin/equipments', [EquipmentController::class, 'index']);
Route::put('/admin/equipments/{equipment}', [EquipmentController::class, 'update']);
Route::delete('/admin/equipments/{equipment}', [EquipmentController::class, 'destroy']);
Route::post('/admin/equipments', [EquipmentController::class, 'store']);
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentBooking extends Model
{
    protected $fillable = [
        'equipment_id', 
        'user_id', 
        'booking_date', 
        'quantity', 
        'status'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

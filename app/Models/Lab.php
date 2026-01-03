<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}

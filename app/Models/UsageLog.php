<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'lab_id',
        'equipment_id',
        'checked_in_at',
        'checked_out_at',
        'kiosk_label',
        'meta',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'meta' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relationship: the booking tied to this usage log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: the user who checked the equipment out.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Relationship: the lab where the usage occurred.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Relationship: the equipment that was checked out.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function getIsOpenAttribute(): bool
    {
        return $this->checked_in_at && $this->checked_out_at === null;
    }

    /**
     * Returns true when the usage log has a checked in timestamp but no checked out timestamp.
     *
     * @return bool
     */
}

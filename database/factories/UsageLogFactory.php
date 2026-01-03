<?php

namespace Database\Factories;

use App\Models\UsageLog;
use App\Models\Booking;
use App\Models\User;
use App\Models\Lab;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class UsageLogFactory extends Factory
{
    protected $model = UsageLog::class;

    public function definition()
    {
        // Ensure a user exists (create if necessary)
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        // Ensure a lab exists
        $lab = Lab::inRandomOrder()->first() ?? Lab::firstOrCreate(['code' => 'TEST-LAB'], ['name' => 'Test Lab', 'is_active' => true]);

        // Ensure equipment exists for the lab
        $equipment = Equipment::where('lab_id', $lab->id)->inRandomOrder()->first();
        if (!$equipment) {
            $equipment = Equipment::create(['name' => 'Test Equipment', 'lab_id' => $lab->id, 'is_active' => true, 'serial_number' => 'TEST-001']);
        }

        // Ensure a booking exists for the user and lab
        $booking = Booking::where('user_id', $user->id)->inRandomOrder()->first();
        if (!$booking) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'lab_id' => $lab->id,
                'equipment_id' => $equipment->id,
                'starts_at' => now()->subHours(3),
                'ends_at' => now()->addHours(3),
                'status' => Booking::STATUS_CONFIRMED ?? 'confirmed',
            ]);
        }

        return [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'equipment_id' => $equipment->id,
            'checked_in_at' => now()->subHours(rand(1, 48)),
            'checked_out_at' => now()->subHours(rand(0, 2)),
            'kiosk_label' => Arr::random(['Tablet Kiosk','Front Desk','Mobile Kiosk']),
            'meta' => ['demo' => true],
        ];
    }
}

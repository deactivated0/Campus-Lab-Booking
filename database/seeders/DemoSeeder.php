<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist (RoleSeeder is tolerant to missing Spatie tables)
        $this->call(RoleSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@test.test'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );
        $admin->syncRoles(['Admin']);

        $staff = User::firstOrCreate(
            ['email' => 'staff@test.test'],
            ['name' => 'Lab Staff', 'password' => Hash::make('password')]
        );
        $staff->syncRoles(['LabStaff']);

        $student = User::firstOrCreate(
            ['email' => 'student@test.test'],
            ['name' => 'Student One', 'password' => Hash::make('password')]
        );
        $student->syncRoles(['Student']);

        // Demo social accounts (helpful for manual testing)
        $socialDemos = [
            'github' => ['email' => 'github_demo@test.test', 'name' => 'GitHub Demo', 'id' => 'GIT-DEMO-1', 'avatar' => 'https://example.com/github.png'],
            'google' => ['email' => 'google_demo@test.test', 'name' => 'Google Demo', 'id' => 'GGL-DEMO-1', 'avatar' => 'https://example.com/google.png'],
            'facebook' => ['email' => 'facebook_demo@test.test', 'name' => 'Facebook Demo', 'id' => 'FB-DEMO-1', 'avatar' => 'https://example.com/facebook.png'],
        ];

        foreach ($socialDemos as $provider => $info) {
            $u = User::firstOrCreate(
                ['email' => $info['email']],
                ['name' => $info['name'], 'password' => Hash::make('password'), 'avatar_url' => $info['avatar'], "{$provider}_id" => $info['id']]
            );
            try { $u->syncRoles(['Student']); } catch (\Throwable $e) {}
        }

        // Create labs with official codes and locations
        $labs = [
            ['code' => 'LAB-CHEM', 'name' => 'Chemistry Lab', 'location' => 'Building C — 2nd Floor', 'capacity' => 18],
            ['code' => 'LAB-ELEC', 'name' => 'Electronics & Circuits Lab', 'location' => 'Building B — Ground Floor', 'capacity' => 20],
            ['code' => 'LAB-ENG', 'name' => 'Mechanical Engineering Lab', 'location' => 'Building A — 3rd Floor', 'capacity' => 24],
            ['code' => 'LAB-IT', 'name' => 'IT & Networking Lab', 'location' => 'Building A — 1st Floor', 'capacity' => 36],
            ['code' => 'LAB-A2-3', 'name' => 'Advanced Materials & Microscopy Lab', 'location' => 'Building A — 2nd Floor', 'capacity' => 24],
            ['code' => 'LAB-MEDIA', 'name' => 'Media Production Lab', 'location' => 'Building B — 1st Floor', 'capacity' => 16],
        ];

        $labMap = [];
        foreach ($labs as $l) {
            $lab = Lab::firstOrCreate(['code' => $l['code']], ['name' => $l['name'], 'location' => $l['location'], 'capacity' => $l['capacity'], 'is_active' => true]);
            // Ensure name/location/capacity are up-to-date
            $lab->update(['name' => $l['name'], 'location' => $l['location'], 'capacity' => $l['capacity'], 'is_active' => true]);
            $labMap[$l['code']] = $lab;
        }

        // Inventory list per recommended items
        $equipmentList = [
            // LAB-CHEM
            ['name' => 'Analytical Spectrometer', 'lab_code' => 'LAB-CHEM', 'category' => 'Spectrometer', 'serial' => 'CHEM-001'],
            ['name' => 'pH/Conductivity Meter', 'lab_code' => 'LAB-CHEM', 'category' => 'Meter', 'serial' => 'CHEM-002'],
            ['name' => 'High-precision Balance (0.1 mg)', 'lab_code' => 'LAB-CHEM', 'category' => 'Balance', 'serial' => 'CHEM-003'],
            ['name' => 'Gas Chromatograph (GC)', 'lab_code' => 'LAB-CHEM', 'category' => 'Chromatograph', 'serial' => 'CHEM-004'],
            ['name' => 'HPLC (optional)', 'lab_code' => 'LAB-CHEM', 'category' => 'Chromatograph', 'serial' => 'CHEM-005'],
            ['name' => 'Electrochemical Workstation', 'lab_code' => 'LAB-CHEM', 'category' => 'Electrochemistry', 'serial' => 'CHEM-006'],
            ['name' => 'Fume Hood (x2)', 'lab_code' => 'LAB-CHEM', 'category' => 'Safety', 'serial' => 'CHEM-007'],
            ['name' => 'Vacuum Pump', 'lab_code' => 'LAB-CHEM', 'category' => 'Pump', 'serial' => 'CHEM-008'],
            ['name' => 'Chemical Storage Cabinets', 'lab_code' => 'LAB-CHEM', 'category' => 'Furniture', 'serial' => 'CHEM-009'],
            ['name' => 'Eye wash & Safety Shower', 'lab_code' => 'LAB-CHEM', 'category' => 'Safety', 'serial' => 'CHEM-010'],

            // LAB-ELEC
            ['name' => 'Oscilloscope', 'lab_code' => 'LAB-ELEC', 'category' => 'Oscilloscope', 'serial' => 'ELEC-001'],
            ['name' => 'Function Generator', 'lab_code' => 'LAB-ELEC', 'category' => 'Generator', 'serial' => 'ELEC-002'],
            ['name' => 'Bench Power Supply (adjustable)', 'lab_code' => 'LAB-ELEC', 'category' => 'Power', 'serial' => 'ELEC-003'],
            ['name' => 'Multimeter (Fluke or equivalent)', 'lab_code' => 'LAB-ELEC', 'category' => 'Meter', 'serial' => 'ELEC-004'],
            ['name' => 'Logic Analyzer', 'lab_code' => 'LAB-ELEC', 'category' => 'Analyzer', 'serial' => 'ELEC-005'],
            ['name' => 'Soldering Stations (Hakko)', 'lab_code' => 'LAB-ELEC', 'category' => 'Soldering', 'serial' => 'ELEC-006'],
            ['name' => 'Rework/Hot Air Station', 'lab_code' => 'LAB-ELEC', 'category' => 'Soldering', 'serial' => 'ELEC-007'],
            ['name' => 'PCB Prototyping Tools', 'lab_code' => 'LAB-ELEC', 'category' => 'Tools', 'serial' => 'ELEC-008'],
            ['name' => 'Component Storage & Kits', 'lab_code' => 'LAB-ELEC', 'category' => 'Consumable', 'serial' => 'ELEC-009'],
            ['name' => 'ESD-safe benches and mats', 'lab_code' => 'LAB-ELEC', 'category' => 'Safety', 'serial' => 'ELEC-010'],

            // LAB-ENG
            ['name' => '3D Printer (Prusa/Ultimaker)', 'lab_code' => 'LAB-ENG', 'category' => '3D Printer', 'serial' => 'ENG-001'],
            ['name' => 'CNC Milling Machine', 'lab_code' => 'LAB-ENG', 'category' => 'Machinery', 'serial' => 'ENG-002'],
            ['name' => 'Laser Cutter/Engraver', 'lab_code' => 'LAB-ENG', 'category' => 'Laser', 'serial' => 'ENG-003'],
            ['name' => 'Stereo / Optical Microscopes', 'lab_code' => 'LAB-ENG', 'category' => 'Microscope', 'serial' => 'ENG-004'],
            ['name' => 'Thermal Imager', 'lab_code' => 'LAB-ENG', 'category' => 'Imaging', 'serial' => 'ENG-005'],

            // LAB-IT
            ['name' => 'Dell OptiPlex Workstation (x12)', 'lab_code' => 'LAB-IT', 'category' => 'Computer', 'serial' => 'IT-001'],
            ['name' => 'HP Z-series Workstations (x6)', 'lab_code' => 'LAB-IT', 'category' => 'Computer', 'serial' => 'IT-002'],
            ['name' => 'MacBook Pro 16 (loaner pool x4)', 'lab_code' => 'LAB-IT', 'category' => 'Laptop', 'serial' => 'IT-003'],
            ['name' => 'Server Rack (with servers & UPS)', 'lab_code' => 'LAB-IT', 'category' => 'Server', 'serial' => 'IT-004'],
            ['name' => 'Network Switches', 'lab_code' => 'LAB-IT', 'category' => 'Network', 'serial' => 'IT-005'],

            // LAB-A2-3
            ['name' => 'Transmission/Scanning Electron Microscope (TEM/SEM)', 'lab_code' => 'LAB-A2-3', 'category' => 'Microscope', 'serial' => 'A2-3-001'],
            ['name' => 'Optical & Stereo Microscopes', 'lab_code' => 'LAB-A2-3', 'category' => 'Microscope', 'serial' => 'A2-3-002'],
            ['name' => 'Sample preparation station', 'lab_code' => 'LAB-A2-3', 'category' => 'Prep', 'serial' => 'A2-3-003'],

            // LAB-MEDIA
            ['name' => 'Canon EOS R5 Kit (x2)', 'lab_code' => 'LAB-MEDIA', 'category' => 'Camera', 'serial' => 'MEDIA-001'],
            ['name' => 'Professional Lighting Kits', 'lab_code' => 'LAB-MEDIA', 'category' => 'Lighting', 'serial' => 'MEDIA-002'],
            ['name' => 'Zoom H6 / Portable Audio Recorders', 'lab_code' => 'LAB-MEDIA', 'category' => 'Audio', 'serial' => 'MEDIA-003'],
        ];

        $order = 1;
        foreach ($equipmentList as $item) {
            $lab = $labMap[$item['lab_code']] ?? null;
            if (!$lab) continue;

            Equipment::firstOrCreate(
                ['name' => $item['name'], 'lab_id' => $lab->id],
                ['category' => $item['category'], 'serial_number' => $item['serial'], 'is_active' => true, 'sort_order' => $order]
            );
            $order++;
        }

        // Add 50 extra generic inventory items spread across labs & categories
        $extraCategories = ['Tools','Consumable','Instrument','Accessory','Furniture'];
        $labCodes = array_keys($labMap);
        for ($i = 1; $i <= 50; $i++) {
            $labCode = $labCodes[$i % count($labCodes)];
            $lab = $labMap[$labCode];
            $cat = $extraCategories[$i % count($extraCategories)];
            $serial = sprintf('GEN-%03d', $i);
            Equipment::firstOrCreate(
                ['serial_number' => $serial],
                [
                    'name' => "Generic Item {$i}",
                    'lab_id' => $lab->id,
                    'category' => $cat,
                    'serial_number' => $serial,
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
            $order++;
        }

        // Resolve a few named items we reference later (safe lookups)
        $cam = Equipment::where('serial_number', 'MEDIA-001')->first() ?? Equipment::where('name', 'LIKE', '%Canon EOS R5%')->first();
        $microscope = Equipment::where('serial_number', 'A2-3-002')->first() ?? Equipment::where('category', 'Microscope')->first();
        $rec = Equipment::where('serial_number', 'MEDIA-003')->first() ?? Equipment::where('name', 'LIKE', '%Zoom H6%')->first();

        // Create a booking active now (prefer the camera's lab if available)
        $activeLabId = $cam?->lab_id ?? ($labMap['LAB-MEDIA']->id ?? array_values($labMap)[0]->id);

        $active = Booking::create([
            'user_id' => $student->id,
            'lab_id' => $activeLabId,
            'equipment_id' => $cam?->id,
            'title' => 'Camera Checkout',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
            'status' => Booking::STATUS_CHECKED_OUT,
            'confirmed_by' => $staff->id,
            'confirmed_at' => now()->subHours(2),
        ]);

        UsageLog::create([
            'booking_id' => $active->id,
            'user_id' => $student->id,
            'lab_id' => $activeLabId,
            'equipment_id' => $cam?->id,
            'checked_in_at' => now()->subHours(1),
            'checked_out_at' => null,
            'kiosk_label' => 'Tablet Kiosk',
            'meta' => ['demo' => true],
        ]);

        // Create a booking up next (prefer microscope's lab)
        $micLabId = $microscope?->lab_id ?? ($labMap['LAB-ENG']->id ?? array_values($labMap)[0]->id);

        Booking::create([
            'user_id' => $student->id,
            'lab_id' => $micLabId,
            'equipment_id' => $microscope?->id,
            'title' => 'Microscope Session',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 30),
            'status' => Booking::STATUS_CONFIRMED,
            'confirmed_by' => $staff->id,
            'confirmed_at' => now()->subDay(),
        ]);

        // Recent returned
        $old = Booking::create([
            'user_id' => $student->id,
            'lab_id' => $micLabId,
            'equipment_id' => $microscope?->id,
            'title' => 'Returned Demo',
            'starts_at' => now()->subDays(1)->setTime(12, 0),
            'ends_at' => now()->subDays(1)->setTime(14, 0),
            'status' => Booking::STATUS_RETURNED,
            'confirmed_by' => $staff->id,
            'confirmed_at' => now()->subDays(2),
        ]);

        UsageLog::create([
            'booking_id' => $old->id,
            'user_id' => $student->id,
            'lab_id' => $micLabId,
            'equipment_id' => $microscope?->id,
            'checked_in_at' => now()->subDays(1)->setTime(12, 0),
            'checked_out_at' => now()->subDays(1)->setTime(14, 0),
            'kiosk_label' => 'Tablet Kiosk',
            'meta' => ['demo' => true],
        ]);

        // Pending booking to approve (prefer recorder's lab)
        $recLabId = $rec?->lab_id ?? ($labMap['LAB-MEDIA']->id ?? array_values($labMap)[0]->id);

        Booking::create([
            'user_id' => $student->id,
            'lab_id' => $recLabId,
            'equipment_id' => $rec?->id,
            'title' => 'Recorder Booking',
            'starts_at' => now()->addHours(5),
            'ends_at' => now()->addHours(7),
            'status' => Booking::STATUS_PENDING,
        ]);
    }
}

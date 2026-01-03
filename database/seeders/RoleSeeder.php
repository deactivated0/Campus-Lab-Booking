<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Admin', 'LabStaff', 'Student'];

        // If the Spatie Role model exists and the roles table is present, use it.
        if (class_exists(\Spatie\Permission\Models\Role::class) && Schema::hasTable('roles')) {
            foreach ($roles as $role) {
                try {
                    \Spatie\Permission\Models\Role::findOrCreate($role);
                } catch (\Throwable $e) {
                    // Fall back to DB insert if model behaves unexpectedly
                    DB::table('roles')->updateOrInsert(['name' => $role], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            return;
        }

        // If Spatie is not installed or roles table isn't present, create rows directly (if table exists)
        if (Schema::hasTable('roles')) {
            foreach ($roles as $role) {
                DB::table('roles')->updateOrInsert(['name' => $role], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}

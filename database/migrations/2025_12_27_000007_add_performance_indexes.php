<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'starts_at', 'ends_at'], 'bookings_user_status_window_idx');
            $table->index(['status', 'starts_at'], 'bookings_status_starts_idx');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'equipment_active_order_idx');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            // equipment_id is created as foreignId (which usually creates an index),
            // but an explicit index ensures better portability/performance.
            $table->index('equipment_id', 'usage_logs_equipment_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_user_status_window_idx');
            $table->dropIndex('bookings_status_starts_idx');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex('equipment_active_order_idx');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropIndex('usage_logs_equipment_idx');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('system_configurations')->where('config_key', 'calendar_announcements')->exists()) {
            return;
        }

        DB::table('system_configurations')->insert([
            'config_key' => 'calendar_announcements',
            'config_value' => '',
            'config_type' => 'string',
            'description' => 'Important announcements for the student academic calendar (one per line)',
            'category' => 'calendar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_configurations')->where('config_key', 'calendar_announcements')->delete();
    }
};

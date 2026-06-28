<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_configurations')) {
            return;
        }

        $defaults = [
            ['workflow.default_academic_level', 'bachelor', 'Default academic level fallback', 'workflow'],
            ['workflow.default_workflow_type', 'standard', 'Default workflow template', 'workflow'],
            ['workflow.final_year.diploma', '2', 'Default final year for diploma programmes', 'workflow'],
            ['workflow.final_year.bachelor', '3', 'Default final year for bachelor programmes', 'workflow'],
            ['workflow.final_year.masters', '2', 'Default final year for masters programmes', 'workflow'],
            ['workflow.final_year.phd', '3', 'Default final year for PhD programmes', 'workflow'],
        ];

        foreach ($defaults as [$key, $value, $desc, $category]) {
            $exists = DB::table('system_configurations')->where('config_key', $key)->exists();
            if ($exists) {
                continue;
            }

            DB::table('system_configurations')->insert([
                'config_key' => $key,
                'config_value' => $value,
                'config_type' => 'string',
                'description' => $desc,
                'category' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_configurations')) {
            return;
        }

        DB::table('system_configurations')->whereIn('config_key', [
            'workflow.default_academic_level',
            'workflow.default_workflow_type',
            'workflow.final_year.diploma',
            'workflow.final_year.bachelor',
            'workflow.final_year.masters',
            'workflow.final_year.phd',
        ])->delete();
    }
};

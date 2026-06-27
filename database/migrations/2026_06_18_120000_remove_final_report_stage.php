<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_stages')->where('stage_name', 'Final Report')->delete();

        $order = [
            'Proposal Chapter 1' => 1,
            'Proposal Chapter 2' => 2,
            'Proposal Chapter 3' => 3,
            'Research Chapter 1' => 4,
            'Research Chapter 2' => 5,
            'Research Chapter 3' => 6,
            'Research Chapter 4' => 7,
            'Research Chapter 5' => 8,
            'Source Code Submission' => 9,
            'Complete Proposal Document' => 10,
            'Complete Research Document' => 11,
            'Complete Project Document' => 12,
            'Progress Presentation 1' => 13,
            'Progress Presentation 2' => 14,
            'Progress Presentation 3' => 15,
            'Final Presentation Consent Letter' => 16,
        ];

        foreach ($order as $name => $stageOrder) {
            DB::table('project_stages')
                ->where('stage_name', $name)
                ->update(['stage_order' => $stageOrder]);
        }
    }

    public function down(): void
    {
        DB::table('project_stages')->updateOrInsert(
            ['stage_name' => 'Final Report'],
            ['stage_order' => 10, 'days_allowed' => 14, 'created_at' => now(), 'updated_at' => now()]
        );
    }
};

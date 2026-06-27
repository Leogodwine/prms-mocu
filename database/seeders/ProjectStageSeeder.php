<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectStageSeeder extends Seeder
{
    /**
     * Mandatory workflow stages — required before students can submit work.
     */
    public function run(): void
    {
        $stages = [
            ['stage_name' => 'Proposal Chapter 1', 'stage_order' => 1, 'days_allowed' => 14],
            ['stage_name' => 'Proposal Chapter 2', 'stage_order' => 2, 'days_allowed' => 14],
            ['stage_name' => 'Proposal Chapter 3', 'stage_order' => 3, 'days_allowed' => 14],
            ['stage_name' => 'Research Chapter 1', 'stage_order' => 4, 'days_allowed' => 7],
            ['stage_name' => 'Research Chapter 2', 'stage_order' => 5, 'days_allowed' => 14],
            ['stage_name' => 'Research Chapter 3', 'stage_order' => 6, 'days_allowed' => 21],
            ['stage_name' => 'Research Chapter 4', 'stage_order' => 7, 'days_allowed' => 21],
            ['stage_name' => 'Research Chapter 5', 'stage_order' => 8, 'days_allowed' => 14],
            ['stage_name' => 'Complete System', 'stage_order' => 9, 'days_allowed' => 30],
            ['stage_name' => 'Complete Proposal Document', 'stage_order' => 10, 'days_allowed' => 14],
            ['stage_name' => 'Complete Research Document', 'stage_order' => 11, 'days_allowed' => 14],
            ['stage_name' => 'Complete Project Document', 'stage_order' => 12, 'days_allowed' => 14],
            ['stage_name' => 'Progress Presentation 1', 'stage_order' => 13, 'days_allowed' => 7],
            ['stage_name' => 'Progress Presentation 2', 'stage_order' => 14, 'days_allowed' => 7],
            ['stage_name' => 'Progress Presentation 3', 'stage_order' => 15, 'days_allowed' => 7],
            ['stage_name' => 'Final Presentation', 'stage_order' => 16, 'days_allowed' => 7],
            ['stage_name' => 'Final Presentation Consent Letter', 'stage_order' => 17, 'days_allowed' => 7],
        ];

        foreach ($stages as $stage) {
            \App\Models\ProjectStage::updateOrCreate(
                ['stage_name' => $stage['stage_name']],
                $stage
            );
        }
    }
}

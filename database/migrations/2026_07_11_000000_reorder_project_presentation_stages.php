<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reorder post–Complete System project stages to match the workflow:
     * PP1 → PP2 → PP3 → Consent → Final Presentation → Complete Project Document.
     */
    public function up(): void
    {
        $orders = [
            'Progress Presentation 1' => 12,
            'Progress Presentation 2' => 13,
            'Progress Presentation 3' => 14,
            'Final Presentation Consent Letter' => 15,
            'Final Presentation' => 16,
            'Complete Project Document' => 17,
        ];

        foreach ($orders as $stageName => $order) {
            DB::table('project_stages')
                ->where('stage_name', $stageName)
                ->update(['stage_order' => $order]);
        }
    }

    /**
     * Restore the previous stage order.
     */
    public function down(): void
    {
        $orders = [
            'Complete Project Document' => 12,
            'Progress Presentation 1' => 13,
            'Progress Presentation 2' => 14,
            'Progress Presentation 3' => 15,
            'Final Presentation' => 16,
            'Final Presentation Consent Letter' => 17,
        ];

        foreach ($orders as $stageName => $order) {
            DB::table('project_stages')
                ->where('stage_name', $stageName)
                ->update(['stage_order' => $order]);
        }
    }
};

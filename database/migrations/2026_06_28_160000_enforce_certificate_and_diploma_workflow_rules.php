<?php

use App\Enums\ProgramOutputType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('programmes')) {
            return;
        }

        $projectDiplomaCode = strtoupper((string) config('prms.workflow.project_diploma_programme_code', 'DBICT'));

        DB::table('programmes')
            ->where('academic_level', 'certificate')
            ->update([
                'output_type' => ProgramOutputType::None->value,
                'is_project_eligible' => false,
                'allowed_project_years' => null,
                'updated_at' => now(),
            ]);

        DB::table('programmes')
            ->where('academic_level', 'diploma')
            ->whereRaw('UPPER(programme_code) <> ?', [$projectDiplomaCode])
            ->update([
                'output_type' => ProgramOutputType::None->value,
                'is_project_eligible' => false,
                'allowed_project_years' => null,
                'updated_at' => now(),
            ]);

        DB::table('programmes')
            ->whereRaw('UPPER(programme_code) = ?', [$projectDiplomaCode])
            ->update([
                'academic_level' => 'diploma',
                'output_type' => ProgramOutputType::ProjectOnly->value,
                'is_project_eligible' => true,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('department_workflow_rules')) {
            DB::table('department_workflow_rules')
                ->whereIn('academic_level', ['certificate', 'diploma'])
                ->update([
                    'output_type' => ProgramOutputType::None->value,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Data correction migration — no rollback.
    }
};

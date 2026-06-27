<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_stages')) {
            DB::table('project_stages')
                ->where('stage_name', 'Source Code Submission')
                ->update(['stage_name' => 'Complete System']);
        }

        if (Schema::hasTable('project_submissions')) {
            DB::table('project_submissions')
                ->where('stage', 'Source Code Submission')
                ->update(['stage' => 'Complete System']);
        }

        if (Schema::hasTable('project_stages')
            && ! DB::table('project_stages')->where('stage_name', 'Final Presentation')->exists()) {
            DB::table('project_stages')
                ->where('stage_name', 'Final Presentation Consent Letter')
                ->update(['stage_order' => 17]);

            DB::table('project_stages')->insert([
                'stage_name' => 'Final Presentation',
                'stage_order' => 16,
                'days_allowed' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('project_submission_screenshots')) {
            Schema::create('project_submission_screenshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_submission_id')
                    ->constrained('project_submissions')
                    ->cascadeOnDelete();
                $table->string('interface_name', 160);
                $table->string('file_path', 1000);
                $table->string('original_filename', 255)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('project_submission_screenshots')
            && Schema::hasTable('project_submissions')) {
            DB::table('project_submissions')
                ->whereNotNull('screenshot_path')
                ->orderBy('id')
                ->each(function (object $row): void {
                    $exists = DB::table('project_submission_screenshots')
                        ->where('project_submission_id', $row->id)
                        ->exists();

                    if ($exists) {
                        return;
                    }

                    DB::table('project_submission_screenshots')->insert([
                        'project_submission_id' => $row->id,
                        'interface_name' => 'Home page',
                        'file_path' => $row->screenshot_path,
                        'original_filename' => $row->screenshot_original_filename,
                        'mime_type' => $row->screenshot_mime_type,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_submission_screenshots');

        if (Schema::hasTable('project_stages')) {
            DB::table('project_stages')
                ->where('stage_name', 'Complete System')
                ->update(['stage_name' => 'Source Code Submission']);

            DB::table('project_stages')
                ->where('stage_name', 'Final Presentation')
                ->delete();

            DB::table('project_stages')
                ->where('stage_name', 'Final Presentation Consent Letter')
                ->update(['stage_order' => 16]);
        }

        if (Schema::hasTable('project_submissions')) {
            DB::table('project_submissions')
                ->where('stage', 'Complete System')
                ->update(['stage' => 'Source Code Submission']);
        }
    }
};

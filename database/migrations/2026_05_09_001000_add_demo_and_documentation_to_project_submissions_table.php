<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project source-code submissions become first-class showcases.
 *
 *   • demo_url               — public URL to a hosted/live demo (embedded
 *                              as an iframe in the showcase modal so
 *                              reviewers can interact with the system
 *                              without unzipping the source).
 *   • video_url              — YouTube / Vimeo / Loom walkthrough that
 *                              the showcase embeds as a video player.
 *   • documentation_path     — separate PDF (user manual / API docs)
 *                              uploaded alongside the source archive.
 *   • documentation_original_filename / documentation_mime_type — kept
 *     so the inline-stream endpoint can serve sensible headers.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('project_submissions')) {
            return;
        }

        Schema::table('project_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('project_submissions', 'demo_url')) {
                $table->string('demo_url', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('project_submissions', 'video_url')) {
                $table->string('video_url', 500)->nullable()->after('demo_url');
            }
            if (! Schema::hasColumn('project_submissions', 'documentation_path')) {
                $table->string('documentation_path', 1000)->nullable()->after('screenshot_mime_type');
            }
            if (! Schema::hasColumn('project_submissions', 'documentation_original_filename')) {
                $table->string('documentation_original_filename', 255)->nullable()->after('documentation_path');
            }
            if (! Schema::hasColumn('project_submissions', 'documentation_mime_type')) {
                $table->string('documentation_mime_type', 120)->nullable()->after('documentation_original_filename');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_submissions')) {
            return;
        }

        Schema::table('project_submissions', function (Blueprint $table) {
            foreach ([
                'demo_url',
                'video_url',
                'documentation_path',
                'documentation_original_filename',
                'documentation_mime_type',
            ] as $col) {
                if (Schema::hasColumn('project_submissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

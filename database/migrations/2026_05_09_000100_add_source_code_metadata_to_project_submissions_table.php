<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capture richer metadata for project source-code submissions.
 *
 *   • description       — short bio (text) describing what the system does,
 *                         shown to readers/reviewers before they open the
 *                         source archive. Optional for proposal/research
 *                         submissions, required for project source code.
 *   • screenshot_path   — path to a "home page interface" preview image so
 *                         reviewers can see how the system looks without
 *                         having to unzip and run it.
 *   • screenshot_original_filename / screenshot_mime_type — preserved so
 *     download/preview routes can serve the file with sensible headers.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('project_submissions')) {
            return;
        }

        Schema::table('project_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('project_submissions', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('project_submissions', 'screenshot_path')) {
                $table->string('screenshot_path', 1000)->nullable()->after('original_filename');
            }
            if (! Schema::hasColumn('project_submissions', 'screenshot_original_filename')) {
                $table->string('screenshot_original_filename', 255)->nullable()->after('screenshot_path');
            }
            if (! Schema::hasColumn('project_submissions', 'screenshot_mime_type')) {
                $table->string('screenshot_mime_type', 120)->nullable()->after('screenshot_original_filename');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_submissions')) {
            return;
        }

        Schema::table('project_submissions', function (Blueprint $table) {
            foreach (['description', 'screenshot_path', 'screenshot_original_filename', 'screenshot_mime_type'] as $col) {
                if (Schema::hasColumn('project_submissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

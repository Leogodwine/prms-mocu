<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_submissions')) {
            return;
        }

        Schema::table('project_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('project_submissions', 'showcase_doc_summary')) {
                $table->text('showcase_doc_summary')->nullable()->after('documentation_mime_type');
            }
            if (! Schema::hasColumn('project_submissions', 'showcase_doc_significance')) {
                $table->text('showcase_doc_significance')->nullable()->after('showcase_doc_summary');
            }
            if (! Schema::hasColumn('project_submissions', 'showcase_readme_body')) {
                $table->text('showcase_readme_body')->nullable()->after('showcase_doc_significance');
            }
            if (! Schema::hasColumn('project_submissions', 'showcase_archive_tree')) {
                $table->json('showcase_archive_tree')->nullable()->after('showcase_readme_body');
            }
            if (! Schema::hasColumn('project_submissions', 'showcase_analysis_status')) {
                $table->string('showcase_analysis_status', 20)->nullable()->after('showcase_archive_tree');
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
                'showcase_doc_summary',
                'showcase_doc_significance',
                'showcase_readme_body',
                'showcase_archive_tree',
                'showcase_analysis_status',
            ] as $column) {
                if (Schema::hasColumn('project_submissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

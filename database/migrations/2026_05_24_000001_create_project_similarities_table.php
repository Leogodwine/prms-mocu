<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('research_projects')->cascadeOnDelete();
            $table->foreignId('similar_project_id')->constrained('research_projects')->cascadeOnDelete();
            $table->decimal('similarity_score', 5, 2);
            $table->decimal('text_similarity_score', 5, 2)->nullable();
            $table->string('risk_level', 16)->default('low');
            $table->text('summary')->nullable();
            $table->json('overlap_areas')->nullable();
            $table->string('analysis_model')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'similar_project_id']);
            $table->index(['project_id', 'similarity_score']);
            $table->index(['similar_project_id', 'similarity_score']);
            $table->index('risk_level');
        });

        if (Schema::hasTable('research_projects')) {
            Schema::table('research_projects', function (Blueprint $table) {
                if (! Schema::hasColumn('research_projects', 'similarity_checked_at')) {
                    $col = $table->timestamp('similarity_checked_at')->nullable();
                    if (Schema::hasColumn('research_projects', 'plagiarism_score')) {
                        $col->after('plagiarism_score');
                    }
                }
                if (! Schema::hasColumn('research_projects', 'similarity_status')) {
                    $col = $table->string('similarity_status', 32)->nullable();
                    if (Schema::hasColumn('research_projects', 'similarity_checked_at')) {
                        $col->after('similarity_checked_at');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_similarities');

        if (Schema::hasTable('research_projects')) {
            Schema::table('research_projects', function (Blueprint $table) {
                if (Schema::hasColumn('research_projects', 'similarity_status')) {
                    $table->dropColumn('similarity_status');
                }
                if (Schema::hasColumn('research_projects', 'similarity_checked_at')) {
                    $table->dropColumn('similarity_checked_at');
                }
            });
        }
    }
};

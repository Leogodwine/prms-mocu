<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evaluation_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria'); // [{name, weight, description}]
            $table->integer('total_marks')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_rubric_id')->constrained('evaluation_rubrics')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_group_id')->nullable()->constrained('project_groups')->cascadeOnDelete();
            $table->json('scores'); // [{criteria_name, score, comments}]
            $table->integer('total_score');
            $table->text('general_comments')->nullable();
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
        Schema::dropIfExists('evaluation_rubrics');
    }
};

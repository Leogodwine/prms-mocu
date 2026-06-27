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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_project_id')->constrained('research_projects')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->enum('submission_type', ['proposal', 'report', 'demo', 'code', 'documentation', 'presentation']);
            $table->integer('version')->default(1);
            $table->text('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('file_type', 50)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->useCurrent();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'needs_revision'])->default('submitted');
            $table->date('review_due_date')->nullable();
            $table->timestamp('actual_review_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};

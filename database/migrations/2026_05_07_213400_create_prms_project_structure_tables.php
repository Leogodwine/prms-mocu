<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('academic_year')->nullable();
            $table->timestamps();
        });

        Schema::create('project_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_group_id')->constrained('project_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('research_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_group_id')->nullable()->constrained('project_groups')->nullOnDelete();
            $table->foreignId('project_type_id')->nullable()->constrained('project_types')->nullOnDelete();
            $table->enum('status', ['ongoing', 'completed', 'suspended'])->default('ongoing');
            $table->timestamps();
        });

        Schema::create('supervisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_group_id')->nullable()->constrained('project_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_assignments');
        Schema::dropIfExists('research_projects');
        Schema::dropIfExists('project_group_members');
        Schema::dropIfExists('project_groups');
    }
};

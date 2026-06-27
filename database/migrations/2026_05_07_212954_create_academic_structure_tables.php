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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_code', 20)->unique();
            $table->string('department_name', 100);
            $table->string('head_of_department', 100)->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamps();
        });

        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->string('programme_code', 20)->unique();
            $table->string('programme_name', 100);
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->integer('duration_years')->nullable();
            $table->boolean('is_project_eligible')->default(false);
            $table->integer('project_year')->nullable();
            $table->timestamps();
        });

        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 50)->unique();
            $table->text('description')->nullable();
            $table->integer('min_students')->default(1);
            $table->integer('max_students')->default(5);
            $table->boolean('is_group_based')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_types');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('departments');
    }
};

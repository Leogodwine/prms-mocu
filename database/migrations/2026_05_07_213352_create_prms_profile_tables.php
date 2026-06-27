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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('registration_number', 50)->unique();
            $table->string('full_name', 200);
            $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
            $table->integer('year_of_study');
            $table->enum('enrollment_status', ['active', 'suspended', 'graduated', 'withdrawn'])->default('active');
            $table->string('university_email')->unique()->nullable();
            $table->string('personal_email')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->date('admission_date')->nullable();
            $table->date('expected_graduation')->nullable();
            $table->json('sis_data')->nullable();
            $table->timestamp('sis_sync_date')->nullable();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('staff_number', 50)->unique();
            $table->string('full_name', 200);
            $table->string('designation', 100)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('email')->unique()->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('office_location', 100)->nullable();
            $table->integer('max_students_allowed')->default(10);
            $table->integer('current_student_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('students');
    }
};

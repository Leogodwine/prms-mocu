<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('stage_name');
            $table->string('academic_year')->nullable();
            $table->foreignId('project_group_id')->nullable()->constrained('project_groups')->nullOnDelete();
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_deadlines');
    }
};

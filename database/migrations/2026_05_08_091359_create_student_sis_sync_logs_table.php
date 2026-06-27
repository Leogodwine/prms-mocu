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
        Schema::create('student_sis_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('sync_timestamp')->useCurrent();
            $table->integer('records_processed')->default(0);
            $table->integer('records_added')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_deactivated')->default(0);
            $table->string('sync_status')->default('success');
            $table->text('error_message')->nullable();
            $table->string('initiated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_sis_sync_logs');
    }
};

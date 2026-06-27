<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->foreignId('coordinator_approved_by')->nullable()->after('coordinator_approved_at')
                ->constrained('users')->nullOnDelete();
            $table->string('coordinator_signature_path')->nullable()->after('coordinator_approved_by');
            $table->string('coordinator_consent_pdf_path')->nullable()->after('coordinator_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropColumn(['coordinator_signature_path', 'coordinator_consent_pdf_path']);
            $table->dropConstrainedForeignId('coordinator_approved_by');
        });
    }
};

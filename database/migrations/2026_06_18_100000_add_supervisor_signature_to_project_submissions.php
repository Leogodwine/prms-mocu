<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->string('supervisor_signature_path')->nullable()->after('supervisor_consent_signed_at');
            $table->string('supervisor_consent_pdf_path')->nullable()->after('supervisor_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropColumn(['supervisor_signature_path', 'supervisor_consent_pdf_path']);
        });
    }
};

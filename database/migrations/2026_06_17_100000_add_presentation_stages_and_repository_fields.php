<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->timestamp('repository_published_at')->nullable()->after('coordinator_approved_at');
            $table->foreignId('supervisor_consent_signed_by')->nullable()->after('repository_published_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_consent_signed_at')->nullable()->after('supervisor_consent_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_consent_signed_by');
            $table->dropColumn(['repository_published_at', 'supervisor_consent_signed_at']);
        });
    }
};

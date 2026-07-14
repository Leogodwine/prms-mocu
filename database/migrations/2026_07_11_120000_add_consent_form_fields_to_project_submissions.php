<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->date('presentation_date')->nullable()->after('submitted_at');
            $table->string('consent_project_title', 500)->nullable()->after('presentation_date');
            $table->string('consent_group_number', 120)->nullable()->after('consent_project_title');
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropColumn(['presentation_date', 'consent_project_title', 'consent_group_number']);
        });
    }
};

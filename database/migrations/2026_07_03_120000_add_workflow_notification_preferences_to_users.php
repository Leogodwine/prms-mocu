<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'notify_email_workflow')) {
                $table->boolean('notify_email_workflow')->default(true)->after('notify_email_submission_reviewed');
            }
            if (! Schema::hasColumn('users', 'notify_sms_workflow')) {
                $table->boolean('notify_sms_workflow')->default(true)->after('notify_email_workflow');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'notify_sms_workflow')) {
                $table->dropColumn('notify_sms_workflow');
            }
            if (Schema::hasColumn('users', 'notify_email_workflow')) {
                $table->dropColumn('notify_email_workflow');
            }
        });
    }
};

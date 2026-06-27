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
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id')->unique()->after('email');
            $table->string('registration_number')->nullable()->unique()->after('login_id');
            $table->string('staff_id')->nullable()->unique()->after('registration_number');
            $table->string('role')->default('student')->after('staff_id');
            $table->string('account_status')->default('active')->after('role');
            $table->string('enrollment_status')->default('active')->after('account_status');
            $table->string('department')->nullable()->after('enrollment_status');
            $table->string('programme')->nullable()->after('department');
            $table->integer('year_of_study')->nullable()->after('programme');
            $table->boolean('must_change_password')->default(true)->after('password');
            $table->string('phone_number')->nullable()->after('must_change_password');
            $table->boolean('notify_email_new_submission')->default(true);
            $table->boolean('notify_email_submission_reviewed')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_id',
                'registration_number',
                'staff_id',
                'role',
                'account_status',
                'enrollment_status',
                'department',
                'programme',
                'year_of_study',
                'must_change_password',
                'phone_number',
                'notify_email_new_submission',
                'notify_email_submission_reviewed'
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff') && ! Schema::hasColumn('staff', 'gender')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->enum('gender', ['male', 'female'])->nullable()->after('full_name');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'gender')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('gender', ['male', 'female'])->nullable()->after('phone_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'gender')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'gender')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }
    }
};

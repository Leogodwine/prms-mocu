<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_rubrics', function (Blueprint $table) {
            $table->boolean('is_system_default')->default(false)->after('is_active');
        });

        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->string('evaluation_scope', 20)->default('submission')->after('project_submission_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->dropColumn('evaluation_scope');
        });

        Schema::table('evaluation_rubrics', function (Blueprint $table) {
            $table->dropColumn('is_system_default');
        });
    }
};

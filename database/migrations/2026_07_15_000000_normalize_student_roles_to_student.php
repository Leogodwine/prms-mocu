<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['normal_student', 'project_student', 'research_student'])
            ->update(['role' => 'student']);
    }

    public function down(): void
    {
        // Irreversible: original subtype values are not recoverable safely.
    }
};

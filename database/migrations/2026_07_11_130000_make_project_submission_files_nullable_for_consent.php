<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE project_submissions MODIFY file_path VARCHAR(1000) NULL');
        DB::statement('ALTER TABLE project_submissions MODIFY original_filename VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE project_submissions MODIFY file_path VARCHAR(1000) NOT NULL');
        DB::statement('ALTER TABLE project_submissions MODIFY original_filename VARCHAR(255) NOT NULL');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_projects', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('status');
            $table->timestamp('published_at')->nullable()->after('is_public');
            $table->string('citation_count')->default(0)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('research_projects', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'published_at', 'citation_count']);
        });
    }
};

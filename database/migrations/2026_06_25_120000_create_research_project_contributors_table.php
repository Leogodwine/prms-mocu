<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('research_project_contributors')) {
            return;
        }

        Schema::create('research_project_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_project_id')->constrained('research_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('contribution_role', 40)->default('contributor');
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['research_project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_project_contributors');
    }
};

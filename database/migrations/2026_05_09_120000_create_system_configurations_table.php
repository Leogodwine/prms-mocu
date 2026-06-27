<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique();
            $table->text('config_value')->nullable();
            $table->string('config_type', 32)->default('string');
            $table->text('description')->nullable();
            $table->string('category', 64)->default('general')->index();
            $table->timestamps();
        });

        $defaults = [
            ['academic_year', '', 'string', 'Active academic year label', 'lifecycle'],
            ['project_cycle', '', 'string', 'Current project/teaching cycle', 'lifecycle'],
            ['deadline_proposal', '', 'string', 'Proposal submission deadline (Y-m-d)', 'deadlines'],
            ['deadline_final', '', 'string', 'Final submission deadline (Y-m-d)', 'deadlines'],
            ['eligibility_min_year', '3', 'string', 'Minimum year of study for project groups', 'eligibility'],
        ];

        foreach ($defaults as [$key, $value, $type, $desc, $category]) {
            DB::table('system_configurations')->insert([
                'config_key' => $key,
                'config_value' => $value,
                'config_type' => $type,
                'description' => $desc,
                'category' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};

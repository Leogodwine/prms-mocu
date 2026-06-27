<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->dateTime('meeting_starts_at')->nullable()->after('supervisor_id');
            $table->dateTime('meeting_ends_at')->nullable()->after('meeting_starts_at');
        });

        foreach (DB::table('supervision_logs')->orderBy('id')->get() as $row) {
            $date = $row->meeting_date ?? now()->toDateString();
            DB::table('supervision_logs')->where('id', $row->id)->update([
                'meeting_starts_at' => $date.' 09:00:00',
                'meeting_ends_at' => $date.' 10:00:00',
            ]);
        }

        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->dropColumn('meeting_date');
        });
    }

    public function down(): void
    {
        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->date('meeting_date')->nullable()->after('supervisor_id');
        });

        foreach (DB::table('supervision_logs')->orderBy('id')->get() as $row) {
            $startsAt = $row->meeting_starts_at ?? now();
            DB::table('supervision_logs')->where('id', $row->id)->update([
                'meeting_date' => date('Y-m-d', strtotime((string) $startsAt)),
            ]);
        }

        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->dropColumn(['meeting_starts_at', 'meeting_ends_at']);
        });
    }
};

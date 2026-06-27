<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR-02 alignment for `submissions` and `project_versions`.
 *
 * Each column is added in its own Schema::table() call with hasColumn()
 * checked outside the closure. Batching every column inside one closure
 * can make MySQL attempt duplicate adds when the schema cache is stale
 * (e.g. after a partial failed run).
 */
return new class extends Migration
{
    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    /**
     * @param  callable(Blueprint): void  $callback
     */
    private function addColumnIfMissing(string $table, string $column, callable $callback): void
    {
        if (! Schema::hasTable($table) || $this->hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, $callback);
        } catch (\Throwable $e) {
            if ($this->isDuplicateColumnError($e)) {
                return;
            }

            throw $e;
        }
    }

    private function isDuplicateColumnError(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Duplicate column')
            || str_contains($message, '1060');
    }

    private function extendProjectVersions(): void
    {
        if (! Schema::hasTable('project_versions')) {
            return;
        }

        $this->addColumnIfMissing('project_versions', 'project_id', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->after('id')
                ->constrained('research_projects')
                ->cascadeOnDelete();
        });

        $this->addColumnIfMissing('project_versions', 'version_number', function (Blueprint $table) {
            $table->unsignedInteger('version_number')->default(1);
        });

        $this->addColumnIfMissing('project_versions', 'version_note', function (Blueprint $table) {
            $table->text('version_note')->nullable();
        });

        $this->addColumnIfMissing('project_versions', 'submitted_at', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable();
        });

        $this->addColumnIfMissing('project_versions', 'submitted_by', function (Blueprint $table) {
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        $this->addColumnIfMissing('project_versions', 'total_comments', function (Blueprint $table) {
            $table->unsignedInteger('total_comments')->default(0);
        });

        $this->addColumnIfMissing('project_versions', 'total_annotations', function (Blueprint $table) {
            $table->unsignedInteger('total_annotations')->default(0);
        });

        $this->addColumnIfMissing('project_versions', 'is_current', function (Blueprint $table) {
            $table->boolean('is_current')->default(true);
        });
    }

    private function extendSubmissions(): void
    {
        if (! Schema::hasTable('submissions')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE submissions MODIFY submission_type VARCHAR(60) NOT NULL');
        } catch (\Throwable) {
            // Non-MySQL drivers: leave the column shape alone.
        }

        if ($this->hasColumn('submissions', 'stage_id')) {
            try {
                DB::statement('ALTER TABLE submissions MODIFY stage_id BIGINT UNSIGNED NULL');
            } catch (\Throwable) {
                // Non-MySQL drivers: leave the column shape alone.
            }
        }

        try {
            DB::statement("ALTER TABLE submissions MODIFY status VARCHAR(40) NOT NULL DEFAULT 'submitted'");
        } catch (\Throwable) {
            // Non-MySQL drivers: leave the column shape alone.
        }

        $this->addColumnIfMissing('submissions', 'project_id', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('research_project_id')
                ->constrained('research_projects')
                ->cascadeOnDelete();
        });

        $this->addColumnIfMissing('submissions', 'version_number', function (Blueprint $table) {
            $table->unsignedInteger('version_number')->default(1)->after('version');
        });

        $this->addColumnIfMissing('submissions', 'document_path', function (Blueprint $table) {
            $table->text('document_path')->nullable()->after('file_path');
        });

        $this->addColumnIfMissing('submissions', 'document_name', function (Blueprint $table) {
            $table->string('document_name')->nullable()->after('file_name');
        });

        $this->addColumnIfMissing('submissions', 'document_size', function (Blueprint $table) {
            $table->unsignedBigInteger('document_size')->nullable()->after('file_size');
        });

        $this->addColumnIfMissing('submissions', 'submission_date', function (Blueprint $table) {
            $table->timestamp('submission_date')->nullable()->after('submitted_at');
        });

        $afterNotes = $this->hasColumn('submissions', 'notes') ? 'notes' : 'actual_review_date';

        $this->addColumnIfMissing('submissions', 'plagiarism_score', function (Blueprint $table) use ($afterNotes) {
            $table->decimal('plagiarism_score', 6, 3)->nullable()->after($afterNotes);
        });

        $this->addColumnIfMissing('submissions', 'preview_path', function (Blueprint $table) {
            $table->string('preview_path', 500)->nullable();
        });

        $this->addColumnIfMissing('submissions', 'review_status', function (Blueprint $table) {
            $table->string('review_status', 60)->nullable();
        });

        $this->addColumnIfMissing('submissions', 'submission_stage', function (Blueprint $table) {
            $table->unsignedSmallInteger('submission_stage')->nullable();
        });

        $this->addColumnIfMissing('submissions', 'total_comments', function (Blueprint $table) {
            $table->unsignedInteger('total_comments')->default(0);
        });

        $this->addColumnIfMissing('submissions', 'ip_address', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
        });

        $this->addColumnIfMissing('submissions', 'is_current', function (Blueprint $table) {
            $table->boolean('is_current')->default(true);
        });
    }

    public function up(): void
    {
        $this->extendProjectVersions();
        $this->extendSubmissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                foreach ([
                    'is_current',
                    'ip_address',
                    'total_comments',
                    'submission_stage',
                    'review_status',
                    'preview_path',
                    'plagiarism_score',
                    'submission_date',
                    'document_size',
                    'document_name',
                    'document_path',
                    'version_number',
                    'project_id',
                ] as $col) {
                    if ($this->hasColumn('submissions', $col)) {
                        if ($col === 'project_id') {
                            try {
                                $table->dropForeign([$col]);
                            } catch (\Throwable) {
                                // ignore
                            }
                        }
                        $table->dropColumn($col);
                    }
                }
            });

            try {
                DB::statement("ALTER TABLE submissions MODIFY submission_type ENUM('proposal','report','demo','code','documentation','presentation') NOT NULL");
                DB::statement("ALTER TABLE submissions MODIFY status ENUM('submitted','under_review','approved','rejected','needs_revision') NOT NULL DEFAULT 'submitted'");
            } catch (\Throwable) {
                // Non-MySQL drivers: leave the column shape alone.
            }
        }

        if (Schema::hasTable('project_versions')) {
            Schema::table('project_versions', function (Blueprint $table) {
                foreach (['is_current', 'total_annotations', 'total_comments', 'submitted_by', 'submitted_at', 'version_note', 'version_number', 'project_id'] as $col) {
                    if ($this->hasColumn('project_versions', $col)) {
                        if (in_array($col, ['project_id', 'submitted_by'], true)) {
                            try {
                                $table->dropForeign([$col]);
                            } catch (\Throwable) {
                                // ignore if FK already gone
                            }
                        }
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

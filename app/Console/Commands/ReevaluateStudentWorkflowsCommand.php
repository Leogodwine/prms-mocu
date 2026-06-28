<?php

namespace App\Console\Commands;

use App\Support\StudentWorkflowAssigner;
use Illuminate\Console\Command;

class ReevaluateStudentWorkflowsCommand extends Command
{
    protected $signature = 'prms:reevaluate-student-workflows';

    protected $description = 'Re-evaluate final-year eligibility, workflow roles, and output tracks for all students';

    public function handle(): int
    {
        $count = StudentWorkflowAssigner::reevaluateAll();

        $this->info("Re-evaluated workflow roles for {$count} student account(s).");

        return self::SUCCESS;
    }
}

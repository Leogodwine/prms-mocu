<?php

namespace App\Console\Commands;

use App\Support\PublicPortalPublication;
use Illuminate\Console\Command;

class SyncRepositoryPublicationsCommand extends Command
{
    protected $signature = 'repository:sync-publications';

    protected $description = 'Publish eligible complete documents from approved consent letters and sync them to the public research repository';

    public function handle(): int
    {
        $published = PublicPortalPublication::backfillFromSubmissions();

        $publicCount = \App\Models\ResearchProject::query()
            ->where('is_public', true)
            ->whereNotNull('published_at')
            ->count();

        $this->info("Repository sync complete. Newly published complete documents: {$published}");
        $this->info("Public research records available: {$publicCount}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\IpLocation;
use App\Models\Visit;
use Illuminate\Console\Command;

class PruneVisitsCommand extends Command
{
    protected $signature = 'epic:prune-visits {--days= : Keep this many days of visits (overrides the setting)}';

    protected $description = 'Delete visitor records older than the retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? setting('analytics_retention_days', '365'));

        if ($days <= 0) {
            $this->components->info('Visitor records are kept indefinitely (retention is set to 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $batch = Visit::where('visited_at', '<', $cutoff)->limit(2000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        // Forget cached addresses that no visit refers to any more.
        $staleAddresses = IpLocation::whereNotExists(fn ($query) => $query
            ->selectRaw('1')
            ->from('visits')
            ->whereColumn('visits.ip_hash', 'ip_locations.ip_hash'))
            ->delete();

        Visit::forgetCounters();

        $this->components->twoColumnDetail('Retention', $days.' days');
        $this->components->twoColumnDetail('Visits deleted', (string) $deleted);
        $this->components->twoColumnDetail('Cached addresses removed', (string) $staleAddresses);

        return self::SUCCESS;
    }
}

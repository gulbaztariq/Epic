<?php

namespace App\Console\Commands;

use App\Services\GeoLocator;
use Illuminate\Console\Command;

class ResolveVisitorLocationsCommand extends Command
{
    protected $signature = 'epic:resolve-visitor-locations {--limit=100 : How many addresses to look up in this run}';

    protected $description = 'Look up the country, region and city for visitor IP addresses';

    public function handle(GeoLocator $locator): int
    {
        if (setting('analytics_geolocation', '1') !== '1') {
            $this->components->warn('Visitor geolocation is switched off in Site settings → Analytics.');

            return self::SUCCESS;
        }

        $result = $locator->resolvePending(max(1, (int) $this->option('limit')));

        $this->components->twoColumnDetail('Addresses resolved', (string) $result['resolved']);
        $this->components->twoColumnDetail('Lookups failed', (string) $result['failed']);
        $this->components->twoColumnDetail('Visits updated', (string) $result['visits']);

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\IpLocation;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns visitor IP addresses into country / region / city.
 *
 * Lookups run in the background (php artisan epic:resolve-visitor-locations,
 * scheduled every 15 minutes) so no page ever waits on an external service.
 * Results are cached per address in ip_locations and copied onto the visits
 * that came from that address.
 */
class GeoLocator
{
    /**
     * ipwho.is is free, needs no API key and answers over HTTPS.
     * To use a different provider, change this URL and {@see mapResponse()}.
     */
    public const ENDPOINT = 'https://ipwho.is/';

    public const BATCH_SIZE = 10;

    /**
     * Resolve the addresses still waiting for a location.
     *
     * @return array{resolved: int, failed: int, visits: int}
     */
    public function resolvePending(int $limit = 100): array
    {
        if (setting('analytics_geolocation', '1') !== '1') {
            return ['resolved' => 0, 'failed' => 0, 'visits' => 0];
        }

        $pending = IpLocation::pending()->orderBy('id')->limit($limit)->get();

        if ($pending->isEmpty()) {
            return ['resolved' => 0, 'failed' => 0, 'visits' => 0];
        }

        $resolved = 0;
        $failed = 0;
        $visits = 0;

        foreach ($pending->chunk(self::BATCH_SIZE) as $chunk) {
            foreach ($this->lookupChunk($chunk) as $id => $data) {
                /** @var IpLocation $location */
                $location = $pending->firstWhere('id', $id);

                if (! $location) {
                    continue;
                }

                if ($data === null) {
                    $location->increment('attempts');
                    $failed++;

                    continue;
                }

                $location->fill($data);
                $location->resolved_at = now();
                $location->attempts = $location->attempts + 1;

                if (setting('analytics_store_full_ip', '0') !== '1') {
                    $location->ip_address = null;
                }

                $location->save();
                $resolved++;
                $visits += $this->backfillVisits($location);
            }
        }

        return ['resolved' => $resolved, 'failed' => $failed, 'visits' => $visits];
    }

    /** Copy a resolved location onto every visit from that address. */
    public function backfillVisits(IpLocation $location): int
    {
        return Visit::where('ip_hash', $location->ip_hash)
            ->where('location_resolved', false)
            ->update($location->visitAttributes());
    }

    /**
     * Look up a handful of addresses at once.
     *
     * @return array<int, array<string, mixed>|null> keyed by IpLocation id
     */
    protected function lookupChunk(Collection $chunk): array
    {
        $results = [];

        try {
            $responses = Http::pool(fn ($pool) => $chunk
                ->map(fn (IpLocation $location) => $pool
                    ->as((string) $location->id)
                    ->timeout(6)
                    ->connectTimeout(4)
                    ->acceptJson()
                    ->get(self::ENDPOINT.$location->ip_address))
                ->all());
        } catch (\Throwable $e) {
            Log::warning('IP location lookup failed: '.$e->getMessage());

            return $chunk->mapWithKeys(fn ($location) => [$location->id => null])->all();
        }

        foreach ($chunk as $location) {
            $response = $responses[(string) $location->id] ?? null;
            $results[$location->id] = null;

            if ($response instanceof \Throwable) {
                continue;
            }

            try {
                if ($response && method_exists($response, 'successful') && $response->successful()) {
                    $results[$location->id] = $this->mapResponse((array) $response->json());
                }
            } catch (\Throwable) {
                // Leave this address unresolved; it will be retried.
            }
        }

        return $results;
    }

    /**
     * Map an ipwho.is payload onto our columns.
     *
     * @return array<string, mixed>|null
     */
    protected function mapResponse(array $payload): ?array
    {
        if (($payload['success'] ?? false) !== true) {
            return null;
        }

        $code = strtoupper((string) ($payload['country_code'] ?? ''));

        return [
            'country_code' => strlen($code) === 2 ? $code : null,
            'country' => $payload['country'] ?? self::countryName($code),
            'region' => $payload['region'] ?? null,
            'city' => $payload['city'] ?? null,
            'timezone' => data_get($payload, 'timezone.id'),
            'latitude' => is_numeric($payload['latitude'] ?? null) ? $payload['latitude'] : null,
            'longitude' => is_numeric($payload['longitude'] ?? null) ? $payload['longitude'] : null,
            'organisation' => data_get($payload, 'connection.org') ?: data_get($payload, 'connection.isp'),
        ];
    }

    /** Human-readable country name for a two-letter code. */
    public static function countryName(?string $code): ?string
    {
        if (blank($code) || strlen($code) !== 2) {
            return null;
        }

        $code = strtoupper($code);

        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');

            if (filled($name) && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }
}

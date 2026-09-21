<?php

namespace Database\Factories;

use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        // Deterministic on purpose: tests set whatever they need to vary.
        return [
            'visitor_key' => hash('sha256', $this->faker->unique()->uuid()),
            'session_key' => hash('sha256', $this->faker->uuid()),
            'path' => '/',
            'page_title' => 'EPIC — Economic Policy and Innovation Centre',
            'referrer' => null,
            'referrer_host' => null,
            'ip_address' => '203.0.113.0',
            'ip_hash' => hash('sha256', $this->faker->unique()->ipv4()),
            'country_code' => 'PK',
            'country' => 'Pakistan',
            'region' => 'Punjab',
            'city' => 'Lahore',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'is_bot' => false,
            'is_new_visitor' => true,
            'location_resolved' => true,
            'visited_at' => now(),
        ];
    }

    public function bot(): self
    {
        return $this->state(fn () => [
            'is_bot' => true,
            'device_type' => 'bot',
            'browser' => 'Crawler',
            'platform' => null,
        ]);
    }

    public function on(\DateTimeInterface|string $when): self
    {
        return $this->state(fn () => ['visited_at' => $when]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A cached IP -> location lookup. Each address is resolved once and reused by
 * every later visit from the same address.
 */
class IpLocation extends Model
{
    public const MAX_ATTEMPTS = 3;

    protected $fillable = [
        'ip_hash', 'ip_address', 'country_code', 'country', 'region', 'city',
        'timezone', 'latitude', 'longitude', 'organisation', 'attempts', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /** Addresses still waiting to be looked up. */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('resolved_at')
            ->whereNotNull('ip_address')
            ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /** The fields a visit copies from a resolved lookup. */
    public function visitAttributes(): array
    {
        return [
            'country_code' => $this->country_code,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'timezone' => $this->timezone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_resolved' => true,
        ];
    }
}

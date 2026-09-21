<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_key', 'session_key', 'path', 'page_title', 'referrer', 'referrer_host',
        'ip_address', 'ip_hash', 'country_code', 'country', 'region', 'city', 'timezone',
        'latitude', 'longitude', 'device_type', 'browser', 'platform', 'language',
        'is_bot', 'is_new_visitor', 'location_resolved', 'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_bot' => 'boolean',
        'is_new_visitor' => 'boolean',
        'location_resolved' => 'boolean',
    ];

    public const DEVICE_TYPES = [
        'desktop' => 'Desktop',
        'mobile' => 'Mobile',
        'tablet' => 'Tablet',
        'bot' => 'Bot / crawler',
    ];

    protected static function booted(): void
    {
        static::created(fn () => static::forgetCounters());
    }

    /* ------------------------------------------------------------- Scopes */

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('visited_at', [$from, $to]);
    }

    /** Real people — crawlers and monitoring tools excluded. */
    public function scopeHumans(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    public function scopeBots(Builder $query): Builder
    {
        return $query->where('is_bot', true);
    }

    public function scopeAwaitingLocation(Builder $query): Builder
    {
        return $query->where('location_resolved', false);
    }

    /* ---------------------------------------------------- Public counters */

    public static function forgetCounters(): void
    {
        Cache::forget('epic.visits.total');
        Cache::forget('epic.visitors.total');
    }

    /** Total page views, cached so the footer counter costs nothing. */
    public static function totalPageViews(): int
    {
        return (int) Cache::remember('epic.visits.total', now()->addMinutes(10),
            fn () => static::humans()->count());
    }

    /** Total distinct visitors, cached. */
    public static function totalVisitors(): int
    {
        return (int) Cache::remember('epic.visitors.total', now()->addMinutes(10),
            fn () => static::humans()->distinct('visitor_key')->count('visitor_key'));
    }

    /* --------------------------------------------------------- Accessors */

    public function getLocationLabelAttribute(): string
    {
        $parts = array_filter([$this->city, $this->country]);

        return $parts ? implode(', ', $parts) : 'Unknown';
    }

    public function getDeviceLabelAttribute(): string
    {
        return self::DEVICE_TYPES[$this->device_type] ?? ucfirst((string) $this->device_type);
    }
}

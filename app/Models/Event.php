<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'event_type', 'excerpt', 'description', 'image',
        'starts_at', 'ends_at', 'location', 'city', 'mode',
        'registration_url', 'is_featured', 'is_published',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public const MODES = [
        'In-Person' => 'In-Person',
        'Hybrid Event' => 'Hybrid Event',
        'Online' => 'Online',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->published()
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '>=', now()->startOfDay()))
            ->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->published()
            ->whereNotNull('starts_at')
            ->where('starts_at', '<', now()->startOfDay())
            ->orderByDesc('starts_at');
    }

    public function getIsUpcomingAttribute(): bool
    {
        return ! $this->starts_at || $this->starts_at->gte(now()->startOfDay());
    }

    public function getDayAttribute(): string
    {
        return $this->starts_at ? $this->starts_at->format('d') : '--';
    }

    public function getMonthYearAttribute(): string
    {
        return $this->starts_at ? mb_strtoupper($this->starts_at->format('M Y')) : '';
    }
}

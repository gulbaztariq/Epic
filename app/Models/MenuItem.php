<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MenuItem extends Model
{
    protected $fillable = ['label', 'url', 'location', 'parent_id', 'target', 'sort', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** Menu rows are loaded once per request and reused by the header, footer and mobile nav. */
    protected static ?Collection $cachedItems = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::$cachedItems = null);
        static::deleted(fn () => static::$cachedItems = null);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Top level items (with their children) for a menu location.
     */
    public static function tree(string $location = 'header'): Collection
    {
        static::$cachedItems ??= static::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $all = static::$cachedItems;

        return $all
            ->where('location', $location)
            ->whereNull('parent_id')
            ->map(function (self $item) use ($all) {
                $item->setRelation('children', $all->where('parent_id', $item->id)->values());

                return $item;
            })
            ->values();
    }

    public function resolvedUrl(): string
    {
        $url = (string) $this->url;

        if ($url === '' || $url === '#') {
            return '#';
        }

        if (str_starts_with($url, 'http') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        return url($url);
    }
}

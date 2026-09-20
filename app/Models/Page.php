<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'slug', 'title', 'menu_label', 'eyebrow', 'hero_title', 'hero_subtitle', 'hero_image',
        'intro', 'body', 'quote', 'quote_author', 'cta_text', 'cta_url',
        'meta_title', 'meta_description', 'is_published', 'sort',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort');
    }

    public function activeSections(): HasMany
    {
        return $this->sections()->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Fetch a page by slug, falling back to an empty model so views never break
     * if an admin removes a page record.
     */
    public static function findBySlug(string $slug): self
    {
        return static::with('activeSections')->where('slug', $slug)->first() ?? new static(['title' => ucwords(str_replace('-', ' ', $slug))]);
    }

    public function getHeroHeadingAttribute(): string
    {
        return $this->hero_title ?: (string) $this->title;
    }

    /**
     * Fetch one of the page's content blocks by type, always returning a model
     * so Blade can read ->heading / ->link_text without null checks.
     */
    public function section(string $type): PageSection
    {
        $sections = $this->relationLoaded('activeSections')
            ? $this->activeSections
            : ($this->exists ? $this->activeSections()->get() : collect());

        return $sections->firstWhere('type', $type) ?? new PageSection(['type' => $type]);
    }
}

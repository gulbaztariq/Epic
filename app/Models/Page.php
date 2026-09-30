<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

class Page extends Model
{
    protected $fillable = [
        'slug', 'key', 'title', 'menu_label', 'eyebrow', 'hero_title', 'hero_subtitle', 'hero_image',
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
     * Fetch one of the pages the website itself depends on, by its stable key.
     *
     * The key never changes, so an editor can rename the page, retitle it or
     * change its slug without the site losing track of it. Databases from before
     * keys existed are still found by slug. Falls back to an empty model so a
     * view never breaks if the record has been removed.
     */
    public static function builtIn(string $key): self
    {
        try {
            $page = static::with('activeSections')->where('key', $key)->first();
        } catch (QueryException) {
            // The update that adds the `key` column has not been applied yet. That is
            // the moment between uploading new files and running the installer on
            // hosting without SSH, and the live site must not error in it: fall back to
            // the slug, which is what the site used before keys existed.
            $page = null;
        }

        $page ??= static::with('activeSections')->where('slug', $key)->first();

        return $page ?? new static(['title' => ucwords(str_replace('-', ' ', $key))]);
    }

    /** Whether the site itself relies on this page (its slug is then fixed). */
    public function isBuiltIn(): bool
    {
        return filled($this->key);
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

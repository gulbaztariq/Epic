<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class TeamMember extends Model
{
    protected $fillable = [
        'name', 'designation', 'category', 'photo', 'short_bio', 'bio',
        'email', 'linkedin', 'country', 'sort', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    /** Fallback names, and the page each group takes its live name from. */
    public const CATEGORIES = [
        'team' => 'EPIC Team',
        'board' => 'Board of Directors',
        'advisory' => 'Advisory Council',
    ];

    private const CATEGORY_PAGES = [
        'team' => 'epic-team',
        'board' => 'board',
        'advisory' => 'advisory-council',
    ];

    /**
     * The groups as the dashboard shows them. Each takes its name from the title
     * of its page, so renaming a page in the dashboard renames the group too.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        try {
            $titles = Page::whereIn('key', array_values(self::CATEGORY_PAGES))->pluck('title', 'key');
        } catch (QueryException) {
            $titles = collect(); // database not yet updated: use the default names
        }

        return collect(self::CATEGORIES)
            ->map(fn (string $default, string $category) => $titles[self::CATEGORY_PAGES[$category]] ?? $default)
            ->all();
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category)->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}

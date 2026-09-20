<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = [
        'name', 'designation', 'category', 'photo', 'short_bio', 'bio',
        'email', 'linkedin', 'country', 'sort', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const CATEGORIES = [
        'team' => 'EPIC Team',
        'board' => 'Board of Governance',
        'advisory' => 'Advisory Council',
    ];

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

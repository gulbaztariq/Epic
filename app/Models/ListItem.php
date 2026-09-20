<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ListItem extends Model
{
    protected $fillable = ['group', 'title', 'description', 'icon', 'image', 'url', 'sort', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * The content groups offered in the admin UI.
     */
    public const GROUPS = [
        'hero_highlights' => 'Home — Hero highlights',
        'principles' => 'Who We Are — EPIC Principles',
        'strengths' => 'Who We Are — Our Strengths',
        'board_areas' => 'Who We Are — Board of Governance areas',
        'advisory_areas' => 'Who We Are — Advisory Council guidance',
        'themes' => 'What We Do — Themes of EPIC work',
        'project_types' => 'What We Do — Types of projects',
        'event_types' => 'Events — Types of events',
        'partner_types' => 'Partnerships — Who we work with',
        'mou_scope' => 'Partnerships — MoU partnership areas',
        'memberships' => 'Partnerships — Membership networks',
        'entrepreneurship_pillars' => 'Home — Entrepreneurship pillars',
        'get_involved' => 'Get Involved — Ways to engage',
        'publication_types' => 'Publications — Our collection types',
    ];

    public static function inGroup(string $group): Collection
    {
        return static::query()
            ->where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function getGroupLabelAttribute(): string
    {
        return self::GROUPS[$this->group] ?? ucwords(str_replace('_', ' ', (string) $this->group));
    }
}

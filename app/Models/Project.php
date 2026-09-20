<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'category', 'summary', 'description', 'image', 'status',
        'partners', 'started_at', 'ended_at', 'is_featured', 'is_published', 'sort',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public const STATUSES = [
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'upcoming' => 'Upcoming',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort')->orderByDesc('id');
    }
}

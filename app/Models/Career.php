<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'type', 'location', 'summary', 'description', 'requirements',
        'deadline', 'apply_url', 'apply_email', 'is_open', 'sort',
    ];

    protected $casts = ['deadline' => 'date', 'is_open' => 'boolean'];

    public const TYPES = [
        'Full Time' => 'Full Time',
        'Part Time' => 'Part Time',
        'Internship' => 'Internship',
        'Consultancy' => 'Consultancy',
        'Fellowship' => 'Fellowship',
        'Volunteer' => 'Volunteer',
    ];

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true)->orderBy('sort')->orderByDesc('id');
    }
}

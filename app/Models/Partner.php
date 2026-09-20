<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name', 'type', 'category', 'logo', 'website', 'description',
        'country', 'signed_on', 'sort', 'is_active',
    ];

    protected $casts = ['signed_on' => 'date', 'is_active' => 'boolean'];

    public const TYPES = [
        'partnership' => 'Partnership',
        'mou' => 'MoU',
        'membership' => 'Membership',
    ];

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->where('is_active', true)->orderBy('sort')->orderBy('name');
    }
}

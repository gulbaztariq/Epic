<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = [
        'country', 'city', 'description', 'image', 'contact_name',
        'contact_email', 'status', 'sort', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const STATUSES = ['active' => 'Active', 'forming' => 'Forming'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('country');
    }
}

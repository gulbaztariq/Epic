<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Podcast extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'episode_number', 'guest', 'description', 'cover_image',
        'audio_url', 'embed_url', 'duration', 'published_at', 'is_published',
    ];

    protected $casts = ['published_at' => 'date', 'is_published' => 'boolean'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at')->orderByDesc('id');
    }
}

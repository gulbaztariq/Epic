<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'description', 'cover_image', 'location',
        'event_date', 'is_published', 'sort',
    ];

    protected $casts = ['event_date' => 'date', 'is_published' => 'boolean'];

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort')->orderByDesc('event_date');
    }

    public function getCoverAttribute(): ?string
    {
        return $this->cover_image ?: $this->images->first()?->image;
    }
}

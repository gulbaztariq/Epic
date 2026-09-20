<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'category', 'author', 'excerpt', 'body', 'image',
        'tags', 'published_at', 'is_featured', 'is_published',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public const CATEGORIES = [
        'blog' => 'Blog',
        'article' => 'Article',
        'press_release' => 'Press Release',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at')->orderByDesc('id');
    }

    public function scopeCategory(Builder $query, string|array $category): Builder
    {
        return $query->whereIn('category', (array) $category);
    }

    public function getSummaryAttribute(): string
    {
        return filled($this->excerpt)
            ? $this->excerpt
            : Str::limit(strip_tags((string) $this->body), 160);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function publicUrl(): string
    {
        return $this->category === 'press_release'
            ? route('media.press.show', $this->slug)
            : route('blogs.show', $this->slug);
    }
}

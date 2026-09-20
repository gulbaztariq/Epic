<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasSlug;

    protected $fillable = [
        'title', 'slug', 'subtitle', 'type', 'collection', 'authors', 'abstract', 'body',
        'cover_image', 'file_path', 'external_url', 'theme', 'issue',
        'published_at', 'is_featured', 'is_published', 'sort',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public const TYPES = [
        'Research Report' => 'Research Report',
        'Policy Brief' => 'Policy Brief',
        'Research Paper' => 'Research Paper',
        'Working Paper' => 'Working Paper',
        'Book' => 'Book',
        'Journal Article' => 'Journal Article',
        'Journal Issue' => 'Journal Issue',
        'E-Newsletter' => 'E-Newsletter',
        'Discussion Paper' => 'Discussion Paper',
    ];

    public const COLLECTIONS = [
        'collection' => 'Our Collection',
        'journal' => 'Journal (HEC-recognized)',
        'newsletter' => 'E-Newsletter',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at')->orderByDesc('id');
    }

    public function scopeCollection(Builder $query, string $collection): Builder
    {
        return $query->where('collection', $collection);
    }

    public function downloadUrl(): ?string
    {
        if (filled($this->file_path)) {
            return uploaded_url($this->file_path);
        }

        return filled($this->external_url) ? $this->external_url : null;
    }
}

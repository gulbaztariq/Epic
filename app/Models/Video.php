<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title', 'description', 'youtube_url', 'thumbnail',
        'published_at', 'is_featured', 'is_published', 'sort',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort')->orderByDesc('published_at');
    }

    /**
     * Pull the video id out of any common YouTube URL shape.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        $url = (string) $this->youtube_url;

        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return $m[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{6,}$~', $url) ? $url : null;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return $this->youtube_id ? 'https://www.youtube.com/embed/'.$this->youtube_id : null;
    }

    public function getWatchUrlAttribute(): ?string
    {
        return $this->youtube_id ? 'https://www.youtube.com/watch?v='.$this->youtube_id : $this->youtube_url;
    }

    public function getPosterAttribute(): ?string
    {
        if (filled($this->thumbnail)) {
            return uploaded_url($this->thumbnail);
        }

        return $this->youtube_id ? 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg' : null;
    }
}

<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Video;

class VideoController extends ResourceController
{
    protected string $model = Video::class;

    protected string $uri = 'videos';

    protected string $title = 'Videos';

    protected string $singular = 'Video';

    protected string $description = 'YouTube videos shown on the Media → YouTube page.';

    protected string $icon = 'youtube';

    protected string $uploadFolder = 'videos';

    protected array $searchable = ['title', 'description'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['key' => 'thumbnail', 'label' => 'Thumb', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Video', 'type' => 'title', 'sub' => 'description'],
            ['key' => 'youtube_url', 'label' => 'YouTube', 'limit' => 40],
            ['key' => 'published_at', 'label' => 'Published', 'type' => 'date'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:255', 'col' => 8]),
            self::field('published_at', 'Published on', 'date', ['col' => 4]),
            self::field('youtube_url', 'YouTube link', 'text', [
                'col' => 12, 'rules' => 'required|string|max:500',
                'placeholder' => 'https://www.youtube.com/watch?v=…',
                'hint' => 'Full watch link, share link or the video ID.',
            ]),
            self::field('description', 'Description', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:1000']),
            self::field('thumbnail', 'Custom thumbnail', 'image', ['col' => 6, 'hint' => 'Optional — the YouTube thumbnail is used automatically.']),
            self::field('is_featured', 'Show as the main video', 'checkbox', ['col' => 2]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 2, 'default' => true]),
            self::field('sort', 'Order', 'number', ['col' => 2, 'default' => 0]),
        ];
    }
}

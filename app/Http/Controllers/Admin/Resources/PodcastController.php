<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Podcast;
use Illuminate\Validation\Rule;

class PodcastController extends ResourceController
{
    protected string $model = Podcast::class;

    protected string $uri = 'podcasts';

    protected string $title = 'Podcast episodes';

    protected string $singular = 'Episode';

    protected string $description = 'EPIC podcast episodes, with audio upload or an embed from your podcast host.';

    protected string $icon = 'mic';

    protected string $uploadFolder = 'podcast';

    protected array $searchable = ['title', 'guest', 'description'];

    protected string $orderBy = 'published_at';

    protected function columns(): array
    {
        return [
            ['key' => 'cover_image', 'label' => 'Cover', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Episode', 'type' => 'title', 'sub' => 'guest'],
            ['key' => 'episode_number', 'label' => 'No.'],
            ['key' => 'duration', 'label' => 'Duration'],
            ['key' => 'published_at', 'label' => 'Published', 'type' => 'date'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Episode'),
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:255', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Podcast $r) => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('podcasts', 'slug')->ignore($r?->id)],
            ]),
            self::field('episode_number', 'Episode number', 'text', ['col' => 3]),
            self::field('guest', 'Guest(s)', 'text', ['col' => 5]),
            self::field('duration', 'Duration', 'text', ['col' => 2, 'placeholder' => '32 min']),
            self::field('published_at', 'Published on', 'date', ['col' => 2]),
            self::field('description', 'Description', 'richtext', ['col' => 12]),

            self::section('Media'),
            self::field('cover_image', 'Cover image', 'image', ['col' => 6]),
            self::field('audio_url', 'Audio file', 'file', ['col' => 6, 'accept' => 'audio/*', 'rules' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:20480']),
            self::field('embed_url', 'Embed URL', 'text', ['col' => 12, 'hint' => 'Spotify, Anchor, YouTube or SoundCloud embed link. Used instead of the audio file when set.']),
            self::field('is_published', 'Published', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }
}

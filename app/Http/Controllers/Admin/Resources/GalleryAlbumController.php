<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\GalleryAlbum;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GalleryAlbumController extends ResourceController
{
    protected string $model = GalleryAlbum::class;

    protected string $uri = 'gallery-albums';

    protected string $title = 'Photo gallery';

    protected string $singular = 'Album';

    protected string $description = 'Photo albums. Save an album first, then add photos to it.';

    protected string $icon = 'image';

    protected string $uploadFolder = 'gallery';

    protected array $searchable = ['title', 'description', 'location'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['key' => 'cover_image', 'label' => 'Cover', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Album', 'type' => 'title', 'sub' => 'description'],
            ['key' => 'images_count', 'label' => 'Photos'],
            ['key' => 'event_date', 'label' => 'Date', 'type' => 'date'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
        ];
    }

    protected function newQuery(): Builder
    {
        return GalleryAlbum::query()->withCount('images');
    }

    protected function fields(): array
    {
        return [
            self::field('title', 'Album title', 'text', ['rules' => 'required|string|max:190', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?GalleryAlbum $r) => ['nullable', 'string', 'max:190', 'alpha_dash', Rule::unique('gallery_albums', 'slug')->ignore($r?->id)],
            ]),
            self::field('description', 'Description', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:1000']),
            self::field('cover_image', 'Cover image', 'image', ['col' => 6, 'hint' => 'Optional — the first photo is used otherwise.']),
            self::field('location', 'Location', 'text', ['col' => 3]),
            self::field('event_date', 'Date', 'date', ['col' => 3]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 6, 'default' => true]),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
        ];
    }

    /**
     * After saving, send the admin straight to the album's photo manager.
     */
    public function store(Request $request, MediaService $media)
    {
        parent::store($request, $media);

        return redirect()
            ->route('admin.gallery-albums.edit', $this->savedRecord)
            ->with('success', 'Album created. You can now add photos below.');
    }

    protected function formPartial(?Model $record): ?string
    {
        return 'admin.gallery.images';
    }
}

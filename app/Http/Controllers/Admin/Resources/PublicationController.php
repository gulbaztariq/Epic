<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Publication;
use Illuminate\Validation\Rule;

class PublicationController extends ResourceController
{
    protected string $model = Publication::class;

    protected string $uri = 'publications';

    protected string $title = 'Publications';

    protected string $singular = 'Publication';

    protected string $description = 'Research reports, policy briefs, working papers, journal issues and newsletters.';

    protected string $icon = 'book';

    protected string $uploadFolder = 'publications';

    protected array $searchable = ['title', 'subtitle', 'authors', 'abstract'];

    protected string $orderBy = 'published_at';

    protected function filters(): array
    {
        return [
            'collection' => ['label' => 'collections', 'options' => Publication::COLLECTIONS],
            'type' => ['label' => 'types', 'options' => Publication::TYPES],
        ];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'cover_image', 'label' => 'Cover', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Title', 'type' => 'title', 'sub' => 'subtitle'],
            ['key' => 'type', 'label' => 'Type', 'type' => 'badge'],
            ['key' => 'collection', 'label' => 'Collection', 'type' => 'badge', 'map' => Publication::COLLECTIONS, 'tone' => 'badge-grey'],
            ['key' => 'published_at', 'label' => 'Published', 'type' => 'date'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Publication details'),
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:255', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Publication $r) => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('publications', 'slug')->ignore($r?->id)],
                'hint' => 'Leave empty to generate from the title.',
            ]),
            self::field('subtitle', 'Subtitle', 'text', ['col' => 12]),
            self::field('type', 'Type', 'select', ['col' => 4, 'options' => Publication::TYPES, 'rules' => 'required|string|max:80', 'default' => 'Research Report']),
            self::field('collection', 'Collection', 'select', ['col' => 4, 'options' => Publication::COLLECTIONS, 'rules' => 'required|string|max:40', 'default' => 'collection']),
            self::field('published_at', 'Publication date', 'date', ['col' => 4]),
            self::field('authors', 'Author(s)', 'text', ['col' => 6]),
            self::field('theme', 'Theme', 'text', ['col' => 3, 'placeholder' => 'Human Capital']),
            self::field('issue', 'Volume / issue', 'text', ['col' => 3, 'placeholder' => 'Vol 1, Issue 2']),

            self::section('Content'),
            self::field('abstract', 'Abstract / summary', 'textarea', ['col' => 12, 'rows' => 4, 'rules' => 'nullable|string|max:2000']),
            self::field('body', 'Full text', 'richtext', ['col' => 12]),

            self::section('Files'),
            self::field('cover_image', 'Cover image', 'image', ['col' => 6, 'hint' => 'Portrait works best (3:4).']),
            self::field('file_path', 'PDF file', 'file', ['col' => 6, 'accept' => '.pdf,.doc,.docx', 'rules' => 'nullable|file|mimes:pdf,doc,docx|max:20480']),
            self::field('external_url', 'External link', 'text', ['col' => 12, 'hint' => 'Used when no PDF is uploaded.']),

            self::section('Visibility'),
            self::field('is_featured', 'Feature on the home page', 'checkbox', ['col' => 4]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 4, 'default' => true]),
            self::field('sort', 'Display order', 'number', ['col' => 4, 'default' => 0]),
        ];
    }
}

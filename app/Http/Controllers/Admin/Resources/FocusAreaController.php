<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\FocusArea;

class FocusAreaController extends ResourceController
{
    protected string $model = FocusArea::class;

    protected string $uri = 'focus-areas';

    protected string $title = 'Focus areas';

    protected string $singular = 'Focus area';

    protected string $description = 'The icon strip shown near the top of the home page.';

    protected string $icon = 'target';

    protected array $searchable = ['title', 'description'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['key' => 'title', 'label' => 'Focus area', 'type' => 'title', 'sub' => 'description'],
            ['key' => 'color', 'label' => 'Accent', 'type' => 'badge', 'map' => ['navy' => 'Blue', 'green' => 'Green']],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:120', 'col' => 6]),
            self::field('icon', 'Icon', 'icon', ['col' => 6, 'default' => 'chart', 'rules' => 'required|string|max:60', 'required' => true]),
            self::field('description', 'Short description', 'textarea', ['col' => 12, 'rows' => 2, 'rules' => 'nullable|string|max:255']),
            self::field('url', 'Link', 'text', ['col' => 6, 'placeholder' => '/what-we-do/themes']),
            self::field('color', 'Icon colour', 'select', ['col' => 6, 'options' => ['navy' => 'Blue', 'green' => 'Green'], 'default' => 'navy', 'rules' => 'required|in:navy,green']),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }
}

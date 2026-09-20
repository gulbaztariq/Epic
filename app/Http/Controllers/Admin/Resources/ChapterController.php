<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Chapter;

class ChapterController extends ResourceController
{
    protected string $model = Chapter::class;

    protected string $uri = 'chapters';

    protected string $title = 'International chapters';

    protected string $singular = 'Chapter';

    protected string $description = 'EPIC chapters and country presence.';

    protected string $icon = 'globe';

    protected string $uploadFolder = 'chapters';

    protected array $searchable = ['country', 'city', 'description'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function filters(): array
    {
        return ['status' => ['label' => 'statuses', 'options' => Chapter::STATUSES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'image', 'label' => 'Flag', 'type' => 'image'],
            ['key' => 'country', 'label' => 'Country', 'type' => 'title', 'sub' => 'city'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'map' => Chapter::STATUSES],
            ['key' => 'contact_email', 'label' => 'Contact'],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Visibility', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('country', 'Country', 'text', ['rules' => 'required|string|max:120', 'col' => 6]),
            self::field('city', 'City', 'text', ['col' => 6]),
            self::field('description', 'Description', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:1000']),
            self::field('image', 'Flag or image', 'image', ['col' => 6]),
            self::field('status', 'Status', 'select', ['col' => 6, 'options' => Chapter::STATUSES, 'default' => 'active', 'rules' => 'required|string|max:40']),
            self::field('contact_name', 'Contact name', 'text', ['col' => 4]),
            self::field('contact_email', 'Contact email', 'email', ['col' => 4]),
            self::field('sort', 'Display order', 'number', ['col' => 2, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 2, 'default' => true]),
        ];
    }
}

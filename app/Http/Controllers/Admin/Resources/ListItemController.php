<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\ListItem;

class ListItemController extends ResourceController
{
    protected string $model = ListItem::class;

    protected string $uri = 'list-items';

    protected string $title = 'Content lists';

    protected string $singular = 'List item';

    protected string $description = 'Reusable lists: principles, strengths, themes, project types, event types, partnership areas and more.';

    protected string $icon = 'list';

    protected array $searchable = ['title', 'description'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected ?string $primaryOrderBy = 'group';

    protected int $perPage = 30;

    protected function filters(): array
    {
        return ['group' => ['label' => 'lists', 'options' => ListItem::GROUPS]];
    }

    protected function indexNote(): ?string
    {
        return 'Each item belongs to a list. The list decides where it appears on the website — for example
                <strong>Who We Are — EPIC Principles</strong> feeds the EPIC Principles page.';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'group', 'label' => 'List', 'type' => 'badge', 'map' => ListItem::GROUPS, 'tone' => 'badge-grey'],
            ['key' => 'title', 'label' => 'Item', 'type' => 'title', 'sub' => 'description'],
            ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('group', 'List', 'select', [
                'col' => 6, 'rules' => 'required|string|max:80',
                'options' => ListItem::GROUPS,
            ]),
            self::field('icon', 'Icon', 'icon', ['col' => 6]),
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:190', 'col' => 12]),
            self::field('description', 'Description', 'textarea', ['col' => 12, 'rows' => 4, 'rules' => 'nullable|string|max:2000']),
            self::field('image', 'Image', 'image', ['col' => 6, 'folder' => 'lists']),
            self::field('url', 'Link', 'text', ['col' => 6]),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }
}

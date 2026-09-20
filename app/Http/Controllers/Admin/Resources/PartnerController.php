<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Partner;

class PartnerController extends ResourceController
{
    protected string $model = Partner::class;

    protected string $uri = 'partners';

    protected string $title = 'Partners & MoUs';

    protected string $singular = 'Partner';

    protected string $description = 'Partner organisations, signed MoUs and network memberships.';

    protected string $icon = 'partnership';

    protected string $uploadFolder = 'partners';

    protected array $searchable = ['name', 'category', 'country'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected ?string $primaryOrderBy = 'type';

    protected function filters(): array
    {
        return ['type' => ['label' => 'types', 'options' => Partner::TYPES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'logo', 'label' => 'Logo', 'type' => 'image'],
            ['key' => 'name', 'label' => 'Organisation', 'type' => 'title', 'sub' => 'description'],
            ['key' => 'type', 'label' => 'Type', 'type' => 'badge', 'map' => Partner::TYPES],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'country', 'label' => 'Country'],
            ['key' => 'is_active', 'label' => 'Visibility', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('name', 'Organisation name', 'text', ['rules' => 'required|string|max:190', 'col' => 6]),
            self::field('type', 'Listed under', 'select', ['col' => 6, 'options' => Partner::TYPES, 'default' => 'partnership', 'rules' => 'required|string|max:40']),
            self::field('category', 'Category', 'text', ['col' => 6, 'placeholder' => 'University · Think tank · Government']),
            self::field('country', 'Country', 'text', ['col' => 6]),
            self::field('description', 'Description', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:1000']),
            self::field('logo', 'Logo', 'image', ['col' => 6]),
            self::field('website', 'Website', 'text', ['col' => 6]),
            self::field('signed_on', 'MoU signed on', 'date', ['col' => 4]),
            self::field('sort', 'Display order', 'number', ['col' => 4, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 4, 'default' => true]),
        ];
    }
}

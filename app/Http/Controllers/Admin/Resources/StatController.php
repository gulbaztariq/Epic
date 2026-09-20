<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Stat;

class StatController extends ResourceController
{
    protected string $model = Stat::class;

    protected string $uri = 'stats';

    protected string $title = 'Data & insights';

    protected string $singular = 'Statistic';

    protected string $description = 'The headline figures shown in the Data & Insights strip on the home page.';

    protected string $icon = 'chart';

    protected array $searchable = ['label', 'value', 'caption'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['key' => 'label', 'label' => 'Label', 'type' => 'title', 'sub' => 'caption'],
            ['key' => 'value', 'label' => 'Value'],
            ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('label', 'Label', 'text', ['rules' => 'required|string|max:120', 'col' => 6, 'placeholder' => 'GDP GROWTH (REAL)']),
            self::field('value', 'Value', 'text', ['rules' => 'required|string|max:60', 'col' => 6, 'placeholder' => '2.4%']),
            self::field('caption', 'Caption', 'text', ['col' => 8, 'placeholder' => 'Pakistan | FY 2023']),
            self::field('icon', 'Icon', 'icon', ['col' => 4, 'default' => 'chart', 'required' => true, 'rules' => 'required|string|max:60']),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }
}

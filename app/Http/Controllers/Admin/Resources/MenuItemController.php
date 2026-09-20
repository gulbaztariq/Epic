<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MenuItemController extends ResourceController
{
    protected string $model = MenuItem::class;

    protected string $uri = 'menu-items';

    protected string $title = 'Navigation menus';

    protected string $singular = 'Menu item';

    protected string $description = 'Header, footer and legal navigation links.';

    protected string $icon = 'menu';

    protected array $searchable = ['label', 'url'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected ?string $primaryOrderBy = 'location';

    protected function locations(): array
    {
        return [
            'header' => 'Header menu',
            'footer' => 'Footer columns',
            'footer_legal' => 'Footer bottom links',
        ];
    }

    protected function filters(): array
    {
        return ['location' => ['label' => 'menus', 'options' => $this->locations()]];
    }

    protected function indexNote(): ?string
    {
        return 'Leave <strong>Parent</strong> empty for a top-level item. In the footer, top-level items become column headings.
                Use site-relative links such as <code>/publications</code> so they keep working if the domain changes.';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'label', 'label' => 'Label', 'type' => 'title', 'sub' => 'url'],
            ['key' => 'location', 'label' => 'Menu', 'type' => 'badge', 'map' => $this->locations()],
            ['key' => 'parent.label', 'label' => 'Parent'],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function newQuery(): Builder
    {
        return MenuItem::query()->with('parent');
    }

    protected function fields(): array
    {
        $parents = MenuItem::whereNull('parent_id')
            ->orderBy('location')
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->id => ($this->locations()[$item->location] ?? $item->location).' — '.$item->label])
            ->all();

        return [
            self::field('label', 'Label', 'text', ['rules' => 'required|string|max:120', 'col' => 6]),
            self::field('url', 'Link', 'text', ['rules' => 'required|string|max:500', 'col' => 6, 'default' => '/', 'placeholder' => '/publications']),
            self::field('location', 'Menu', 'select', [
                'col' => 4, 'rules' => 'required|string|max:40',
                'options' => $this->locations(), 'default' => 'header',
            ]),
            self::field('parent_id', 'Parent item', 'select', [
                'col' => 4, 'placeholder' => 'None (top level)',
                'options' => $parents, 'rules' => 'nullable|exists:menu_items,id',
            ]),
            self::field('target', 'Opens in', 'select', [
                'col' => 4, 'options' => ['_self' => 'Same tab', '_blank' => 'New tab'], 'default' => '_self',
                'rules' => 'required|in:_self,_blank',
            ]),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }

    protected function cannotDelete(Model $record): ?string
    {
        return $record->children()->exists()
            ? 'Remove or reassign the child menu items first.'
            : null;
    }
}

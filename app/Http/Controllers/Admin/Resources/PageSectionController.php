<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\ListItem;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Builder;

class PageSectionController extends ResourceController
{
    protected string $model = PageSection::class;

    protected string $uri = 'page-sections';

    protected string $title = 'Page sections';

    protected string $singular = 'Section';

    protected string $description = 'Extra content blocks that appear on a page, in the order you set.';

    protected string $icon = 'layers';

    protected string $uploadFolder = 'sections';

    protected array $searchable = ['heading', 'subheading'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected ?string $primaryOrderBy = 'page_id';

    protected function newQuery(): Builder
    {
        return PageSection::query()->with('page');
    }

    protected function filters(): array
    {
        return [
            'page_id' => ['label' => 'pages', 'options' => Page::orderBy('title')->pluck('title', 'id')->all()],
            'type' => ['label' => 'types', 'options' => $this->types()],
        ];
    }

    protected function indexNote(): ?string
    {
        return 'Home page blocks use the reserved types <code>focus</code>, <code>publications</code>, <code>events</code>,
                <code>entrepreneurship</code> and <code>stats</code> to set those section headings and links.';
    }

    protected function types(): array
    {
        return [
            'text' => 'Text block',
            'list' => 'Checklist from a content list',
            'cards' => 'Icon cards from a content list',
            'image_text' => 'Image + text',
            'quote' => 'Quote',
            'accordion' => 'Accordion (FAQ style)',
            'cta' => 'Call to action band',
            'focus' => 'Home: focus areas heading',
            'publications' => 'Home: publications heading',
            'events' => 'Home: events heading',
            'entrepreneurship' => 'Home: entrepreneurship band',
            'stats' => 'Home: data & insights heading',
            'vision' => 'Vision block',
            'mission' => 'Mission block',
        ];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'page.title', 'label' => 'Page'],
            ['key' => 'heading', 'label' => 'Heading', 'type' => 'title', 'sub' => 'subheading'],
            ['key' => 'type', 'label' => 'Type', 'type' => 'badge', 'map' => $this->types()],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Placement'),
            self::field('page_id', 'Page', 'select', [
                'col' => 6,
                'rules' => 'required|exists:pages,id',
                'options' => Page::orderBy('title')->pluck('title', 'id')->all(),
            ]),
            self::field('type', 'Block type', 'select', [
                'col' => 6,
                'rules' => 'required|string|max:60',
                'options' => $this->types(),
                'default' => 'text',
            ]),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
            self::field('is_active', 'Visible on the website', 'checkbox', ['col' => 6, 'default' => true]),

            self::section('Content'),
            self::field('heading', 'Heading', 'text', ['col' => 6]),
            self::field('subheading', 'Sub heading / quote', 'textarea', ['col' => 6, 'rows' => 3]),
            self::field('body', 'Body', 'richtext', ['col' => 12]),
            self::field('image', 'Image', 'image', ['col' => 6]),
            self::field('list_group', 'Content list to display', 'select', [
                'col' => 6,
                'placeholder' => 'None',
                'options' => ListItem::GROUPS,
                'rules' => 'nullable|string|max:80',
                'hint' => 'Used by the checklist, cards and accordion block types.',
            ]),

            self::section('Link'),
            self::field('link_text', 'Link label', 'text', ['col' => 6]),
            self::field('link_url', 'Link URL', 'text', ['col' => 6]),
        ];
    }
}

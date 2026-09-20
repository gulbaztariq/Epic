<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Page;
use Illuminate\Validation\Rule;

class PageController extends ResourceController
{
    protected string $model = Page::class;

    protected string $uri = 'pages';

    protected string $title = 'Pages';

    protected string $singular = 'Page';

    protected string $description = 'Hero copy, introductions and SEO for every page of the website.';

    protected string $icon = 'document';

    protected string $uploadFolder = 'pages';

    protected array $searchable = ['title', 'slug'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function indexNote(): ?string
    {
        return 'Page <strong>slugs</strong> connect each record to a page of the website (for example <code>about-us</code>).
                Changing a slug of a built-in page will stop that page from showing its content, so only edit slugs for pages you created yourself.';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'title', 'label' => 'Page', 'type' => 'title', 'sub' => 'hero_title'],
            ['key' => 'slug', 'label' => 'Slug'],
            ['key' => 'hero_image', 'label' => 'Hero', 'type' => 'image'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
            ['key' => 'sort', 'label' => 'Order'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Page identity'),
            self::field('title', 'Page title', 'text', ['rules' => 'required|string|max:190', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Page $record) => ['required', 'string', 'max:190', 'alpha_dash', Rule::unique('pages', 'slug')->ignore($record?->id)],
                'hint' => 'Used by the website to find this page.',
            ]),
            self::field('menu_label', 'Short menu label', 'text', ['col' => 6, 'hint' => 'Optional shorter label for menus.']),
            self::field('sort', 'Display order', 'number', ['col' => 3, 'default' => 0]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 3, 'default' => true]),

            self::section('Hero'),
            self::field('eyebrow', 'Eyebrow / kicker', 'text', ['col' => 6, 'hint' => 'Small line above the heading.']),
            self::field('hero_title', 'Hero heading', 'textarea', [
                'col' => 6, 'rows' => 3,
                'hint' => 'On the home page each line becomes its own line, and the last line is highlighted in green.',
            ]),
            self::field('hero_subtitle', 'Hero text', 'textarea', ['col' => 12, 'rows' => 3]),
            self::field('hero_image', 'Hero image', 'image', ['col' => 6, 'hint' => 'Recommended 1600×1000px or larger.']),
            self::field('quote', 'Highlight quote', 'textarea', ['col' => 6, 'rows' => 3]),
            self::field('quote_author', 'Quote attribution', 'text', ['col' => 6]),

            self::section('Body content'),
            self::field('intro', 'Introduction', 'textarea', ['col' => 12, 'rows' => 4]),
            self::field('body', 'Main content', 'richtext', ['col' => 12]),

            self::section('Call to action'),
            self::field('cta_text', 'Button label', 'text', ['col' => 6]),
            self::field('cta_url', 'Button link', 'text', ['col' => 6, 'placeholder' => '/publications']),

            self::section('Search engine listing'),
            self::field('meta_title', 'Meta title', 'text', ['col' => 6, 'rules' => 'nullable|string|max:190']),
            self::field('meta_description', 'Meta description', 'textarea', ['col' => 6, 'rows' => 3, 'rules' => 'nullable|string|max:300']),
        ];
    }
}

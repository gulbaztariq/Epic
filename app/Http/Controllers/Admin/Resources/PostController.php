<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Post;
use Illuminate\Validation\Rule;

class PostController extends ResourceController
{
    protected string $model = Post::class;

    protected string $uri = 'posts';

    protected string $title = 'Blogs & press';

    protected string $singular = 'Post';

    protected string $description = 'Blogs, articles and press releases.';

    protected string $icon = 'edit';

    protected string $uploadFolder = 'posts';

    protected array $searchable = ['title', 'excerpt', 'author', 'tags'];

    protected string $orderBy = 'published_at';

    protected function filters(): array
    {
        return ['category' => ['label' => 'categories', 'options' => Post::CATEGORIES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Title', 'type' => 'title', 'sub' => 'excerpt'],
            ['key' => 'category', 'label' => 'Category', 'type' => 'badge', 'map' => Post::CATEGORIES],
            ['key' => 'author', 'label' => 'Author'],
            ['key' => 'published_at', 'label' => 'Published', 'type' => 'date'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Post details'),
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:255', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Post $r) => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('posts', 'slug')->ignore($r?->id)],
            ]),
            self::field('category', 'Category', 'select', ['col' => 4, 'options' => Post::CATEGORIES, 'default' => 'blog', 'rules' => 'required|string|max:40']),
            self::field('author', 'Author', 'text', ['col' => 4]),
            self::field('published_at', 'Publication date', 'date', ['col' => 4]),
            self::field('tags', 'Tags', 'text', ['col' => 12, 'placeholder' => 'Human capital, Skills, Youth']),

            self::section('Content'),
            self::field('excerpt', 'Short summary', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:600']),
            self::field('body', 'Body', 'richtext', ['col' => 12]),
            self::field('image', 'Featured image', 'image', ['col' => 6]),

            self::section('Visibility'),
            self::field('is_featured', 'Featured', 'checkbox', ['col' => 6]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }
}

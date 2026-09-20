<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Project;
use Illuminate\Validation\Rule;

class ProjectController extends ResourceController
{
    protected string $model = Project::class;

    protected string $uri = 'projects';

    protected string $title = 'Projects';

    protected string $singular = 'Project';

    protected string $description = 'Research, policy, capacity-building and development projects.';

    protected string $icon = 'briefcase';

    protected string $uploadFolder = 'projects';

    protected array $searchable = ['title', 'summary', 'category', 'partners'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function filters(): array
    {
        return ['status' => ['label' => 'statuses', 'options' => Project::STATUSES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Project', 'type' => 'title', 'sub' => 'summary'],
            ['key' => 'category', 'label' => 'Theme'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'map' => Project::STATUSES],
            ['key' => 'is_published', 'label' => 'Visibility', 'type' => 'boolean'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Project details'),
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:255', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Project $r) => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('projects', 'slug')->ignore($r?->id)],
            ]),
            self::field('category', 'Theme', 'text', ['col' => 6, 'placeholder' => 'Human Capital Development']),
            self::field('status', 'Status', 'select', ['col' => 6, 'options' => Project::STATUSES, 'default' => 'ongoing', 'rules' => 'required|string|max:40']),
            self::field('started_at', 'Start date', 'date', ['col' => 6]),
            self::field('ended_at', 'End date', 'date', ['col' => 6]),
            self::field('partners', 'Partners', 'text', ['col' => 12]),

            self::section('Content'),
            self::field('summary', 'Short summary', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:600']),
            self::field('description', 'Full description', 'richtext', ['col' => 12]),
            self::field('image', 'Project image', 'image', ['col' => 6]),

            self::section('Visibility'),
            self::field('is_featured', 'Featured', 'checkbox', ['col' => 4]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 4, 'default' => true]),
            self::field('sort', 'Display order', 'number', ['col' => 4, 'default' => 0]),
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Career;
use Illuminate\Validation\Rule;

class CareerController extends ResourceController
{
    protected string $model = Career::class;

    protected string $uri = 'careers';

    protected string $title = 'Careers';

    protected string $singular = 'Vacancy';

    protected string $description = 'Jobs, internships, fellowships and consultancy opportunities.';

    protected string $icon = 'flag';

    protected array $searchable = ['title', 'summary', 'location'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected function filters(): array
    {
        return ['type' => ['label' => 'types', 'options' => Career::TYPES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'title', 'label' => 'Role', 'type' => 'title', 'sub' => 'summary'],
            ['key' => 'type', 'label' => 'Type', 'type' => 'badge'],
            ['key' => 'location', 'label' => 'Location'],
            ['key' => 'deadline', 'label' => 'Deadline', 'type' => 'date'],
            ['key' => 'is_open', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Open', 'off' => 'Closed'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Role'),
            self::field('title', 'Job title', 'text', ['rules' => 'required|string|max:190', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Career $r) => ['nullable', 'string', 'max:190', 'alpha_dash', Rule::unique('careers', 'slug')->ignore($r?->id)],
            ]),
            self::field('type', 'Type', 'select', ['col' => 4, 'options' => Career::TYPES, 'default' => 'Full Time', 'rules' => 'required|string|max:40']),
            self::field('location', 'Location', 'text', ['col' => 4, 'placeholder' => 'Islamabad / Remote']),
            self::field('deadline', 'Application deadline', 'date', ['col' => 4]),

            self::section('Description'),
            self::field('summary', 'Short summary', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:600']),
            self::field('description', 'Role description', 'richtext', ['col' => 12]),
            self::field('requirements', 'Requirements', 'richtext', ['col' => 12]),

            self::section('How to apply'),
            self::field('apply_url', 'Application link', 'text', ['col' => 6]),
            self::field('apply_email', 'Application email', 'email', ['col' => 6]),
            self::field('is_open', 'Open for applications', 'checkbox', ['col' => 6, 'default' => true]),
            self::field('sort', 'Display order', 'number', ['col' => 6, 'default' => 0]),
        ];
    }
}

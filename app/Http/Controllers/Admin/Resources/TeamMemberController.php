<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\TeamMember;

class TeamMemberController extends ResourceController
{
    protected string $model = TeamMember::class;

    protected string $uri = 'team-members';

    protected string $title = 'Team & councils';

    protected string $singular = 'Member';

    protected string $description = 'EPIC team, Board of Governance and Advisory Council profiles.';

    protected string $icon = 'users';

    protected string $uploadFolder = 'people';

    protected array $searchable = ['name', 'designation', 'short_bio'];

    protected string $orderBy = 'sort';

    protected string $orderDir = 'asc';

    protected ?string $primaryOrderBy = 'category';

    protected function filters(): array
    {
        return ['category' => ['label' => 'groups', 'options' => TeamMember::CATEGORIES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'photo', 'label' => 'Photo', 'type' => 'image', 'round' => true],
            ['key' => 'name', 'label' => 'Name', 'type' => 'title', 'sub' => 'designation'],
            ['key' => 'category', 'label' => 'Group', 'type' => 'badge', 'map' => TeamMember::CATEGORIES],
            ['key' => 'country', 'label' => 'Country'],
            ['key' => 'sort', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Visible', 'off' => 'Hidden'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Profile'),
            self::field('name', 'Full name', 'text', ['rules' => 'required|string|max:190', 'col' => 6]),
            self::field('designation', 'Designation', 'text', ['col' => 6]),
            self::field('category', 'Group', 'select', ['col' => 6, 'options' => TeamMember::CATEGORIES, 'default' => 'team', 'rules' => 'required|string|max:40']),
            self::field('country', 'Country', 'text', ['col' => 6]),
            self::field('photo', 'Photograph', 'image', ['col' => 6, 'hint' => 'Square images look best.']),
            self::field('short_bio', 'Short bio', 'textarea', ['col' => 6, 'rows' => 4, 'rules' => 'nullable|string|max:600']),
            self::field('bio', 'Full biography', 'richtext', ['col' => 12]),

            self::section('Contact & order'),
            self::field('email', 'Email', 'email', ['col' => 4]),
            self::field('linkedin', 'LinkedIn URL', 'text', ['col' => 4]),
            self::field('sort', 'Display order', 'number', ['col' => 2, 'default' => 0]),
            self::field('is_active', 'Visible', 'checkbox', ['col' => 2, 'default' => true]),
        ];
    }
}

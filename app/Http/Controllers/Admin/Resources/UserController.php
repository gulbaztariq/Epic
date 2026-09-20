<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends ResourceController
{
    protected string $model = User::class;

    protected string $uri = 'users';

    protected string $title = 'Admin users';

    protected string $singular = 'User';

    protected string $description = 'People who can sign in to this dashboard.';

    protected string $icon = 'shield';

    protected string $uploadFolder = 'avatars';

    protected array $searchable = ['name', 'email'];

    protected string $orderBy = 'id';

    protected string $orderDir = 'asc';

    protected function filters(): array
    {
        return ['role' => ['label' => 'roles', 'options' => User::ROLES]];
    }

    protected function indexNote(): ?string
    {
        return 'Roles: <strong>Super Admin</strong> and <strong>Administrator</strong> can manage everything including users and settings.
                <strong>Editor</strong> accounts can manage content only.';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'avatar', 'label' => 'Avatar', 'type' => 'image', 'round' => true],
            ['key' => 'name', 'label' => 'Name', 'type' => 'title', 'sub' => 'email'],
            ['key' => 'role', 'label' => 'Role', 'type' => 'badge', 'map' => User::ROLES],
            ['key' => 'designation', 'label' => 'Designation'],
            ['key' => 'last_login_at', 'label' => 'Last sign-in', 'type' => 'datetime'],
            ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean', 'on' => 'Active', 'off' => 'Disabled'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::field('name', 'Full name', 'text', ['rules' => 'required|string|max:190', 'col' => 6]),
            self::field('email', 'Email address', 'email', [
                'col' => 6,
                'rules' => fn (?User $r) => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($r?->id)],
            ]),
            self::field('password', 'Password', 'password', [
                'col' => 6,
                'rules' => fn (?User $r) => $r ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
                'hint' => 'Leave blank to keep the current password. Minimum 8 characters.',
            ]),
            self::field('role', 'Role', 'select', ['col' => 6, 'options' => User::ROLES, 'default' => 'admin', 'rules' => 'required|in:super_admin,admin,editor']),
            self::field('designation', 'Designation', 'text', ['col' => 6]),
            self::field('avatar', 'Profile photo', 'image', ['col' => 6]),
            self::field('is_active', 'Account active', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }

    protected function beforeSave(array $data, Model $record, Request $request): array
    {
        // Never let an admin lock themselves out of their own account.
        if ($record->exists && $record->id === Auth::id()) {
            $data['is_active'] = true;
            $data['role'] = $record->role;
        }

        return $data;
    }

    protected function cannotDelete(Model $record): ?string
    {
        if ($record->id === Auth::id()) {
            return 'You cannot delete the account you are signed in with.';
        }

        if ($record->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return 'At least one super admin account must remain.';
        }

        return null;
    }
}

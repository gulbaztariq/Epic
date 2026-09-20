<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.epic');
        Paginator::defaultSimpleView('vendor.pagination.epic');

        // Site settings and dashboard accounts are limited to super admins and administrators.
        Gate::define('manage-system', fn ($user) => $user->canManageSystem());

        // Shared hosting often terminates TLS at the proxy; honour the scheme
        // configured in APP_URL so assets are not served over mixed content.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}

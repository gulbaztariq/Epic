<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First dashboard account
    |--------------------------------------------------------------------------
    |
    | Used once, by `php artisan epic:install`, to create the super admin. Set
    | these in .env before installing so the site never starts with the
    | published default password.
    |
    */

    'admin' => [
        'name' => env('EPIC_ADMIN_NAME', 'EPIC Super Admin'),
        'email' => env('EPIC_ADMIN_EMAIL', 'admin@epic.org.pk'),
        'password' => env('EPIC_ADMIN_PASSWORD', 'EpicAdmin@2025'),
    ],

];

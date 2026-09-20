<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') &middot; {{ setting('site_name', 'EPIC') }} Admin</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
    <link rel="stylesheet" href="{{ asset_v('css/admin.css') }}">
</head>
<body>
<div class="admin-shell">
    @include('admin.partials.sidebar')

    <div class="admin-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn btn-outline btn-icon sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">{!! icon('menu') !!}</button>
                <div>
                    <h1>@yield('heading', 'Dashboard')</h1>
                    <span class="crumb">@yield('crumb', 'EPIC content management')</span>
                </div>
            </div>

            <div class="topbar-right">
                <a class="btn btn-outline btn-sm" href="{{ route('home') }}" target="_blank" rel="noopener">{!! icon('eye') !!} View site</a>

                <a class="user-chip" href="{{ route('admin.profile') }}">
                    <span class="avatar">
                        @if (auth()->user()->avatar)
                            <img src="{{ uploaded_url(auth()->user()->avatar) }}" alt="">
                        @else
                            {{ auth()->user()->initials }}
                        @endif
                    </span>
                    <span class="who">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->role_label }}</span>
                    </span>
                </a>

                <form action="{{ route('admin.logout') }}" method="post">
                    @csrf
                    <button class="btn btn-outline btn-icon" type="submit" title="Sign out" aria-label="Sign out">{!! icon('logout') !!}</button>
                </form>
            </div>
        </header>

        <div class="admin-content">
            @include('admin.partials.flash')
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script src="{{ asset_v('js/admin.js') }}" defer></script>
@stack('scripts')
</body>
</html>

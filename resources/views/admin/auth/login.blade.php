<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in &middot; {{ setting('site_name', 'EPIC') }} Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset_v('css/admin.css') }}">
</head>
<body class="login-page">
    <div class="login-card">
        <img class="logo" src="{{ site_logo() }}" alt="{{ setting('site_name', 'EPIC') }}">
        <h1>Content management</h1>
        <p class="sub">Sign in to manage the EPIC website.</p>

        @include('admin.partials.flash')

        <form action="{{ route('admin.login.attempt') }}" method="post">
            @csrf

            <div class="field" style="margin-bottom:16px">
                <label for="email">Email address</label>
                <input class="control @error('email') is-invalid @enderror" id="email" type="email" name="email"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="field" style="margin-bottom:16px">
                <label for="password">Password</label>
                <input class="control @error('password') is-invalid @enderror" id="password" type="password"
                       name="password" required autocomplete="current-password">
            </div>

            <div class="switch" style="margin-bottom:18px">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Keep me signed in</label>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Sign in</button>
        </form>

        <p class="login-foot"><a href="{{ route('home') }}">&larr; Back to the website</a></p>
    </div>
</body>
</html>

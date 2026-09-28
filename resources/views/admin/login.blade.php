<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Administrator sign in | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <span class="gold-badge">ADMIN AREA</span>
            </div>
        </header>
        <main class="auth-shell">
            <p class="eyebrow"><span class="eyebrow-line"></span> RESTRICTED ACCESS</p>
            <section class="auth-panel" aria-labelledby="admin-login-title">
                <h1 id="admin-login-title">Administrator sign in.</h1>
                <p class="auth-copy">Admin accounts are created by the system owner or another administrator.</p>
                @if ($errors->any())
                    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('admin.login') }}" class="auth-form">
                    @csrf
                    <div class="field">
                        <label for="email">Admin email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="button button-amber" type="submit">Sign in to admin <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="auth-footnote"><a class="text-link" href="{{ route('login') }}">Return to user sign in</a></p>
            </section>
        </main>
    </body>
</html>
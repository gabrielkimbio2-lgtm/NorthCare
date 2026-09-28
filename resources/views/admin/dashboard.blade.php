<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin dashboard | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <span class="gold-badge">ADMIN AREA</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Sign out</button></form>
            </div>
        </header>
        <main class="auth-shell">
            <p class="eyebrow"><span class="eyebrow-line"></span> SYSTEM ADMINISTRATION</p>
            <section class="auth-panel" aria-labelledby="admin-title">
                <h1 id="admin-title">Admin dashboard.</h1>
                <p class="auth-copy">Review provider applications, manage location and category data, and maintain directory services.</p>
                <div class="dashboard-links">
                    <a class="button button-amber" href="{{ route('admin.applications.index') }}">Review applications <span aria-hidden="true">&#8594;</span></a>
                    <a class="button button-secondary" href="{{ route('admin.catalog.index') }}">Manage directory catalog</a>
                    <a class="button button-secondary" href="{{ route('admin.profiles.create') }}">Create provider profile</a>
                    <a class="text-link" href="{{ route('admin.languages.index') }}">Languages and translations</a>
                </div>
            </section>
            <section class="catalog-section">
                <h2>Create administrator</h2>
                @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
                <form class="form-grid" method="POST" action="{{ route('admin.administrators.store') }}">
                    @csrf
                    <div class="field"><label for="admin_name">Name</label><input id="admin_name" name="name" required></div>
                    <div class="field"><label for="admin_email">Email</label><input id="admin_email" name="email" type="email" required></div>
                    <div class="field"><label for="admin_password">Temporary password</label><input id="admin_password" name="password" type="password" minlength="12" required></div>
                    <div class="field"><label for="admin_password_confirmation">Confirm password</label><input id="admin_password_confirmation" name="password_confirmation" type="password" minlength="12" required></div>
                    <button class="button button-secondary" type="submit">Create administrator</button>
                </form>
            </section>
        </main>
    </body>
</html>
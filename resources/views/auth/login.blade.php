<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sign in | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Main navigation"><a class="nav-link" href="{{ route('directory.index') }}">Find care</a><a class="nav-link" href="{{ route('register') }}">Create account</a></nav>
            </div>
        </header>
        <main class="auth-shell">
            <p class="eyebrow"><span class="eyebrow-line"></span> YOUR NORTHCARE ACCOUNT</p>
            <section class="auth-panel" aria-labelledby="login-title">
                <h1 id="login-title">Welcome back.</h1>
                <p class="auth-copy">Sign in as a patient or healthcare provider.</p>
                @if ($errors->any())
                    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <label class="check-field"><input name="remember" type="checkbox" value="1"> Keep me signed in</label>
                    <button class="button button-amber" type="submit">Sign in <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="auth-footnote">New to NorthCare? <a class="text-link" href="{{ route('register') }}">Create an account</a></p>
                <p class="auth-footnote"><a class="subtle-link" href="{{ route('admin.login') }}">Administrator sign in</a></p>
            </section>
        </main>
    </body>
</html>
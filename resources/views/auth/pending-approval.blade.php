<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Profile awaiting approval | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Sign out</button></form>
            </div>
        </header>
        <main class="auth-shell">
            <p class="eyebrow"><span class="eyebrow-line"></span> APPLICATION STATUS</p>
            <section class="auth-panel approval-panel" aria-labelledby="approval-title">
                <span class="gold-badge">AWAITING ADMIN APPROVAL</span>
                <h1 id="approval-title">Your profile is awaiting approval.</h1>
                <p class="auth-copy">Hello {{ $user->name }}. We’ve received your {{ $user->role->value === 'institution' ? 'institution' : 'practitioner' }} application. An administrator must review and approve it before you can access profile management.</p>
                @if ($application)
                    <div class="status-panel status-pending">
                        <strong>Application submitted</strong>
                        <p>{{ $application->name }} · {{ $application->created_at->format('M j, Y') }}</p>
                        <p>Status: {{ ucfirst($application->status) }}</p>
                    </div>
                @endif
                <p class="field-hint">After approval, sign in again to continue directly to your profile.</p>
                <a class="text-link" href="{{ route('directory.index') }}">Browse the directory <span aria-hidden="true">&#8594;</span></a>
            </section>
        </main>
    </body>
</html>
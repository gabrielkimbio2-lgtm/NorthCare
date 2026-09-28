<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Applications | NorthCare Admin</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Admin navigation"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a><a class="nav-link nav-link-active" href="{{ route('admin.applications.index') }}">Applications</a></nav>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Sign out</button></form>
            </div>
        </header>
        <main class="page-shell">
            <p class="eyebrow"><span class="eyebrow-line"></span> ADMIN REVIEW</p>
            <div class="results-heading admin-heading">
                <div><h1>Provider applications</h1><p class="intro-copy">Review details and documents before a profile becomes visible.</p></div>
                <span class="gold-badge">{{ $applications->total() }} PENDING</span>
            </div>
            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            <div class="admin-list">
                @forelse ($applications as $application)
                    <article class="admin-list-row">
                        <div><span class="gold-badge">{{ strtoupper($application->application_type) }}</span><h2>{{ $application->name }}</h2><p>{{ $application->category?->parent?->name }} / {{ $application->category?->name }} · {{ $application->city?->name }}</p></div>
                        <div class="admin-list-meta"><span>{{ $application->applicant->email }}</span><span>{{ $application->created_at->format('M j, Y') }}</span></div>
                        <a class="button button-amber" href="{{ route('admin.applications.edit', $application) }}">Review <span aria-hidden="true">&#8594;</span></a>
                    </article>
                @empty
                    <div class="empty-state"><h2>No applications waiting</h2><p>New practitioner and institution applications will appear here.</p></div>
                @endforelse
            </div>
            {{ $applications->links() }}
        </main>
    </body>
</html>
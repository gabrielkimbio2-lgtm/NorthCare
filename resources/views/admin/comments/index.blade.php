<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Moderate comments | NorthCare Admin</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">A</span><span>northcare admin<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Admin navigation">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Overview</a>
                    <a class="nav-link" href="{{ route('admin.applications.index') }}">Applications</a>
                    <a class="nav-link" href="{{ route('admin.catalog.index') }}">Catalog</a>
                    <a class="nav-link" href="{{ route('admin.languages.index') }}">Languages</a>
                    <a class="nav-link nav-link-active" href="{{ route('admin.comments.index') }}">Comments</a>
                </nav>
            </div>
        </header>

        <main class="page-shell">
            <p class="eyebrow"><span class="eyebrow-line"></span> MODERATION QUEUE</p>
            <h1>Patient comments & reports.</h1>

            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif

            @if ($reportedComments->isNotEmpty())
                <section class="catalog-section" style="border-color: #d2705a;">
                    <h2 style="color: #e3826c;">⚠️ Reported comments ({{ $reportedComments->count() }})</h2>
                    <p class="section-intro">Doctors have reported these comments and indicated problems. Review the complaint and take down the comment if necessary.</p>

                    <div class="admin-list">
                        @foreach ($reportedComments as $comment)
                            <div class="admin-list-row" style="background: rgb(210 112 90 / 6%); padding: 18px 12px;">
                                <div>
                                    <span class="gold-badge" style="border-color: #d2705a; color: #e3826c;">REPORTED BY DOCTOR</span>
                                    <h2>Dr. {{ $comment->practitionerProfile?->name }}</h2>
                                    <p><strong>Patient comment:</strong> &ldquo;{{ $comment->comment }}&rdquo;</p>
                                    <div style="display: flex; gap: 12px; margin-top: 4px;">
                                        @if ($comment->doctor_rating)<p><strong>Doctor:</strong> {{ $comment->doctor_rating }} / 5 ★</p>@endif
                                        @if ($comment->service_rating)<p><strong>Service:</strong> {{ $comment->service_rating }} / 5 ★</p>@endif
                                    </div>
                                    <div class="status-panel status-rejected" style="margin-top: 8px;">
                                        <strong>Doctor's reported problem:</strong>
                                        <p>{{ $comment->report_reason }}</p>
                                        <p style="font-size: 10px; color: var(--muted); margin-top: 4px;">Reported on {{ $comment->reported_at?->format('M j, Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="admin-list-meta">
                                    <span>Author: {{ $comment->user?->name }}</span>
                                    <span>Service: {{ $comment->appointment?->service?->name }}</span>
                                    <span>Current status: {{ strtoupper($comment->status) }}</span>
                                </div>
                                <div class="form-actions" style="flex-direction: column; align-items: stretch; gap: 8px;">
                                    <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="take_down">
                                        <button class="button button-amber" style="background: #d2705a; border-color: #d2705a; color: #fff;" type="submit">
                                            Take down comment
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="dismiss_report">
                                        <button class="button button-secondary" type="submit">
                                            Dismiss report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="catalog-section">
                <h2>Pending comments awaiting review ({{ $pendingComments->count() }})</h2>
                <p class="section-intro">Patients have submitted these comments after completed meetings. Review them to accept or reject before they are published.</p>

                @if ($pendingComments->isEmpty())
                    <p class="field-hint">No new comments currently awaiting admin review.</p>
                @else
                    <div class="admin-list">
                        @foreach ($pendingComments as $comment)
                            <div class="admin-list-row">
                                <div>
                                    <span class="gold-badge">PENDING REVIEW</span>
                                    <h2>For: {{ $comment->practitionerProfile?->name }}</h2>
                                    <p><strong>Comment:</strong> &ldquo;{{ $comment->comment }}&rdquo;</p>
                                    <div style="display: flex; gap: 12px; margin-top: 4px;">
                                        @if ($comment->doctor_rating)<p><strong>Doctor:</strong> {{ $comment->doctor_rating }} / 5 ★</p>@endif
                                        @if ($comment->service_rating)<p><strong>Service:</strong> {{ $comment->service_rating }} / 5 ★</p>@endif
                                    </div>
                                </div>
                                <div class="admin-list-meta">
                                    <span>By: {{ $comment->user?->name }} ({{ $comment->user?->email }})</span>
                                    <span>Meeting: {{ $comment->appointment?->appointment_date?->format('M j, Y') }} · {{ $comment->appointment?->service?->name }}</span>
                                    <span>Submitted: {{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="form-actions">
                                    <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="approve">
                                        <button class="button button-amber" type="submit">Accept & publish</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="reject">
                                        <button class="button button-secondary" type="submit">Reject</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="catalog-section">
                <h2>Published & active comments ({{ $approvedComments->count() }})</h2>
                @if ($approvedComments->isEmpty())
                    <p class="field-hint">No published comments.</p>
                @else
                    <div class="admin-list">
                        @foreach ($approvedComments as $comment)
                            <div class="admin-list-row">
                                <div>
                                    <span class="gold-badge" style="color: var(--mint); border-color: var(--mint);">PUBLISHED</span>
                                    <h2>For: {{ $comment->practitionerProfile?->name }}</h2>
                                    <p>&ldquo;{{ $comment->comment }}&rdquo;</p>
                                    <div style="display: flex; gap: 12px; margin-top: 4px;">
                                        @if ($comment->doctor_rating)<p><strong>Doctor:</strong> {{ $comment->doctor_rating }} / 5 ★</p>@endif
                                        @if ($comment->service_rating)<p><strong>Service:</strong> {{ $comment->service_rating }} / 5 ★</p>@endif
                                    </div>
                                </div>
                                <div class="admin-list-meta">
                                    <span>Author: {{ $comment->user?->name }}</span>
                                    <span>Reviewed: {{ $comment->reviewed_at?->format('M j, Y') }}</span>
                                </div>
                                <div>
                                    <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="take_down">
                                        <button class="text-button text-button-danger" type="submit">Take down</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </body>
</html>

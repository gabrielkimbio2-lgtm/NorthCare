<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Appointments & Schedule | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Main navigation">
                    <a class="nav-link" href="{{ route('practitioner.profile.edit') }}">Profile & Availability</a>
                    <a class="nav-link nav-link-active" href="{{ route('practitioner.appointments.index') }}">Appointments</a>
                    <a class="nav-link" href="{{ route('dashboard') }}">Account</a>
                </nav>
            </div>
        </header>

        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> PRACTITIONER SCHEDULE & REQUESTS</p>
            <h1>Appointments & Patient Comments</h1>

            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif

            <section class="catalog-section">
                <h2>Pending appointment requests ({{ $pendingAppointments->count() }})</h2>
                <p class="section-intro">Review patient requests. Once approved, the timeslot is closed on both your schedule and the patient schedule.</p>

                @if ($pendingAppointments->isEmpty())
                    <p class="field-hint">No pending appointment requests awaiting approval.</p>
                @else
                    <div class="admin-list">
                        @foreach ($pendingAppointments as $appointment)
                            <div class="admin-list-row">
                                <div>
                                    <span class="gold-badge">PENDING APPROVAL</span>
                                    <h2>{{ $appointment->user?->name }}</h2>
                                    <p><strong>Service:</strong> {{ $appointment->service?->name }} ({{ $appointment->service?->pivot->duration_minutes ?? 30 }} min)</p>
                                    <p><strong>Scheduled:</strong> {{ $appointment->appointment_date->format('l, M j, Y') }} at {{ mb_substr($appointment->starts_at, 0, 5) }} &ndash; {{ mb_substr($appointment->ends_at, 0, 5) }}</p>
                                    @if ($appointment->patient_notes)
                                        <p><strong>Patient note:</strong> &ldquo;{{ $appointment->patient_notes }}&rdquo;</p>
                                    @endif
                                </div>
                                <div class="admin-list-meta">
                                    <span>Requested: {{ $appointment->created_at->diffForHumans() }}</span>
                                    <span>Contact: {{ $appointment->user?->email }}</span>
                                </div>
                                <div class="form-actions">
                                    <form method="POST" action="{{ route('practitioner.appointments.approve', $appointment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="button button-amber" type="submit">Approve patient</button>
                                    </form>
                                    <form method="POST" action="{{ route('practitioner.appointments.reject', $appointment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="button button-secondary" type="submit">Reject</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="catalog-section">
                <h2>Confirmed upcoming appointments ({{ $approvedAppointments->count() }})</h2>
                <p class="section-intro">Confirmed patient bookings. These timeslots are closed for other bookings.</p>

                @if ($approvedAppointments->isEmpty())
                    <p class="field-hint">No upcoming confirmed appointments scheduled.</p>
                @else
                    <div class="admin-list">
                        @foreach ($approvedAppointments as $appointment)
                            <div class="admin-list-row">
                                <div>
                                    <span class="gold-badge" style="background: rgb(155 198 173 / 15%); color: var(--mint); border-color: var(--mint);">CONFIRMED</span>
                                    <h2>{{ $appointment->user?->name }}</h2>
                                    <p><strong>Service:</strong> {{ $appointment->service?->name }}</p>
                                    <p><strong>Time:</strong> {{ $appointment->appointment_date->format('l, M j, Y') }} ({{ mb_substr($appointment->starts_at, 0, 5) }} &ndash; {{ mb_substr($appointment->ends_at, 0, 5) }})</p>
                                    @if ($appointment->patient_notes)<p><strong>Notes:</strong> {{ $appointment->patient_notes }}</p>@endif
                                </div>
                                <div class="admin-list-meta">
                                    <span>Patient: {{ $appointment->user?->email }}</span>
                                </div>
                                <div>
                                    <form method="POST" action="{{ route('appointments.cancel', $appointment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-button text-button-danger" type="submit">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="catalog-section">
                <h2>Patient comments & reviews ({{ $receivedComments->count() }})</h2>
                <p class="section-intro">Feedback left by patients after scheduled meetings. Approved comments are displayed publicly. You can report problematic reviews to administrators for review and take-down.</p>

                @if ($receivedComments->isEmpty())
                    <p class="field-hint">No reviews or comments received yet.</p>
                @else
                    <div class="admin-list">
                        @foreach ($receivedComments as $comment)
                            <div class="comment-card" style="padding: 16px 0; border-bottom: 1px solid var(--line);">
                                <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                                    <div>
                                        <strong>{{ $comment->user?->name ?? 'Patient' }}</strong>
                                        <span class="override-meta">· {{ $comment->appointment?->service?->name }} ({{ $comment->appointment?->appointment_date?->format('M j, Y') }})</span>
                                        <div style="display: flex; gap: 8px; margin-top: 4px;">
                                            @if ($comment->doctor_rating)<span class="gold-badge" style="margin-left: 0;">Doctor: {{ $comment->doctor_rating }}/5 ★</span>@endif
                                            @if ($comment->service_rating)<span class="gold-badge">Service: {{ $comment->service_rating }}/5 ★</span>@endif
                                        </div>
                                        <p style="margin: 8px 0; color: var(--text);">&ldquo;{{ $comment->comment }}&rdquo;</p>
                                    </div>
                                    <div>
                                        @if ($comment->is_reported)
                                            <span class="gold-badge" style="border-color: #d2705a; color: #e3826c;">REPORTED TO ADMIN</span>
                                        @elseif ($comment->status === 'approved')
                                            <span class="gold-badge" style="border-color: var(--mint); color: var(--mint);">PUBLISHED</span>
                                        @elseif ($comment->status === 'pending')
                                            <span class="gold-badge">UNDER ADMIN REVIEW</span>
                                        @else
                                            <span class="gold-badge" style="color: var(--muted);">{{ strtoupper($comment->status) }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($comment->is_reported)
                                    <div class="status-panel status-rejected" style="margin-top: 10px; font-size: 11px;">
                                        <strong>Report submitted to admin:</strong> {{ $comment->report_reason }}
                                        <p style="margin-top: 3px;">Reported on {{ $comment->reported_at?->format('M j, Y H:i') }}. Awaiting admin moderation.</p>
                                    </div>
                                @elseif ($comment->status === 'approved')
                                    <details style="margin-top: 10px;">
                                        <summary class="text-button" style="cursor: pointer; font-size: 11px; color: var(--muted);">Report problem with this review</summary>
                                        <form method="POST" action="{{ route('practitioner.comments.report', $comment) }}" style="margin-top: 8px; display: grid; gap: 8px; max-width: 500px;">
                                            @csrf
                                            <label for="reason_{{ $comment->id }}" class="visually-hidden">Report reason</label>
                                            <textarea id="reason_{{ $comment->id }}" name="report_reason" rows="2" placeholder="Explain the problem with this review (e.g. false claims, inappropriate language)..." required></textarea>
                                            <button class="button button-secondary" type="submit" style="width: fit-content;">Submit report to admin</button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </body>
</html>

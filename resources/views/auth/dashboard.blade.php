<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Your account | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Main navigation">
                    <a class="nav-link" href="{{ route('directory.index') }}">Directory</a>
                    <a class="nav-link nav-link-active" href="{{ route('dashboard') }}">Account</a>
                </nav>
                <div class="header-actions">
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Sign out</button></form>
                </div>
            </div>
        </header>

        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> ACCOUNT OVERVIEW</p>
            <section class="auth-panel" aria-labelledby="account-title">
                <p class="gold-badge">{{ strtoupper($user->role->value) }}</p>
                <h1 id="account-title">Hello, {{ $user->name }}.</h1>

                @if (session('status'))
                    <div class="status-panel">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                @endif

                @if ($user->account_status === 'pending')
                    <div class="status-panel status-pending">
                        <strong>Application under review</strong>
                        <p>Your provider profile will not appear in the directory until an administrator approves it.</p>
                        @if ($application)
                            <p>Submitted: {{ $application->created_at->format('M j, Y') }} · Status: {{ ucfirst($application->status) }}</p>
                        @endif
                    </div>
                @elseif ($user->account_status === 'rejected')
                    <div class="status-panel status-rejected"><strong>Application not approved</strong><p>Please contact the NorthCare team for details.</p></div>
                @else
                    <p class="auth-copy">Your account is active. Manage your healthcare bookings and provider profiles below.</p>
                @endif

                @if ($user->role->value === 'practitioner' && $user->account_status === 'active' && $user->practitionerProfile()->exists())
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 14px;">
                        <a class="button button-amber" href="{{ route('practitioner.profile.edit') }}">Manage practitioner profile</a>
                        <a class="button button-secondary" href="{{ route('practitioner.appointments.index') }}">Appointments & Schedule</a>
                    </div>
                @endif
                @if ($user->role->value === 'institution' && $user->account_status === 'active' && $user->institution()->exists())
                    <a class="button button-amber" href="{{ route('institution.profile.edit') }}">Manage institution profile</a>
                @endif

                <section class="catalog-section" style="margin-top: 28px;">
                    <h2>Your Booked Appointments</h2>
                    @if (empty($appointments) || $appointments->isEmpty())
                        <p class="field-hint">You have not booked any appointments yet.</p>
                        <div style="margin-top: 10px;">
                            <a class="button button-amber" href="{{ route('directory.index') }}">Find a doctor to book &rarr;</a>
                        </div>
                    @else
                        <div class="admin-list">
                            @foreach ($appointments as $appointment)
                                <div class="admin-list-row" style="padding: 16px 0;">
                                    <div>
                                        @if ($appointment->status === 'approved')
                                            <span class="gold-badge" style="border-color: var(--mint); color: var(--mint);">CONFIRMED</span>
                                        @elseif ($appointment->status === 'pending')
                                            <span class="gold-badge">PENDING DOCTOR APPROVAL</span>
                                        @elseif ($appointment->status === 'cancelled')
                                            <span class="gold-badge" style="color: var(--muted); border-color: var(--line);">CANCELLED</span>
                                        @elseif ($appointment->status === 'rejected')
                                            <span class="gold-badge" style="border-color: #d2705a; color: #e3826c;">REJECTED</span>
                                        @else
                                            <span class="gold-badge">{{ strtoupper($appointment->status) }}</span>
                                        @endif

                                        <h2 style="margin-top: 6px;">{{ $appointment->practitionerProfile?->name }}</h2>
                                        <p><strong>Service:</strong> {{ $appointment->service?->name }} ({{ $appointment->service?->pivot->duration_minutes ?? 30 }} min)</p>
                                        <p><strong>Time:</strong> {{ $appointment->appointment_date->format('l, M j, Y') }} at {{ mb_substr($appointment->starts_at, 0, 5) }} &ndash; {{ mb_substr($appointment->ends_at, 0, 5) }}</p>

                                        @if ($appointment->canBeCommentedBy($user))
                                            <details style="margin-top: 12px; background: var(--panel); border: 1px solid var(--line); padding: 12px;">
                                                <summary class="text-button" style="cursor: pointer; color: var(--gold); font-weight: 600;">
                                                    Drop a comment / review for Dr. {{ $appointment->practitionerProfile?->name }} &darr;
                                                </summary>
                                                <form method="POST" action="{{ route('appointments.comments.store', $appointment) }}" style="margin-top: 10px; display: grid; gap: 10px;">
                                                    @csrf
                                                    <div class="field">
                                                        <label for="comment_{{ $appointment->id }}">Your comment / feedback</label>
                                                        <textarea id="comment_{{ $appointment->id }}" name="comment" rows="3" placeholder="Share your experience with this doctor..." required minlength="5"></textarea>
                                                    </div>
                                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                                        <div class="field">
                                                            <label for="doctor_rating_{{ $appointment->id }}">Doctor Rating</label>
                                                            <select id="doctor_rating_{{ $appointment->id }}" name="doctor_rating">
                                                                <option value="5">5 ★ - Excellent</option>
                                                                <option value="4">4 ★ - Very good</option>
                                                                <option value="3">3 ★ - Good</option>
                                                                <option value="2">2 ★ - Fair</option>
                                                                <option value="1">1 ★ - Poor</option>
                                                            </select>
                                                        </div>
                                                        <div class="field">
                                                            <label for="service_rating_{{ $appointment->id }}">Service Rating</label>
                                                            <select id="service_rating_{{ $appointment->id }}" name="service_rating">
                                                                <option value="5">5 ★ - Excellent</option>
                                                                <option value="4">4 ★ - Very good</option>
                                                                <option value="3">3 ★ - Good</option>
                                                                <option value="2">2 ★ - Fair</option>
                                                                <option value="1">1 ★ - Poor</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <button class="button button-amber" type="submit" style="width: fit-content;">Send comment for admin review</button>
                                                </form>
                                            </details>
                                        @elseif ($appointment->comment)
                                            <div style="margin-top: 10px; padding: 10px 12px; border: 1px solid var(--line); background: var(--panel);">
                                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                                    <strong>Your review:</strong>
                                                    @if ($appointment->comment->status === 'approved')
                                                        <span class="gold-badge" style="color: var(--mint); border-color: var(--mint);">APPROVED & PUBLISHED</span>
                                                    @elseif ($appointment->comment->status === 'pending')
                                                        <span class="gold-badge">SENT TO ADMIN FOR REVIEW</span>
                                                    @else
                                                        <span class="gold-badge" style="color: var(--muted);">{{ strtoupper($appointment->comment->status) }}</span>
                                                    @endif
                                                </div>
                                                <p style="margin: 6px 0 0; color: var(--muted);">&ldquo;{{ $appointment->comment->comment }}&rdquo;</p>
                                                <div style="margin-top: 6px; display: flex; gap: 12px; font-size: 12px; color: var(--muted);">
                                                    @if($appointment->comment->doctor_rating)
                                                        <span>Doctor: {{ $appointment->comment->doctor_rating }}/5 ★</span>
                                                    @endif
                                                    @if($appointment->comment->service_rating)
                                                        <span>Service: {{ $appointment->comment->service_rating }}/5 ★</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="admin-list-meta">
                                        <span>Status: {{ ucfirst($appointment->status) }}</span>
                                        @if (in_array($appointment->status, ['pending', 'approved'], true))
                                            <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" style="margin-top: 6px;">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-button text-button-danger" type="submit">Cancel booking</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <div style="margin-top: 24px;">
                    <a class="text-link" href="{{ route('directory.index') }}">Return to the directory <span aria-hidden="true">&#8594;</span></a>
                </div>
            </section>
        </main>
    </body>
</html>
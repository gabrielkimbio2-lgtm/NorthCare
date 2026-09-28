<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book appointment with {{ $practitioner->name }} | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Main navigation">
                    <a class="nav-link" href="{{ route('directory.index') }}">Directory</a>
                    <a class="nav-link" href="{{ route('dashboard') }}">My account</a>
                </nav>
            </div>
        </header>

        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> BOOK AN APPOINTMENT</p>
            <h1>Schedule with {{ $practitioner->name }}.</h1>

            <div class="provider-booking-summary">
                <span class="gold-badge">{{ $practitioner->category?->name ?? 'Healthcare Provider' }}</span>
                <span>{{ $practitioner->city?->name }}@if ($practitioner->region) · {{ $practitioner->region->name }}@endif</span>
                @if ($practitioner->address)<span>· {{ $practitioner->address }}</span>@endif
            </div>

            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif

            @if ($noServices ?? false)
                <div class="status-panel status-pending">
                    <strong>No services available</strong>
                    <p>This practitioner has not added any services yet. Please contact them or check back later.</p>
                </div>
                <div style="margin-top: 20px;">
                    <a class="button button-secondary" href="{{ route('directory.index') }}">Return to directory</a>
                </div>
            @else
            <form class="booking-flow-form" method="POST" action="{{ route('appointments.store', $practitioner) }}">
                @csrf

                <section class="catalog-section">
                    <h2>1. Choose a service</h2>
                    <p class="section-intro">Select the service you are booking. Each service has a dedicated appointment duration.</p>
                    <div class="service-selection-grid">
                        @foreach ($services as $service)
                            @php($duration = $service->pivot->duration_minutes ?? 30)
                            <label class="service-card @if($selectedService?->id === $service->id) service-card-selected @endif">
                                <div class="service-radio-label">
                                    <input type="radio" name="service_id" value="{{ $service->id }}"
                                        @checked($selectedService?->id === $service->id)
                                        onchange="window.location.href='{{ route('appointments.create', ['practitioner' => $practitioner, 'service_id' => $service->id, 'date' => $selectedDate->toDateString()]) }}'">
                                    <strong>{{ $service->name }}</strong>
                                </div>
                                <span class="gold-badge">{{ $duration }} MIN SLOT</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="catalog-section">
                    <h2>2. Pick appointment date</h2>
                    <p class="section-intro">Choose an available date. Working days are highlighted.</p>
                    <div class="date-strip-picker">
                        @foreach ($daysPreview as $day)
                            <a class="date-pill @if($day['is_selected']) date-pill-selected @endif @if(!$day['is_working']) date-pill-off @endif"
                               href="{{ route('appointments.create', ['practitioner' => $practitioner, 'service_id' => $selectedService?->id, 'date' => $day['date']]) }}">
                                <span class="date-pill-day">{{ $day['day_name'] }}</span>
                                <span class="date-pill-num">{{ $day['day_num'] }}</span>
                                <span class="date-pill-month">{{ $day['month'] }}</span>
                                @if (!$day['is_working'])<span class="date-pill-status">Off</span>@endif
                            </a>
                        @endforeach
                    </div>
                    <div class="field" style="margin-top: 14px; max-width: 240px;">
                        <label for="custom_date">Or pick specific date:</label>
                        <input id="custom_date" type="date" name="appointment_date"
                            value="{{ $selectedDate->toDateString() }}"
                            min="{{ today()->toDateString() }}"
                            onchange="window.location.href='{{ route('appointments.create', ['practitioner' => $practitioner, 'service_id' => $selectedService?->id]) }}&date=' + this.value">
                    </div>
                </section>

                <section class="catalog-section">
                    <h2>3. Select time slot</h2>
                    <p class="section-intro">Showing available vacancy slots for {{ $selectedDate->format('l, F j, Y') }} ({{ $slotDuration }} min):</p>

                    @if ($isOnVacation)
                        <div class="status-panel status-rejected">
                            <strong>Doctor is away / on vacation on this date</strong>
                            <p>Please select another day to view available vacancy slots.</p>
                        </div>
                    @elseif (empty($availableSlots))
                        <div class="status-panel status-pending">
                            <strong>No vacancies available on this day</strong>
                            <p>The practitioner has no open slots on this date or all slots are closed. Please select another date from the calendar above.</p>
                        </div>
                    @else
                        <div class="slots-selection-grid">
                            @foreach ($availableSlots as $slot)
                                <label class="slot-radio-card">
                                    <input type="radio" name="starts_at" value="{{ $slot['start'] }}" required>
                                    <span class="slot-time">{{ $slot['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="catalog-section">
                    <h2>4. Notes for the practitioner (optional)</h2>
                    <div class="field field-full">
                        <label for="patient_notes">Symptoms or reason for appointment</label>
                        <textarea id="patient_notes" name="patient_notes" rows="3" placeholder="Briefly describe what you'd like to consult on..."></textarea>
                    </div>

                    <div style="margin-top: 20px;">
                        <button class="button button-amber" type="submit" @if(empty($availableSlots)) disabled @endif>
                            Request appointment &rarr;
                        </button>
                    </div>
                    <p class="auth-footnote">Upon request, the doctor will review and approve your appointment. Once approved, the timeslot will be closed on both schedules.</p>
                </section>
            </form>
            @endif
        </main>
    </body>
</html>

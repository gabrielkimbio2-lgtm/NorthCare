<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Practitioner profile | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <a class="nav-link" href="{{ route('dashboard') }}">Account</a>
            </div>
        </header>
        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> PRACTITIONER PROFILE</p>
            <h1>Manage your profile & availability.</h1>
            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif

            <section class="catalog-section">
                <h2>Profile details and visibility</h2>
                <form class="auth-form" method="POST" action="{{ route('practitioner.profile.update') }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-grid">
                        <div class="field field-full"><label for="name">Name</label><input id="name" name="name" value="{{ old('name', $profile->name) }}" required></div>
                        <div class="field"><label for="contact_email">Contact email</label><input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $profile->contact_email) }}"></div>
                        <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $profile->phone) }}"></div>
                        <div class="field field-full"><label for="address">Address</label><input id="address" name="address" value="{{ old('address', $profile->address) }}"></div>
                        <div class="field field-full"><label for="bio">Description</label><textarea id="bio" name="bio" rows="4">{{ old('bio', $profile->bio) }}</textarea></div>
                    </div>
                    <fieldset class="visibility-fields"><legend>Who can see each field?</legend>
                        @foreach (['name' => 'Name', 'contact_email' => 'Email', 'phone' => 'Phone', 'address' => 'Address', 'bio' => 'Description'] as $field => $label)
                            @php($visibility = old('visibility.'.$field, $profile->visibility[$field] ?? ($field === 'name' || $field === 'bio' ? 'public' : 'registered')))
                            <div class="visibility-row">
                                <label for="visibility_{{ $field }}">{{ $label }}</label>
                                <select id="visibility_{{ $field }}" name="visibility[{{ $field }}]">
                                    <option value="public" @selected($visibility === 'public')>Public</option>
                                    <option value="registered" @selected($visibility === 'registered')>Registered users</option>
                                    <option value="private" @selected($visibility === 'private')>Private</option>
                                </select>
                            </div>
                        @endforeach
                    </fieldset>
                    <button class="button button-amber" type="submit">Save profile</button>
                </form>
            </section>

            <section class="catalog-section">
                <h2>Approved services & slot durations</h2>
                <p class="section-intro">Select the services you provide and assign an appointment slot duration (in 15-minute intervals) for booking vacancies.</p>
                <form class="service-selection-form" method="POST" action="{{ route('practitioner.profile.services.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="service-selection-grid">
                        @foreach ($services as $service)
                            @php($attached = $profile->services->find($service->id))
                            @php($currentDuration = $attached?->pivot->duration_minutes ?? 30)
                            <div class="service-card">
                                <label class="service-checkbox-label">
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" @checked($attached !== null)>
                                    <span class="service-name">{{ $service->name }}</span>
                                </label>
                                <div class="service-duration-field">
                                    <label for="duration_{{ $service->id }}">Slot duration</label>
                                    <select id="duration_{{ $service->id }}" name="service_durations[{{ $service->id }}]">
                                        @foreach ([15, 30, 45, 60, 75, 90, 105, 120, 150, 180, 240] as $mins)
                                            <option value="{{ $mins }}" @selected((int) old('service_durations.'.$service->id, $currentDuration) === $mins)>
                                                {{ $mins }} min
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($services->isEmpty())
                        <p class="field-hint">There are no approved services yet.</p>
                    @else
                        <button class="button button-secondary" type="submit">Save services & durations</button>
                    @endif
                </form>
            </section>

            @php($timeOptions = [])
            @for ($hour = 6; $hour <= 22; $hour++)
                @foreach (['00', '15', '30', '45'] as $min)
                    @php($timeOptions[] = sprintf('%02d:%s', $hour, $min))
                @endforeach
            @endfor

            <section class="catalog-section">
                <h2>Weekly working schedule (Vacancies)</h2>
                <p class="section-intro">Pick your working days and working hours in 15-minute increments. The system uses these to list your availability for booking.</p>
                <form class="availability-form" method="POST" action="{{ route('practitioner.profile.availability.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="schedule-grid">
                        @foreach ([
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            7 => 'Sunday',
                        ] as $dayNumber => $dayName)
                            @php($existing = $windowsByDay->get($dayNumber))
                            @php($enabled = old('windows.'.$dayNumber.'.enabled', $existing?->is_active ?? in_array($dayNumber, [1, 2, 3, 4, 5], true)))
                            @php($startTime = old('windows.'.$dayNumber.'.starts_at', $existing ? mb_substr($existing->starts_at, 0, 5) : '09:00'))
                            @php($endTime = old('windows.'.$dayNumber.'.ends_at', $existing ? mb_substr($existing->ends_at, 0, 5) : '17:00'))

                            <div class="schedule-row">
                                <input type="hidden" name="windows[{{ $dayNumber }}][day_of_week]" value="{{ $dayNumber }}">
                                <label class="schedule-day-toggle">
                                    <input type="checkbox" name="windows[{{ $dayNumber }}][enabled]" value="1" @checked($enabled)>
                                    <strong>{{ $dayName }}</strong>
                                </label>
                                <div class="schedule-times">
                                    <div class="time-field">
                                        <label for="starts_at_{{ $dayNumber }}">Starts at</label>
                                        <select id="starts_at_{{ $dayNumber }}" name="windows[{{ $dayNumber }}][starts_at]">
                                            @foreach ($timeOptions as $t)
                                                <option value="{{ $t }}" @selected($startTime === $t)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span class="time-sep" aria-hidden="true">&ndash;</span>
                                    <div class="time-field">
                                        <label for="ends_at_{{ $dayNumber }}">Ends at</label>
                                        <select id="ends_at_{{ $dayNumber }}" name="windows[{{ $dayNumber }}][ends_at]">
                                            @foreach ($timeOptions as $t)
                                                <option value="{{ $t }}" @selected($endTime === $t)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="button button-amber" type="submit">Save weekly schedule</button>
                </form>
            </section>

            <section class="catalog-section">
                <h2>Vacations & schedule alterations</h2>
                <p class="section-intro">Taking a vacation or attending a seminar? Alter your schedule manually by marking specific days or timeslots as unavailable.</p>
                <div class="catalog-forms">
                    <form class="catalog-form" method="POST" action="{{ route('practitioner.profile.unavailable-times.store') }}">
                        @csrf
                        <h3>Schedule time-off or vacation</h3>
                        <div class="field">
                            <label for="vacation_date">Date (or vacation start)</label>
                            <input id="vacation_date" name="date" type="date" min="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="vacation_end_date">End date (optional, for multi-day vacation)</label>
                            <input id="vacation_end_date" name="end_date" type="date" min="{{ today()->toDateString() }}">
                            <span class="field-hint">Leave blank for single-day absence.</span>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="vacation_starts_at">Starts at (optional)</label>
                                <select id="vacation_starts_at" name="starts_at">
                                    <option value="">Full day off</option>
                                    @foreach ($timeOptions as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="vacation_ends_at">Ends at (optional)</label>
                                <select id="vacation_ends_at" name="ends_at">
                                    <option value="">Full day off</option>
                                    @foreach ($timeOptions as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label for="vacation_reason">Reason / note</label>
                            <input id="vacation_reason" name="reason" placeholder="e.g. Annual leave, Holiday, Conference">
                        </div>
                        <button class="button button-amber" type="submit">Mark as unavailable</button>
                    </form>

                    <div class="catalog-form">
                        <h3>Upcoming vacations & unavailable times</h3>
                        @if ($profile->availabilityOverrides->isEmpty())
                            <p class="field-hint">No vacations or blocked timeslots scheduled.</p>
                        @else
                            <div class="override-list">
                                @foreach ($profile->availabilityOverrides as $override)
                                    <div class="override-item">
                                        <div>
                                            <strong>{{ \Illuminate\Support\Carbon::parse($override->date)->format('M d, Y (D)') }}</strong>
                                            <p class="override-meta">
                                                @if ($override->starts_at && $override->ends_at)
                                                    {{ mb_substr($override->starts_at, 0, 5) }} &ndash; {{ mb_substr($override->ends_at, 0, 5) }}
                                                @else
                                                    <span class="gold-badge">VACATION / FULL DAY</span>
                                                @endif
                                                @if ($override->reason) · {{ $override->reason }} @endif
                                            </p>
                                        </div>
                                        <form method="POST" action="{{ route('practitioner.profile.unavailable-times.destroy', $override) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-button text-button-danger" type="submit">Remove</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="catalog-section">
                <h2>Upcoming booking vacancies preview</h2>
                <p class="section-intro">Preview of generated booking vacancy slots for the next 7 days based on your working schedule and vacations (with {{ $previewDuration }}-minute service duration):</p>
                <div class="vacancy-preview-grid">
                    @foreach ($upcomingVacancyDays as $day)
                        <div class="vacancy-day-card @if($day['is_today']) vacancy-day-today @endif">
                            <div class="vacancy-day-header">
                                <strong>{{ $day['formatted_date'] }}</strong>
                                @if ($day['is_today'])<span class="gold-badge">TODAY</span>@endif
                            </div>
                            @if ($day['is_vacation'])
                                <div class="vacation-notice">
                                    <span class="status-dot status-dot-away"></span>
                                    <span>On Vacation</span>
                                </div>
                            @elseif (empty($day['slots']))
                                <p class="vacancy-empty">Not working / no slots</p>
                            @else
                                <div class="vacancy-slot-list">
                                    @foreach ($day['slots'] as $slot)
                                        <span class="vacancy-slot-chip">{{ $slot['label'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="catalog-section">
                <h2>Suggest a service</h2>
                <form class="catalog-form" method="POST" action="{{ route('practitioner.service-suggestions.store') }}">
                    @csrf
                    <div class="field"><label for="suggestion_name">Service name</label><input id="suggestion_name" name="name" required></div>
                    <div class="field"><label for="suggestion_description">Description</label><textarea id="suggestion_description" name="description" rows="3"></textarea></div>
                    <button class="button button-amber" type="submit">Send suggestion</button>
                </form>
                @if ($suggestions->isNotEmpty())
                    <div class="catalog-list">@foreach ($suggestions as $suggestion)<div class="catalog-list-row"><strong>{{ $suggestion->name }}</strong><span class="gold-badge">{{ strtoupper($suggestion->status) }}</span></div>@endforeach</div>
                @endif
            </section>
        </main>
    </body>
</html>
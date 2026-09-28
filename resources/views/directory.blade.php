<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#121711">
        <title>NorthCare | Find a practitioner</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}" aria-label="NorthCare home">
                    <span class="brand-mark" aria-hidden="true">N</span>
                    <span>northcare<span class="brand-period">.</span></span>
                </a>
                <nav class="main-nav" aria-label="Main navigation">
                    <a class="nav-link nav-link-active" href="#directory">{{ __('navigation.find_care') }}</a>
                    <a class="nav-link" href="#for-practitioners">{{ __('navigation.for_practitioners') }}</a>
                </nav>
                <div class="header-actions">
                    @guest
                        <a class="nav-link" href="{{ route('login') }}">{{ __('navigation.sign_in') }}</a>
                        <a class="button button-amber header-button" href="{{ route('register') }}">{{ __('navigation.join') }}</a>
                    @else
                        <a class="nav-link" href="{{ route('dashboard') }}">Account</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Sign out</button></form>
                    @endguest
                    @if ($availableLanguages->count() > 1)
                        <form class="language-switch" method="POST" action="{{ route('language.switch') }}">
                            @csrf
                            <label class="visually-hidden" for="locale">Language</label>
                            <select id="locale" name="locale" aria-label="Language">
                                @foreach ($availableLanguages as $language)
                                    <option value="{{ $language->code }}" @selected($currentLocale === $language->code)>{{ $language->native_name }}</option>
                                @endforeach
                            </select>
                            <button class="text-button" type="submit">OK</button>
                        </form>
                    @endif
                    <a class="nav-link admin-link" href="{{ route('admin.login') }}">Admin</a>
                </div>
                <span class="location-note"><span class="status-dot"></span> Northern Cyprus</span>
            </div>
        </header>

        <main class="page-shell">
            <section class="intro" aria-labelledby="page-title">
                <p class="eyebrow"><span class="eyebrow-line"></span> {{ __('directory.eyebrow') }}</p>
                <div class="intro-row">
                    <div>
                        <h1 id="page-title">{{ __('directory.heading_first') }}<br><span>{{ __('directory.heading_second') }}</span></h1>
                        <p class="intro-copy">{{ __('directory.description') }}</p>
                    </div>
                    <p class="intro-index"><span>01</span> DIRECTORY<br>OF CARE</p>
                </div>
            </section>

            <section class="search-panel" id="directory" aria-label="Search practitioners">
                <form class="search-form" action="{{ route('directory.index') }}" method="GET">
                    <div class="field">
                        <label for="type">Care type</label>
                        <select id="type" name="type">
                            <option value="all" @selected($type === 'all')>All care</option>
                            <option value="doctor" @selected($type === 'doctor')>Doctors</option>
                            <option value="specialist" @selected($type === 'specialist')>Specialists</option>
                            <option value="hospital" @selected($type === 'hospital')>Hospitals</option>
                            <option value="institution" @selected($type === 'institution')>Institutions / Clinics</option>
                            <option value="practitioner" @selected($type === 'practitioner')>All Doctors & Specialists</option>
                        </select>
                    </div>
                    <div class="field field-search">
                        <label for="search">{{ __('directory.search_name') }}</label>
                        <input id="search" name="search" type="search" value="{{ is_string($search) ? $search : '' }}" placeholder="{{ __('directory.search_placeholder') }}">
                    </div>
                    <div class="field">
                        <label for="region">Region</label>
                        <select id="region" name="region">
                            <option value="">All regions</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption->id }}" @selected($region === (string) $regionOption->id)>{{ $regionOption->localized_name }} / {{ $regionOption->city_localized_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="city">{{ __('directory.city') }}</label>
                        <select id="city" name="city">
                            <option value="">{{ __('directory.all_cities') }}</option>
                            @foreach ($cities as $cityOption)
                                <option value="{{ $cityOption->id }}" @selected($city === (string) $cityOption->id)>{{ $cityOption->localized_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="specialty">{{ __('directory.specialty') }}</label>
                        <select id="specialty" name="specialty">
                            <option value="">{{ __('directory.all_specialties') }}</option>
                            @foreach ($specialties as $specialtyOption)
                                <option value="{{ $specialtyOption->id }}" @selected($specialty === (string) $specialtyOption->id)>{{ $specialtyOption->parent_localized_name }} / {{ $specialtyOption->localized_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="service">Service</label>
                        <select id="service" name="service">
                            <option value="">All services</option>
                            @foreach ($services as $serviceOption)
                                <option value="{{ $serviceOption->id }}" @selected($service === (string) $serviceOption->id)>{{ $serviceOption->localized_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="button button-amber" type="submit">{{ __('directory.search_button') }} <span aria-hidden="true">&#8594;</span></button>
                </form>
            </section>

            <section class="results-section" aria-labelledby="results-title">
                <div class="results-heading">
                    <div>
                        <p class="eyebrow eyebrow-muted">{{ __('directory.results_eyebrow') }}</p>
                        <h2 id="results-title">{{ __('directory.results_title') }}</h2>
                    </div>
                    <p class="result-count"><span>{{ $practitioners->count() }}</span> {{ $practitioners->count() === 1 ? 'profile' : 'profiles' }}</p>
                </div>

                <p class="sample-notice"><span class="gold-badge">{{ __('directory.review_badge') }}</span> {{ __('directory.preview_notice') }}</p>

                <div class="practitioner-grid">
                    @forelse ($practitioners as $practitioner)
                        @if (in_array($practitioner['type_label'], ['Hospital', 'Institution']))
                            <article class="practitioner-card institution-card">
                                <div class="card-topline">
                                    <span class="gold-badge">{{ strtoupper($practitioner['type_label']) }}</span>
                                    <span class="verified-label"><span aria-hidden="true">&#10003;</span> Admin approved</span>
                                </div>
                                <div class="provider-heading">
                                    <div class="hospital-emblem">🏥</div>
                                    <div>
                                        <h3>{{ $practitioner['name'] }}</h3>
                                        <p class="provider-location">{{ $practitioner['specialty'] }}@if ($practitioner['branch']) · {{ $practitioner['branch'] }}@endif</p>
                                    </div>
                                </div>
                                @if ($practitioner['address'])<p class="service-list"><strong>Address:</strong> {{ $practitioner['address'] }}</p>@endif
                                <div class="institution-features">
                                    <span class="institution-feature">Multi-location</span>
                                    <span class="institution-feature">24/7 Emergency</span>
                                    <span class="institution-feature">Full Service</span>
                                </div>
                                <div class="institution-contact">
                                    <div class="institution-contact-item">
                                        <span>📍</span>
                                        <span>{{ $practitioner['city'] }}@if ($practitioner['region']) · {{ $practitioner['region'] }}@endif</span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <span class="verified-label">{{ $practitioner['type_label'] }}</span>
                                    <span class="city-label">{{ $practitioner['city'] }}@if ($practitioner['region']) · {{ $practitioner['region'] }}@endif</span>
                                </div>
                            </article>
                        @else
                            <article class="practitioner-card">
                                <div class="card-topline">
                                    <span class="gold-badge">{{ strtoupper($practitioner['type_label']) }}</span>
                                    <span class="verified-label"><span aria-hidden="true">&#10003;</span> Admin approved</span>
                                </div>
                                <div class="provider-heading">
                                    <span class="provider-avatar" aria-hidden="true">{{ $practitioner['initials'] }}</span>
                                    <div>
                                        <h3>{{ $practitioner['name'] }}</h3>
                                        <p class="provider-location">{{ $practitioner['specialty'] }}@if ($practitioner['region']) · {{ $practitioner['region'] }}@endif @if ($practitioner['address']) · {{ $practitioner['address'] }}@endif</p>
                                    </div>
                                </div>
                                @if ($practitioner['services'])<p class="service-list"><strong>Services & slots:</strong> {{ $practitioner['services'] }}</p>@endif
                                @if (!empty($practitioner['weekly_schedule']))<p class="schedule-summary"><span class="status-dot"></span> <strong>Availability:</strong> {{ implode(' · ', $practitioner['weekly_schedule']) }}</p>@endif
                                @if (!empty($practitioner['vacations']))<p class="vacation-summary"><span class="status-dot status-dot-away"></span> <strong>Scheduled leave:</strong> {{ implode('; ', $practitioner['vacations']) }}</p>@endif
                                @if ($practitioner['branch'])<p class="service-list"><strong>Branch:</strong> {{ $practitioner['branch'] }}</p>@endif
                                @if ($practitioner['average_doctor_rating'])
                                    <div style="margin-top: 8px; padding: 6px 10px; background: var(--panel-raised); border: 1px solid var(--line); font-size: 11px;">
                                        <span style="color: var(--gold);">★ {{ $practitioner['average_doctor_rating'] }}</span>
                                        <span style="color: var(--muted);">({{ $practitioner['total_reviews'] }} review{{ $practitioner['total_reviews'] !== 1 ? 's' : '' }})</span>
                                    </div>
                                @endif
                                @if (!empty($practitioner['approved_comments']))
                                    <div class="card-reviews" style="margin-top: 8px; padding: 8px 10px; background: var(--panel-raised); border: 1px solid var(--line); font-size: 11px;">
                                        <div style="color: var(--gold); margin-bottom: 2px;">★ Verified patient review ({{ count($practitioner['approved_comments']) }})</div>
                                        <p style="margin: 0; color: var(--text);">&ldquo;{{ $practitioner['approved_comments'][0]['comment'] }}&rdquo; &ndash; <span style="color: var(--muted);">{{ $practitioner['approved_comments'][0]['author'] }}</span></p>
                                    </div>
                                @endif
                                @if (in_array($practitioner['type_label'], ['Doctor', 'Specialist', 'Practitioner']))
                                    <div style="margin-top: 12px;">
                                        <a class="button button-amber" style="display: flex; justify-content: center; width: 100%; text-align: center;" href="{{ route('appointments.create', $practitioner['id']) }}">
                                            Book appointment &rarr;
                                        </a>
                                    </div>
                                @endif
                                <div class="card-footer">
                                    <span class="verified-label">{{ $practitioner['type_label'] }}</span>
                                    <span class="city-label">{{ $practitioner['city'] }}@if ($practitioner['region']) · {{ $practitioner['region'] }}@endif</span>
                                </div>
                            </article>
                        @endif
                    @empty
                        <div class="empty-state">
                            <span class="empty-mark" aria-hidden="true">?</span>
                            <h3>{{ __('directory.empty_title') }}</h3>
                            <p>{{ __('directory.empty_description') }}</p>
                            <a class="text-link" href="{{ route('directory.index') }}">{{ __('directory.clear_search') }} <span aria-hidden="true">&#8594;</span></a>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="practitioner-callout" id="for-practitioners">
                <div class="callout-number">02</div>
                <div>
                    <p class="eyebrow eyebrow-muted">FOR HEALTHCARE PROFESSIONALS</p>
                    <h2>Make your practice easier to find.</h2>
                    <p>Practitioner and institution profiles will be reviewed by an administrator before they appear in the directory.</p>
                </div>
                <span class="gold-badge">APPLICATIONS COMING SOON</span>
            </section>
        </main>

        <footer class="site-footer">
            <span>northcare<span class="brand-period">.</span></span>
            <span>Healthcare directory · Northern Cyprus</span>
        </footer>
    </body>
</html>
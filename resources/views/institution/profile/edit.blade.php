<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Institution profile | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Main navigation">
                    <a class="nav-link" href="{{ route('directory.index') }}">Directory</a>
                    <a class="nav-link" href="{{ route('dashboard') }}">Account</a>
                </nav>
                <div class="header-actions">
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Sign out</button></form>
                </div>
            </div>
        </header>
        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> INSTITUTION PROFILE</p>
            <div class="institution-header">
                <div class="hospital-emblem">🏥</div>
                <div class="institution-header-content">
                    <h1>Manage {{ $institution->name }}.</h1>
                    <span class="institution-type">HOSPITAL / INSTITUTION</span>
                </div>
            </div>
            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
            
            <section class="form-section">
                <div class="form-section-title">Institution Details</div>
                <form class="auth-form" method="POST" action="{{ route('institution.profile.update') }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-grid">
                        <div class="field field-full improved-field">
                            <label for="name">Institution name</label>
                            <input id="name" name="name" value="{{ old('name', $institution->name) }}" required>
                        </div>
                        <div class="field improved-field">
                            <label for="contact_email">Contact email</label>
                            <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $institution->contact_email) }}">
                        </div>
                        <div class="field improved-field">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" value="{{ old('phone', $institution->phone) }}">
                        </div>
                        <div class="field field-full improved-field">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4">{{ old('description', $institution->description) }}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section-title" style="margin-top: 24px;">Visibility Settings</div>
                    <fieldset class="visibility-fields">
                        <legend>Who can see institution information?</legend>
                        @foreach (['name' => 'Name', 'contact_email' => 'Email', 'phone' => 'Phone', 'description' => 'Description'] as $field => $label)
                            @php($visibility = old('visibility.'.$field, $institution->visibility[$field] ?? ($field === 'name' || $field === 'description' ? 'public' : 'registered')))
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
                    
                    <div style="margin-top: 24px;">
                        <button class="improved-button" type="submit">Save institution</button>
                    </div>
                </form>
            </section>
            @foreach ($institution->locations as $location)
                <section class="form-section">
                    <div class="form-section-title">
                        📍 {{ $location->name ?: $location->city->name }} · {{ $location->city->name }}
                    </div>
                    <form class="auth-form" method="POST" action="{{ route('institution.locations.update', $location) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-grid">
                            <div class="field improved-field">
                                <label for="location_name_{{ $location->id }}">Location label</label>
                                <input id="location_name_{{ $location->id }}" name="name" value="{{ $location->name }}">
                            </div>
                            <div class="field improved-field">
                                <label for="location_city_{{ $location->id }}">City</label>
                                <select id="location_city_{{ $location->id }}" name="city_id" required>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}" @selected($location->city_id === $city->id)>{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field improved-field">
                                <label for="location_region_{{ $location->id }}">Region</label>
                                <select id="location_region_{{ $location->id }}" name="region_id">
                                    <option value="">No region</option>
                                    @foreach ($cities as $city)
                                        @foreach ($city->regions as $region)
                                            <option value="{{ $region->id }}" @selected($location->region_id === $region->id)>{{ $region->name }} / {{ $city->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="field field-full improved-field">
                                <label for="location_address_{{ $location->id }}">Address</label>
                                <input id="location_address_{{ $location->id }}" name="address" value="{{ $location->address }}" required>
                            </div>
                            <div class="field improved-field">
                                <label for="location_email_{{ $location->id }}">Contact email</label>
                                <input id="location_email_{{ $location->id }}" name="contact_email" type="email" value="{{ $location->contact_email }}">
                            </div>
                            <div class="field improved-field">
                                <label for="location_phone_{{ $location->id }}">Phone</label>
                                <input id="location_phone_{{ $location->id }}" name="phone" value="{{ $location->phone }}">
                            </div>
                        </div>
                        
                        <div class="form-section-title" style="margin-top: 24px;">Location Visibility</div>
                        <fieldset class="visibility-fields">
                            <legend>Who can see location information?</legend>
                            @foreach (['name' => 'Label', 'contact_email' => 'Email', 'phone' => 'Phone', 'address' => 'Address'] as $field => $label)
                                @php($visibility = old('visibility.'.$field, $location->visibility[$field] ?? 'registered'))
                                <div class="visibility-row">
                                    <label for="location_visibility_{{ $location->id }}_{{ $field }}">{{ $label }}</label>
                                    <select id="location_visibility_{{ $location->id }}_{{ $field }}" name="visibility[{{ $field }}]">
                                        <option value="public" @selected($visibility === 'public')>Public</option>
                                        <option value="registered" @selected($visibility === 'registered')>Registered users</option>
                                        <option value="private" @selected($visibility === 'private')>Private</option>
                                    </select>
                                </div>
                            @endforeach
                        </fieldset>
                        
                        <div style="margin-top: 24px;">
                            <button class="improved-button improved-button-secondary" type="submit">Save location</button>
                        </div>
                    </form>
                </section>
            @endforeach
        </main>
    </body>
</html>
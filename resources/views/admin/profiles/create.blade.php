<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create directory profile | NorthCare Admin</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Admin navigation"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a><a class="nav-link" href="{{ route('admin.applications.index') }}">Applications</a><a class="nav-link nav-link-active" href="{{ route('admin.profiles.create') }}">Profiles</a></nav>
            </div>
        </header>
        <main class="page-shell admin-catalog">
            <p class="eyebrow"><span class="eyebrow-line"></span> ADMIN DIRECTORY ENTRY</p>
            <h1>Create provider profiles.</h1>
            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif

            <section class="form-section">
                <div class="form-section-title">👨‍⚕️ Doctor or Specialist Profile</div>
                <form class="auth-form" method="POST" action="{{ route('admin.practitioners.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="field field-full improved-field">
                            <label for="practitioner_name">Name</label>
                            <input id="practitioner_name" name="name" required>
                        </div>
                        <div class="field improved-field">
                            <label for="practitioner_category">Category</label>
                            <select id="practitioner_category" name="category_id" required>
                                <option value="">Choose a category</option>
                                @foreach ($practitionerCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->parent->name }} / {{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field improved-field">
                            <label for="practitioner_city">City</label>
                            <select id="practitioner_city" name="city_id" required>
                                <option value="">Choose a city</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field improved-field">
                            <label for="practitioner_region">Region</label>
                            <select id="practitioner_region" name="region_id">
                                <option value="">No region</option>
                                @foreach ($cities as $city)
                                    @foreach ($city->regions as $region)
                                        <option value="{{ $region->id }}">{{ $region->name }} / {{ $city->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="field improved-field">
                            <label for="practitioner_email">Email</label>
                            <input id="practitioner_email" name="contact_email" type="email">
                        </div>
                        <div class="field improved-field">
                            <label for="practitioner_phone">Phone</label>
                            <input id="practitioner_phone" name="phone" type="tel">
                        </div>
                        <div class="field field-full improved-field">
                            <label for="practitioner_address">Address</label>
                            <input id="practitioner_address" name="address">
                        </div>
                        <div class="field field-full improved-field">
                            <label for="practitioner_bio">Description</label>
                            <textarea id="practitioner_bio" name="bio" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section-title" style="margin-top: 24px;">Visibility Settings</div>
                    <fieldset class="visibility-fields">
                        <legend>Field visibility</legend>
                        @foreach (['name' => 'Name', 'contact_email' => 'Email', 'phone' => 'Phone', 'address' => 'Address', 'bio' => 'Description'] as $field => $label)
                            <div class="visibility-row">
                                <label for="practitioner_visibility_{{ $field }}">{{ $label }}</label>
                                <select id="practitioner_visibility_{{ $field }}" name="visibility[{{ $field }}]">
                                    <option value="public" @selected($field === 'name' || $field === 'bio')>Public</option>
                                    <option value="registered" @selected($field !== 'name' && $field !== 'bio')>Registered users</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                        @endforeach
                    </fieldset>
                    
                    <div class="form-section-title" style="margin-top: 24px;">Services</div>
                    <fieldset class="visibility-fields">
                        <legend>Approved services</legend>
                        @foreach ($services as $service)
                            <label class="service-choice">
                                <input type="checkbox" name="service_ids[]" value="{{ $service->id }}">
                                <span>{{ $service->name }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    
                    <div style="margin-top: 24px;">
                        <button class="improved-button" type="submit">Create and publish practitioner</button>
                    </div>
                </form>
            </section>

            <section class="form-section">
                <div class="form-section-title">🏥 Hospital or Institution Profile</div>
                <form class="auth-form" method="POST" action="{{ route('admin.institutions.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="field field-full improved-field">
                            <label for="institution_name">Institution name</label>
                            <input id="institution_name" name="name" required>
                        </div>
                        <div class="field improved-field">
                            <label for="institution_email">Contact email</label>
                            <input id="institution_email" name="contact_email" type="email">
                        </div>
                        <div class="field improved-field">
                            <label for="institution_phone">Phone</label>
                            <input id="institution_phone" name="phone" type="tel">
                        </div>
                        <div class="field field-full improved-field">
                            <label for="institution_description">Description</label>
                            <textarea id="institution_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="field field-full improved-field">
                            <label for="institution_categories">Categories</label>
                            <select id="institution_categories" name="category_ids[]" multiple required>
                                @foreach ($hospitalCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->parent->name }} / {{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section-title" style="margin-top: 24px;">📍 First Location</div>
                    <div class="form-grid">
                        <div class="field improved-field">
                            <label for="institution_city">City</label>
                            <select id="institution_city" name="city_id" required>
                                <option value="">Choose a city</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field improved-field">
                            <label for="institution_region">Region</label>
                            <select id="institution_region" name="region_id">
                                <option value="">No region</option>
                                @foreach ($cities as $city)
                                    @foreach ($city->regions as $region)
                                        <option value="{{ $region->id }}">{{ $region->name }} / {{ $city->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="field improved-field">
                            <label for="location_name">Location label</label>
                            <input id="location_name" name="location_name" placeholder="Main branch">
                        </div>
                        <div class="field field-full improved-field">
                            <label for="institution_address">Address</label>
                            <input id="institution_address" name="address" required>
                        </div>
                    </div>
                    
                    <div class="form-section-title" style="margin-top: 24px;">Visibility Settings</div>
                    <fieldset class="visibility-fields">
                        <legend>Institution field visibility</legend>
                        @foreach (['name' => 'Name', 'contact_email' => 'Email', 'phone' => 'Phone', 'description' => 'Description'] as $field => $label)
                            <div class="visibility-row">
                                <label for="institution_visibility_{{ $field }}">{{ $label }}</label>
                                <select id="institution_visibility_{{ $field }}" name="visibility[{{ $field }}]">
                                    <option value="public" @selected($field === 'name' || $field === 'description')>Public</option>
                                    <option value="registered" @selected($field !== 'name' && $field !== 'description')>Registered users</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                        @endforeach
                    </fieldset>
                    
                    <div style="margin-top: 24px;">
                        <button class="improved-button" type="submit">Create and publish institution</button>
                    </div>
                </form>
            </section>

            <section class="form-section">
                <div class="form-section-title">📍 Add Institution Location</div>
                @forelse ($institutions as $institution)
                    <form class="catalog-form" method="POST" action="{{ route('admin.institutions.locations.store', $institution) }}">
                        @csrf
                        <h3>{{ $institution->name }}</h3>
                        <div class="form-grid">
                            <div class="field improved-field">
                                <label for="additional_location_city_{{ $institution->id }}">City</label>
                                <select id="additional_location_city_{{ $institution->id }}" name="city_id" required>
                                    <option value="">Choose a city</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field improved-field">
                                <label for="additional_location_region_{{ $institution->id }}">Region</label>
                                <select id="additional_location_region_{{ $institution->id }}" name="region_id">
                                    <option value="">No region</option>
                                    @foreach ($cities as $city)
                                        @foreach ($city->regions as $region)
                                            <option value="{{ $region->id }}">{{ $region->name }} / {{ $city->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="field improved-field">
                                <label for="additional_location_name_{{ $institution->id }}">Location label</label>
                                <input id="additional_location_name_{{ $institution->id }}" name="name">
                            </div>
                            <div class="field field-full improved-field">
                                <label for="additional_location_address_{{ $institution->id }}">Address</label>
                                <input id="additional_location_address_{{ $institution->id }}" name="address" required>
                            </div>
                            <div class="field improved-field">
                                <label for="additional_location_email_{{ $institution->id }}">Contact email</label>
                                <input id="additional_location_email_{{ $institution->id }}" name="contact_email" type="email">
                            </div>
                            <div class="field improved-field">
                                <label for="additional_location_phone_{{ $institution->id }}">Phone</label>
                                <input id="additional_location_phone_{{ $institution->id }}" name="phone">
                            </div>
                        </div>
                        <button class="improved-button improved-button-secondary" type="submit">Add location</button>
                    </form>
                @empty
                    <p class="field-hint">Create an institution profile before adding branch locations.</p>
                @endforelse
            </section>
        </main>
    </body>
</html>
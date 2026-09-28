<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Directory catalog | NorthCare Admin</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Admin navigation"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a><a class="nav-link" href="{{ route('admin.applications.index') }}">Applications</a><a class="nav-link nav-link-active" href="{{ route('admin.catalog.index') }}">Catalog</a></nav>
                <a class="nav-link" href="{{ route('admin.languages.index') }}">Languages</a>
            </div>
        </header>
        <main class="page-shell admin-catalog">
            <p class="eyebrow"><span class="eyebrow-line"></span> DIRECTORY SETTINGS</p>
            <h1>Locations and care catalog.</h1>
            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif

            <section class="catalog-section" aria-labelledby="locations-title">
                <div class="catalog-section-heading"><div><p class="eyebrow eyebrow-muted">ADDRESS OPTIONS</p><h2 id="locations-title">Cities and regions</h2></div></div>
                <div class="catalog-forms">
                    <form class="catalog-form" method="POST" action="{{ route('admin.cities.store') }}">
                        @csrf
                        <h3>Add city</h3>
                        <div class="field"><label for="city_name">City name</label><input id="city_name" name="name" required></div>
                        @foreach ($languages as $language)
                            <div class="field"><label for="city_translation_{{ $language->id }}">{{ $language->name }} translation</label><input id="city_translation_{{ $language->id }}" name="translations[{{ $language->id }}]"></div>
                        @endforeach
                        <button class="button button-amber" type="submit">Add city</button>
                    </form>
                    <form class="catalog-form" method="POST" action="{{ route('admin.regions.store') }}">
                        @csrf
                        <h3>Add region</h3>
                        <div class="field"><label for="region_city_id">City</label><select id="region_city_id" name="city_id" required><option value="">Choose a city</option>@foreach ($cities as $city)<option value="{{ $city->id }}">{{ $city->name }}</option>@endforeach</select></div>
                        <div class="field"><label for="region_name">Region name</label><input id="region_name" name="name" required></div>
                        @foreach ($languages as $language)
                            <div class="field"><label for="region_translation_{{ $language->id }}">{{ $language->name }} translation</label><input id="region_translation_{{ $language->id }}" name="translations[{{ $language->id }}]"></div>
                        @endforeach
                        <button class="button button-amber" type="submit">Add region</button>
                    </form>
                </div>
                <div class="catalog-list">
                    @foreach ($cities as $city)
                        <div class="catalog-list-row"><strong>{{ $city->name }}</strong><span>{{ $city->regions->pluck('name')->join(', ') ?: 'No regions yet' }}</span></div>
                    @endforeach
                </div>
            </section>

            <section class="catalog-section" aria-labelledby="categories-title">
                <div class="catalog-section-heading"><div><p class="eyebrow eyebrow-muted">TWO LEVELS</p><h2 id="categories-title">Categories</h2></div></div>
                <form class="catalog-form catalog-form-wide" method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="field"><label for="category_name">Category name</label><input id="category_name" name="name" required></div>
                        <div class="field"><label for="category_kind">Top-level type</label><select id="category_kind" name="kind"><option value="doctor">Doctor</option><option value="specialist">Specialist</option><option value="hospital">Hospital</option></select></div>
                        <div class="field"><label for="category_parent_id">Parent category (leave blank for top level)</label><select id="category_parent_id" name="parent_id"><option value="">Top-level category</option>@foreach ($rootCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                        @foreach ($languages as $language)
                            <div class="field"><label for="category_translation_{{ $language->id }}">{{ $language->name }} translation</label><input id="category_translation_{{ $language->id }}" name="translations[{{ $language->id }}]"></div>
                        @endforeach
                    </div>
                    <button class="button button-amber" type="submit">Add category</button>
                </form>
                <div class="catalog-list">
                    @foreach ($rootCategories as $category)
                        <div class="catalog-list-row catalog-category-root"><strong>{{ $category->name }}</strong><span>{{ $category->children->pluck('name')->join(', ') }}</span></div>
                    @endforeach
                </div>
            </section>

            <section class="catalog-section" aria-labelledby="services-title">
                <div class="catalog-section-heading"><div><p class="eyebrow eyebrow-muted">APPROVED OPTIONS</p><h2 id="services-title">Services</h2></div></div>
                <form class="catalog-form catalog-form-wide" method="POST" action="{{ route('admin.services.store') }}">
                    @csrf
                    <div class="form-grid"><div class="field"><label for="service_name">Service name</label><input id="service_name" name="name" required></div><div class="field"><label for="service_description">Description</label><input id="service_description" name="description"></div>
                        @foreach ($languages as $language)<div class="field"><label for="service_translation_{{ $language->id }}">{{ $language->name }} translation</label><input id="service_translation_{{ $language->id }}" name="translations[{{ $language->id }}]"></div>@endforeach
                    </div>
                    <button class="button button-amber" type="submit">Add service</button>
                </form>
                <div class="catalog-list">
                    @foreach ($services as $service)
                        <form class="catalog-list-row service-edit-form" method="POST" action="{{ route('admin.services.update', $service) }}">
                            @csrf
                            @method('PATCH')
                            <div class="field"><label for="service_name_{{ $service->id }}">Service</label><input id="service_name_{{ $service->id }}" name="name" value="{{ $service->name }}" required></div>
                            <div class="field"><label for="service_description_{{ $service->id }}">Description</label><input id="service_description_{{ $service->id }}" name="description" value="{{ $service->description }}"></div>
                            <label class="check-field"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($service->is_active)> Active</label>
                            <button class="button button-secondary" type="submit">Save</button>
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="catalog-section" aria-labelledby="suggestions-title">
                <div class="catalog-section-heading"><div><p class="eyebrow eyebrow-muted">PRACTITIONER SUBMISSIONS</p><h2 id="suggestions-title">Service suggestions</h2></div><span class="gold-badge">{{ $suggestions->count() }} PENDING</span></div>
                <div class="catalog-list">
                    @forelse ($suggestions as $suggestion)
                        <form class="suggestion-row" method="POST" action="{{ route('admin.service-suggestions.update', $suggestion) }}">
                            @csrf
                            @method('PATCH')
                            <p class="field-hint">Suggested by {{ $suggestion->submitter->name }} · {{ $suggestion->description }}</p>
                            <div class="form-grid"><div class="field"><label for="suggestion_name_{{ $suggestion->id }}">Approved service name</label><input id="suggestion_name_{{ $suggestion->id }}" name="name" value="{{ $suggestion->name }}" required></div><div class="field"><label for="suggestion_existing_{{ $suggestion->id }}">Edit existing service instead</label><select id="suggestion_existing_{{ $suggestion->id }}" name="service_id"><option value="">Create new service</option>@foreach ($services as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select></div></div>
                            <div class="form-actions"><button class="button button-amber" name="decision" value="approve" type="submit">Approve</button><button class="button button-secondary" name="decision" value="reject" type="submit">Reject</button></div>
                        </form>
                    @empty
                        <p class="field-hint">No service suggestions waiting for review.</p>
                    @endforelse
                </div>
            </section>
        </main>
    </body>
</html>
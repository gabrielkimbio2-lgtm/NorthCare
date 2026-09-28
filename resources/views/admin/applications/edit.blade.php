<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Review {{ $application->name }} | NorthCare Admin</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Admin navigation"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a><a class="nav-link nav-link-active" href="{{ route('admin.applications.index') }}">Applications</a></nav>
            </div>
        </header>
        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> REVIEW APPLICATION #{{ $application->id }}</p>
            <section class="auth-panel" aria-labelledby="review-title">
                <span class="gold-badge">{{ strtoupper($application->application_type) }}</span>
                <h1 id="review-title">{{ $application->name }}</h1>
                <p class="auth-copy">Submitted by {{ $application->applicant->email }} on {{ $application->created_at->format('M j, Y') }}.</p>
                @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('admin.applications.update', $application) }}" class="auth-form">
                    @csrf
                    @method('PATCH')
                    <div class="form-grid">
                        <div class="field field-full"><label for="name">Profile name</label><input id="name" name="name" value="{{ old('name', $application->name) }}" required></div>
                        <div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id" required>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $application->category_id) === (string) $category->id)>{{ $category->parent->name }} / {{ $category->name }}</option>@endforeach</select></div>
                        <div class="field"><label for="city_id">City</label><select id="city_id" name="city_id" required>@foreach ($cities as $city)<option value="{{ $city->id }}" @selected((string) old('city_id', $application->city_id) === (string) $city->id)>{{ $city->name }}</option>@endforeach</select></div>
                        <div class="field"><label for="region_id">Region</label><select id="region_id" name="region_id"><option value="">No region selected</option>@foreach ($cities as $city)@foreach ($city->regions as $region)<option value="{{ $region->id }}" @selected((string) old('region_id', $application->region_id) === (string) $region->id)>{{ $region->name }} / {{ $city->name }}</option>@endforeach @endforeach</select></div>
                        <div class="field"><label for="contact_email">Contact email</label><input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $application->contact_email) }}"></div>
                        <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $application->phone) }}"></div>
                        <div class="field field-full"><label for="address">Address / location</label><input id="address" name="address" value="{{ old('address', $application->address) }}"></div>
                        <div class="field field-full"><label for="description">Description</label><textarea id="description" name="description" rows="4">{{ old('description', $application->description) }}</textarea></div>
                    </div>
                    @if ($application->application_type === 'practitioner')
                        <div class="field"><label for="matched_practitioner_profile_id">Match existing practitioner profile</label><select id="matched_practitioner_profile_id" name="matched_practitioner_profile_id"><option value="">Create a new profile</option>@foreach ($practitionerProfiles as $profile)<option value="{{ $profile->id }}" @selected((string) old('matched_practitioner_profile_id', $application->matched_practitioner_profile_id) === (string) $profile->id)>{{ $profile->name }} · {{ $profile->city->name }}</option>@endforeach</select></div>
                    @else
                        <div class="form-grid">
                            <div class="field"><label for="matched_institution_id">Match existing institution</label><select id="matched_institution_id" name="matched_institution_id"><option value="">Create a new institution</option>@foreach ($institutions as $institution)<option value="{{ $institution->id }}" @selected((string) old('matched_institution_id', $application->matched_institution_id) === (string) $institution->id)>{{ $institution->name }}</option>@endforeach</select></div>
                            <div class="field"><label for="matched_institution_location_id">Match existing location</label><select id="matched_institution_location_id" name="matched_institution_location_id"><option value="">Create a new location</option>@foreach ($institutionLocations as $location)<option value="{{ $location->id }}" @selected((string) old('matched_institution_location_id', $application->matched_institution_location_id) === (string) $location->id)>{{ $location->institution->name }} · {{ $location->name ?: $location->address }}</option>@endforeach</select></div>
                        </div>
                    @endif
                    <fieldset class="visibility-fields">
                        <legend>Visibility after approval</legend>
                        @foreach (['name' => 'Name', 'contact_email' => 'Email', 'phone' => 'Phone', 'address' => 'Address', 'description' => 'Description'] as $field => $label)
                            <div class="visibility-row"><label for="visibility_{{ $field }}">{{ $label }}</label><select id="visibility_{{ $field }}" name="visibility[{{ $field }}]"><option value="public" @selected(old('visibility.'.$field, $application->visibility[$field] ?? 'public') === 'public')>Public</option><option value="registered" @selected(old('visibility.'.$field, $application->visibility[$field] ?? 'public') === 'registered')>Registered users</option><option value="private" @selected(old('visibility.'.$field, $application->visibility[$field] ?? 'public') === 'private')>Private</option></select></div>
                        @endforeach
                    </fieldset>
                    <div class="document-list"><h2>Submitted documents</h2>@forelse ($application->documents as $document)<a class="text-link" href="{{ route('admin.applications.documents.download', [$application, $document]) }}">{{ $document->original_name }} <span>({{ number_format($document->byte_size / 1024) }} KB)</span></a>@empty<p class="field-hint">No documents attached.</p>@endforelse</div>
                    <div class="field"><label for="admin_notes">Admin notes</label><textarea id="admin_notes" name="admin_notes" rows="3">{{ old('admin_notes', $application->admin_notes) }}</textarea></div>
                    <div class="form-actions"><button class="button button-amber" name="decision" value="approve" type="submit">Approve and publish</button><button class="button button-secondary" name="decision" value="reject" type="submit">Reject application</button></div>
                </form>
            </section>
        </main>
    </body>
</html>
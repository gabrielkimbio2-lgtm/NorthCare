<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create an account | NorthCare</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('directory.index') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Main navigation"><a class="nav-link" href="{{ route('directory.index') }}">Find care</a><a class="nav-link" href="{{ route('login') }}">Sign in</a></nav>
            </div>
        </header>
        <main class="auth-shell auth-shell-wide">
            <p class="eyebrow"><span class="eyebrow-line"></span> JOIN THE DIRECTORY</p>
            <section class="auth-panel" aria-labelledby="register-title">
                <h1 id="register-title">Create your account.</h1>
                <p class="auth-copy">Patients can start right away. Practitioner and institution profiles are reviewed before they appear.</p>
                @if ($errors->any())
                    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="auth-form">
                    @csrf
                    @php($providerRegistration = in_array(old('account_type', 'patient'), ['practitioner', 'institution'], true))
                    <div class="field">
                        <label for="account_type">I am joining as</label>
                        <select id="account_type" name="account_type" required>
                            <option value="patient" @selected(old('account_type', 'patient') === 'patient')>Patient / regular user</option>
                            <option value="practitioner" @selected(old('account_type') === 'practitioner')>Doctor / specialist</option>
                            <option value="institution" @selected(old('account_type') === 'institution')>Hospital / institution</option>
                        </select>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Full name or institution name</label>
                            <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="email">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        </div>
                        <div class="field">
                            <label for="preferred_locale">Language</label>
                            <select id="preferred_locale" name="preferred_locale">
                                <option value="en">English</option>
                                @foreach ($languages as $language)
                                    @if ($language->code !== 'en')
                                        <option value="{{ $language->code }}" @selected(old('preferred_locale') === $language->code)>{{ $language->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="provider-fields" id="provider-fields" @if (! $providerRegistration) hidden @endif>
                        <div class="section-divider"><span>Practitioner / institution details</span></div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="category_id">Category</label>
                                    <select id="category_id" name="category_id" data-provider-required>
                                    <option value="">Choose a category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->parent_localized_name }} / {{ $category->localized_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="city_id">City</label>
                                    <select id="city_id" name="city_id" data-provider-required>
                                    <option value="">Choose a city</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}" @selected((string) old('city_id') === (string) $city->id)>{{ $city->localized_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="region_id">Region</label>
                                <select id="region_id" name="region_id">
                                    <option value="">Choose a region</option>
                                    @foreach ($cities as $city)
                                        @foreach ($city->regions as $region)
                                            <option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->localized_name }} / {{ $city->localized_name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="phone">Phone</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" data-provider-required>
                            </div>
                            <div class="field field-full">
                                <label for="address">Address</label>
                                <input id="address" name="address" value="{{ old('address') }}" autocomplete="street-address">
                            </div>
                            <div class="field field-full">
                                <label for="description">Profile description</label>
                                <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="field upload-field">
                            <label for="documents">Supporting documents</label>
                            <input id="documents" name="documents[]" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple data-provider-required>
                            <span class="field-hint">Required for provider applications. Up to 5 PDF or image files, 5 MB each.</span>
                        </div>
                        <fieldset class="visibility-fields">
                            <legend>Who can see your profile information?</legend>
                            @foreach (['contact_email' => 'Email', 'phone' => 'Phone', 'address' => 'Address', 'description' => 'Description'] as $field => $label)
                                <div class="visibility-row">
                                    <label for="visibility_{{ $field }}">{{ $label }}</label>
                                    <select id="visibility_{{ $field }}" name="visibility[{{ $field }}]">
                                        <option value="public" @selected(old('visibility.'.$field, $field === 'description' ? 'public' : 'registered') === 'public')>Public</option>
                                        <option value="registered" @selected(old('visibility.'.$field, $field === 'description' ? 'public' : 'registered') === 'registered')>Registered users</option>
                                        <option value="private" @selected(old('visibility.'.$field) === 'private')>Private</option>
                                    </select>
                                </div>
                            @endforeach
                        </fieldset>
                    </div>
                    <button class="button button-amber" type="submit">Create account <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="auth-footnote">Already registered? <a class="text-link" href="{{ route('login') }}">Sign in</a></p>
            </section>
        </main>
        <script>
            const accountType = document.getElementById('account_type');
            const providerFields = document.getElementById('provider-fields');
            const providerControls = providerFields.querySelectorAll('input, select, textarea');
            const providerRequiredControls = providerFields.querySelectorAll('[data-provider-required]');

            function updateProviderFields() {
                const isProvider = accountType.value !== 'patient';
                providerFields.hidden = !isProvider;

                providerControls.forEach((control) => {
                    control.disabled = !isProvider;
                });

                providerRequiredControls.forEach((control) => {
                    control.required = isProvider;
                });
            }

            accountType.addEventListener('change', updateProviderFields);
            updateProviderFields();
        </script>
    </body>
</html>
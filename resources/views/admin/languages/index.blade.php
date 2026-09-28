<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Languages | NorthCare Admin</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">N</span><span>northcare<span class="brand-period">.</span></span></a>
                <nav class="main-nav" aria-label="Admin navigation"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a><a class="nav-link" href="{{ route('admin.catalog.index') }}">Catalog</a><a class="nav-link nav-link-active" href="{{ route('admin.languages.index') }}">Languages</a></nav>
            </div>
        </header>
        <main class="page-shell admin-catalog">
            <p class="eyebrow"><span class="eyebrow-line"></span> LOCALIZATION</p>
            <h1>Languages and translations.</h1>
            @if (session('status'))<div class="status-panel">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
            <p class="intro-copy">Add a language by translating each interface label. Visitors can choose any active language.</p>
            <section class="catalog-section" aria-labelledby="add-language-title">
                <h2 id="add-language-title">Add language</h2>
                <form class="auth-form language-create-form" method="POST" action="{{ route('admin.languages.store') }}">
                    @csrf
                    <div class="form-grid"><div class="field"><label for="language_code">Language code</label><input id="language_code" name="code" placeholder="tr or tr-TR" required></div><div class="field"><label for="language_name">Language name</label><input id="language_name" name="name" required></div><div class="field"><label for="language_native_name">Native name</label><input id="language_native_name" name="native_name" required></div></div>
                    <div class="translation-grid">
                        @foreach ($translationKeys as $translation)
                            <div class="field"><label for="new_translation_{{ $loop->index }}">{{ $translation->value }} <span class="field-hint">{{ $translation->key }}</span></label><input id="new_translation_{{ $loop->index }}" name="translations[{{ $translation->key }}]" required></div>
                        @endforeach
                    </div>
                    <button class="button button-amber" type="submit">Add translated language</button>
                </form>
            </section>
            @foreach ($languages as $language)
                <section class="catalog-section" aria-labelledby="language-{{ $language->id }}">
                    <div class="catalog-section-heading"><div><p class="eyebrow eyebrow-muted">{{ $language->code }}</p><h2 id="language-{{ $language->id }}">{{ $language->native_name }}</h2></div>@if ($language->is_default)<span class="gold-badge">DEFAULT</span>@endif</div>
                    <form class="auth-form language-edit-form" method="POST" action="{{ route('admin.languages.update', $language) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-grid"><div class="field"><label for="language_name_{{ $language->id }}">Language name</label><input id="language_name_{{ $language->id }}" name="name" value="{{ $language->name }}" required></div><div class="field"><label for="native_name_{{ $language->id }}">Native name</label><input id="native_name_{{ $language->id }}" name="native_name" value="{{ $language->native_name }}" required></div></div>
                        <label class="check-field"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($language->is_active)> Active</label>
                        <div class="translation-grid">
                            @foreach ($translationKeys as $translation)
                                @php($currentValue = $language->uiTranslations->firstWhere('key', $translation->key)?->value)
                                <div class="field"><label for="translation_{{ $language->id }}_{{ $loop->index }}">{{ $translation->value }} <span class="field-hint">{{ $translation->key }}</span></label><input id="translation_{{ $language->id }}_{{ $loop->index }}" name="translations[{{ $translation->key }}]" value="{{ $currentValue }}" @required(! $language->is_default)></div>
                            @endforeach
                        </div>
                        <button class="button button-secondary" type="submit">Save language</button>
                    </form>
                </section>
            @endforeach
        </main>
    </body>
</html>
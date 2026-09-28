<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $languages = Language::query()->where('is_active', true)->orderBy('name')->get();
        $requestedLocale = $request->session()->get('locale') ?? $request->user()?->preferred_locale;
        $language = $languages->firstWhere('code', $requestedLocale)
            ?? $languages->firstWhere('is_default', true)
            ?? $languages->first();
        $locale = $language?->code ?? config('app.locale', 'en');

        App::setLocale($locale);

        if ($language !== null) {
            $lines = $language->uiTranslations()->pluck('value', 'key')->all();
            app('translator')->addLines($lines, $locale);
        }

        View::share('availableLanguages', $languages);
        View::share('currentLocale', $locale);

        return $next($request);
    }
}

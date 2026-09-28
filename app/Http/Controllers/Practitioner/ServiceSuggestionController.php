<?php

namespace App\Http\Controllers\Practitioner;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceSuggestionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $normalizedName = Str::lower(trim($validated['name']));

        if (Service::query()->whereRaw('LOWER(name) = ?', [$normalizedName])->exists()) {
            throw ValidationException::withMessages(['name' => 'This service is already in the approved list.']);
        }

        if (ServiceSuggestion::query()->where('submitted_by', $request->user()->id)->where('status', 'pending')->whereRaw('LOWER(name) = ?', [$normalizedName])->exists()) {
            throw ValidationException::withMessages(['name' => 'You already have a pending suggestion with this name.']);
        }

        $request->user()->serviceSuggestions()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Your service suggestion was sent to the admin team.');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        if ($request->user()?->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Use the administrator sign-in page for this account.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user?->account_status === 'pending') {
            return redirect()->route('account.pending-approval');
        }

        if ($user?->account_status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This account is not active. Contact the NorthCare team.'])->onlyInput('email');
        }

        if ($user->role === UserRole::Practitioner && $user->practitionerProfile()->exists()) {
            return redirect()->route('practitioner.profile.edit');
        }

        if ($user->role === UserRole::Institution && $user->institution()->exists()) {
            return redirect()->route('institution.profile.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('directory.index');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalStatusController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->account_status !== 'pending') {
            if ($user->role === UserRole::Practitioner && $user->practitionerProfile()->exists()) {
                return redirect()->route('practitioner.profile.edit');
            }

            if ($user->role === UserRole::Institution && $user->institution()->exists()) {
                return redirect()->route('institution.profile.edit');
            }

            return redirect()->route('dashboard');
        }

        if (! in_array($user->role, [UserRole::Practitioner, UserRole::Institution], true)) {
            return redirect()->route('dashboard');
        }

        return view('auth.pending-approval', [
            'user' => $user,
            'application' => $user->profileApplications()->latest()->first(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->account_status === 'pending') {
            return redirect()->route('account.pending-approval');
        }

        if ($request->user()->role === UserRole::Practitioner && $request->user()->practitionerProfile()->exists()) {
            return redirect()->route('practitioner.profile.edit');
        }

        if ($request->user()->role === UserRole::Institution && $request->user()->institution()->exists()) {
            return redirect()->route('institution.profile.edit');
        }

        $application = $request->user()->profileApplications()->latest()->first();
        $appointments = $request->user()->appointments()
            ->with(['practitionerProfile.category', 'service', 'comment'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('starts_at')
            ->get();

        return view('auth.dashboard', [
            'application' => $application,
            'user' => $request->user(),
            'appointments' => $appointments,
        ]);
    }
}

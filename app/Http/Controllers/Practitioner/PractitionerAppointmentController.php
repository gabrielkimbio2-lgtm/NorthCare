<?php

namespace App\Http\Controllers\Practitioner;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PractitionerAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->practitionerProfile()->firstOrFail();

        $pendingAppointments = $profile->appointments()
            ->with(['user', 'service'])
            ->where('status', 'pending')
            ->orderBy('appointment_date')
            ->orderBy('starts_at')
            ->get();

        $approvedAppointments = $profile->appointments()
            ->with(['user', 'service'])
            ->where('status', 'approved')
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->orderBy('starts_at')
            ->get();

        $pastAppointments = $profile->appointments()
            ->with(['user', 'service', 'comment'])
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orWhere(function ($q) {
                        $q->where('status', 'approved')
                            ->whereDate('appointment_date', '<', today());
                    });
            })
            ->latest('appointment_date')
            ->limit(20)
            ->get();

        $receivedComments = $profile->comments()
            ->with(['user', 'appointment.service'])
            ->latest()
            ->get();

        return view('practitioner.appointments.index', [
            'profile' => $profile,
            'pendingAppointments' => $pendingAppointments,
            'approvedAppointments' => $approvedAppointments,
            'pastAppointments' => $pastAppointments,
            'receivedComments' => $receivedComments,
        ]);
    }

    public function approve(Request $request, Appointment $appointment): RedirectResponse
    {
        $profile = $request->user()->practitionerProfile()->firstOrFail();
        abort_unless($appointment->practitioner_profile_id === $profile->id, 403);

        $appointment->update([
            'status' => 'approved',
            'doctor_notes' => $request->input('doctor_notes'),
        ]);

        return back()->with('status', 'Appointment approved. Both your schedule and the patient schedule are now closed for that timeslot.');
    }

    public function reject(Request $request, Appointment $appointment): RedirectResponse
    {
        $profile = $request->user()->practitionerProfile()->firstOrFail();
        abort_unless($appointment->practitioner_profile_id === $profile->id, 403);

        $appointment->update([
            'status' => 'rejected',
            'doctor_notes' => $request->input('doctor_notes'),
        ]);

        return back()->with('status', 'Appointment request has been rejected.');
    }

    public function reportComment(Request $request, AppointmentComment $comment): RedirectResponse
    {
        $profile = $request->user()->practitionerProfile()->firstOrFail();
        abort_unless($comment->practitioner_profile_id === $profile->id, 403);

        $validated = $request->validate([
            'report_reason' => ['required', 'string', 'max:1000'],
        ]);

        $comment->update([
            'is_reported' => true,
            'report_reason' => $validated['report_reason'],
            'reported_at' => now(),
        ]);

        return back()->with('status', 'Comment reported to administrators for review.');
    }
}

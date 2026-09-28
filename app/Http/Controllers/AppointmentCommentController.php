<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentCommentController extends Controller
{
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        abort_unless($appointment->user_id === $user->id, 403);

        if (in_array($appointment->status, ['cancelled', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'comment' => 'You cannot leave a comment on a cancelled or rejected meeting.',
            ]);
        }

        if (! $appointment->isCompleted()) {
            throw ValidationException::withMessages([
                'comment' => 'You can only leave a comment after the scheduled meeting has taken place.',
            ]);
        }

        if ($appointment->comment()->exists()) {
            throw ValidationException::withMessages([
                'comment' => 'You have already submitted a comment for this meeting.',
            ]);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
            'doctor_rating' => ['nullable', 'integer', 'between:1,5'],
            'service_rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        AppointmentComment::create([
            'appointment_id' => $appointment->id,
            'user_id' => $user->id,
            'practitioner_profile_id' => $appointment->practitioner_profile_id,
            'comment' => $validated['comment'],
            'doctor_rating' => $validated['doctor_rating'] ?? null,
            'service_rating' => $validated['service_rating'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Your comment has been submitted and sent to the administrators for review.');
    }
}

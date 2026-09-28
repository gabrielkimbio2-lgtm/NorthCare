<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCommentController extends Controller
{
    public function index(Request $request): View
    {
        $pendingComments = AppointmentComment::query()
            ->with(['user', 'practitionerProfile', 'appointment.service'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $reportedComments = AppointmentComment::query()
            ->with(['user', 'practitionerProfile', 'appointment.service'])
            ->where('is_reported', true)
            ->whereIn('status', ['approved', 'pending'])
            ->latest('reported_at')
            ->get();

        $approvedComments = AppointmentComment::query()
            ->with(['user', 'practitionerProfile', 'appointment.service'])
            ->where('status', 'approved')
            ->where('is_reported', false)
            ->latest('reviewed_at')
            ->limit(30)
            ->get();

        $rejectedComments = AppointmentComment::query()
            ->with(['user', 'practitionerProfile', 'appointment.service'])
            ->whereIn('status', ['rejected', 'removed'])
            ->latest('reviewed_at')
            ->limit(30)
            ->get();

        return view('admin.comments.index', [
            'pendingComments' => $pendingComments,
            'reportedComments' => $reportedComments,
            'approvedComments' => $approvedComments,
            'rejectedComments' => $rejectedComments,
        ]);
    }

    public function moderate(Request $request, AppointmentComment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'take_down', 'dismiss_report'])],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $decision = $validated['decision'];
        $notes = $validated['admin_notes'] ?? null;
        $adminId = $request->user()->id;

        if ($decision === 'approve') {
            $comment->update([
                'status' => 'approved',
                'is_reported' => false,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
            ]);
            $msg = 'Comment approved and published.';
        } elseif ($decision === 'reject') {
            $comment->update([
                'status' => 'rejected',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
            ]);
            $msg = 'Comment rejected.';
        } elseif ($decision === 'take_down') {
            $comment->update([
                'status' => 'removed',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
            ]);
            $msg = 'Comment taken down from the system.';
        } else { // dismiss_report
            $comment->update([
                'is_reported' => false,
                'admin_notes' => $notes,
            ]);
            $msg = 'Report dismissed. Comment remains approved.';
        }

        return back()->with('status', $msg);
    }
}

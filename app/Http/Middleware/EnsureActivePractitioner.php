<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePractitioner
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        abort_unless(
            $user->role === UserRole::Practitioner
                && $user->account_status === 'active'
                && $user->practitionerProfile()->exists(),
            403
        );

        return $next($request);
    }
}

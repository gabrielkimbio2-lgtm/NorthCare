<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveInstitution
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
            $user->role === UserRole::Institution
                && $user->account_status === 'active'
                && $user->institution()->exists(),
            403
        );

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Flatten any comma-separated roles passed (e.g. 'role:production_staff,business_owner')
        $allowedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $trimmed = trim($r);
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }

        if (empty($allowedRoles) || in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        // Graceful redirect to the user's designated home portal
        $redirectRoute = match ($user->role) {
            User::ROLE_BUSINESS_OWNER => route('owner.dashboard'),
            User::ROLE_PRODUCTION_STAFF => route('staff.dashboard'),
            default => route('customer.dashboard'),
        };

        return redirect($redirectRoute)->with('warning', 'Access restricted. You have been redirected to your role portal.');
    }
}

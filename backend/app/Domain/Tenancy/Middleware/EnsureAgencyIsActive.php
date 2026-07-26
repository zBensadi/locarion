<?php

namespace App\Domain\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domain\Tenancy\Models\Agency;

class EnsureAgencyIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->agency_id) {
            $agency = Agency::find($user->agency_id);
            if (! $agency || $agency->status === 'suspended') {
                abort(403, 'Your agency is suspended.');
            }
        }

        return $next($request);
    }
}

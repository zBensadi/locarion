<?php

namespace App\Domain\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domain\Tenancy\Services\TenantContext;

class SetPermissionsTeamId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $tenantContext = app(TenantContext::class);
            
            // In a real impersonation scenario, this might check for a session variable first.
            // For now, it defaults to the authenticated user's own agency_id.
            $agencyId = $user->agency_id;
            
            $tenantContext->setAgencyId($agencyId);
            
            // Set Spatie permissions team to match
            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                setPermissionsTeamId($agencyId);
            }
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\SuperAdmin\Models\Etablissement;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify's routes (login, logout, password reset...) are shared across every
 * domain (`fortify.domain` is null) and used to hardcode `resolve.tenant`
 * (config/fortify.php), which does `Etablissement::where('sous_domaine', ...)->firstOrFail()`.
 * There is no `etablissement` row for "admin", so every Fortify route 404'd on
 * admin.plateforme.sn — invisible until Session 9 added a page an unauthenticated
 * Super Admin could actually be redirected to /login from. Branches the same way
 * the shared Livewire update route does (cf. AppServiceProvider::boot()):
 * platform.team on the admin subdomain, normal tenant resolution everywhere else.
 */
class ResolveTenantOrPlatformTeam
{
    public function __construct(private readonly ResolveTenant $resolveTenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === config('app.admin_subdomain')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(Etablissement::PLATFORM_TEAM_ID);

            return $next($request);
        }

        return $this->resolveTenant->handle($request, $next);
    }
}

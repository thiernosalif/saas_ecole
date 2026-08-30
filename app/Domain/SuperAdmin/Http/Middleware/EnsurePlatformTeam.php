<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Middleware;

use App\Domain\SuperAdmin\Models\Etablissement;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le portail Super Admin ne passe pas par ResolveTenant (aucun etablissement
 * courant), donc rien ne fixe le "team" spatie sur cette requête : sans ce
 * middleware, hasAnyRole('SUPER_ADMIN') vérifierait contre un team_id
 * résiduel d'une requête précédente (Octane) ou aucun, jamais fiable. On le
 * fixe explicitement sur le nil-UUID réservé à la plateforme (§3.3).
 */
class EnsurePlatformTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(Etablissement::PLATFORM_TEAM_ID);

        return $next($request);
    }
}

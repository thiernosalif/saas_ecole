<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\SuperAdmin\Models\Etablissement;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = explode('.', $request->getHost())[0];

        // Etablissement est une table globale, non scopée par tenant (§15.5) :
        // pas de withoutTenant() à appeler, il n'y a pas de TenantScope à contourner.
        $tenant = Etablissement::where('sous_domaine', $subdomain)->firstOrFail();

        abort_if($tenant->statut === 'SUSPENDU', 403, 'École suspendue.');

        // currentTenantId = etablissement.tenant_id (le slug), pas etablissement.id (PK) :
        // les tables métier référencent etablissement(tenant_id), cf. PROJET_LARAVEL.md §5.2.
        app()->instance('currentTenant', $tenant);
        app()->instance('currentTenantId', $tenant->tenant_id);

        // Le "team" spatie est un concept distinct : il est scopé sur etablissement.id
        // (la PK), pas sur le slug tenant_id — cf. §3.3, cohérent avec le type de colonne
        // team_id (bigint/uuid) généré par la migration spatie, pas VARCHAR.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        return $next($request);
    }
}

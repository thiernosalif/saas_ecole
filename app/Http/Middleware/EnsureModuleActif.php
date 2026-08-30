<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\SuperAdmin\Models\Etablissement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate un module optionnel (§15.1 "Modules & limites") via
 * `Etablissement::aModule()` plutôt qu'un if dispersé dans chaque
 * controller — utilisé pour le paiement en ligne (module
 * `paiement_mobile_money`), toujours après `resolve.tenant`.
 */
class EnsureModuleActif
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        /** @var Etablissement|null $tenant */
        $tenant = app('currentTenant');

        abort_unless($tenant?->aModule($module), 403, "Module « {$module} » non activé pour cette école.");

        return $next($request);
    }
}

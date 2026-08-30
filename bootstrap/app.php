<?php

use App\Domain\SuperAdmin\Console\Commands\CheckAbonnementsCommand;
use App\Domain\SuperAdmin\Http\Middleware\EnsurePlatformTeam;
use App\Http\Middleware\EnsureModuleActif;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserActif;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\ResolveTenantOrPlatformTeam;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        CheckAbonnementsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'resolve.tenant' => ResolveTenant::class,
            'role' => EnsureRole::class,
            'platform.team' => EnsurePlatformTeam::class,
            'resolve.tenant.or.platform' => ResolveTenantOrPlatformTeam::class,
            'module' => EnsureModuleActif::class,
            'user.actif' => EnsureUserActif::class,
        ]);

        // Sans ceci, SubstituteBindings (résolution des {eleve}, {classe}, ...)
        // s'exécute avant ResolveTenant sur les routes api.php : le TenantScope
        // ne filtre alors rien (currentTenantId pas encore lié) et le binding
        // implicite de route peut résoudre un enregistrement d'un AUTRE tenant.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

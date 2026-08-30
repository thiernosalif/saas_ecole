<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $personne = $request->user()?->personne;

        abort_if($personne && ! $personne->actif, 403, 'Compte désactivé.');

        return $next($request);
    }
}

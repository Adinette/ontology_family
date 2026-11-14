<?php

namespace App\Http\Middleware;

use App\Services\Family\FamilyService;
use Closure;
use Illuminate\Http\Request;

/**
 * Au premier appel, si Fuseki est vide (dataset en mémoire fraîchement lancé),
 * l'ontologie du projet est chargée automatiquement : le professeur n'a rien à importer à la main.
 */
class EnsureOntologyLoaded
{
    public function __construct(private FamilyService $family)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->family->ensureLoaded();

        return $next($request);
    }
}

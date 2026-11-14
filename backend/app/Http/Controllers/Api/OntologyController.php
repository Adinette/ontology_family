<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OntologyController extends Controller
{
    public function __construct(private FamilyService $family)
    {
    }

    /** GET /api/health : l'API et Fuseki répondent-ils ? */
    public function health(): JsonResponse
    {
        $health = $this->family->health();

        return response()->json($health, $health['reachable'] ? 200 : 503);
    }

    /** GET /api/ontology : classes, propriétés et axiomes (inverse, symétrique, chaîne...) */
    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->family->ontology()]);
    }

    /** GET /api/export/turtle : télécharge toute la base (ontologie + membres) au format Turtle */
    public function exportTurtle(): Response
    {
        return response($this->family->exportTurtle(), 200, [
            'Content-Type'        => 'text/turtle; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="famille-dossou.ttl"',
        ]);
    }

    /** POST /api/reset : recharge l'ontologie d'origine (efface les membres ajoutés) */
    public function reset(): JsonResponse
    {
        if (!config('fuseki.allow_reset')) {
            throw new ApiException('La réinitialisation est désactivée (FUSEKI_ALLOW_RESET=false).', 403);
        }
        $triples = $this->family->importOntology();

        return response()->json(['message' => 'Base réinitialisée.', 'triples' => $triples]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SparqlController extends Controller
{
    public function __construct(private FamilyService $family)
    {
    }

    /** GET /api/sparql/presets : requêtes d'exemple pour la console */
    public function presets(): JsonResponse
    {
        return response()->json(['data' => $this->family->presets()]);
    }

    /** POST /api/sparql  { "query": "SELECT ..." } : exécute une requête SELECT/ASK (lecture seule) */
    public function run(Request $request): JsonResponse
    {
        $input = $request->validate(['query' => 'required|string|max:10000']);

        return response()->json(['data' => $this->family->runSparql($input['query'])]);
    }
}

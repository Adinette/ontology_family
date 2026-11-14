<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Family\FamilyQueries;
use App\Services\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RelationController extends Controller
{
    public function __construct(private FamilyService $family)
    {
    }

    /** GET /api/members/{id}/relations : toutes les relations d'un membre (père, mère, fratrie, oncles, cousins...) */
    public function relations(string $id): JsonResponse
    {
        return response()->json(['data' => $this->family->relationsOf($id)]);
    }

    /** GET /api/search/types : liste des relations interrogeables */
    public function types(): JsonResponse
    {
        return response()->json(['data' => $this->family->searchTypes()]);
    }

    /** GET /api/search?person=ID&relation=uncles_aunts */
    public function search(Request $request): JsonResponse
    {
        $input = $request->validate([
            'person'   => 'required|string|max:120',
            'relation' => ['required', 'string', Rule::in(array_keys(FamilyQueries::RELATIONS))],
        ]);

        return response()->json(['data' => $this->family->search($input['relation'], $input['person'])]);
    }

    /** GET /api/relationship?from=ID&to=ID : quel est le lien entre A et B ? */
    public function relationship(Request $request): JsonResponse
    {
        $input = $request->validate([
            'from' => 'required|string|max:120',
            'to'   => 'required|string|max:120',
        ]);

        return response()->json(['data' => $this->family->relationship($input['from'], $input['to'])]);
    }
}

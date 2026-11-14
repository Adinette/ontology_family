<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Family\FamilyService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /** GET /api/stats : chiffres du tableau de bord (membres, hommes, femmes, générations, relations) */
    public function __invoke(FamilyService $family): JsonResponse
    {
        return response()->json(['data' => $family->stats()]);
    }
}

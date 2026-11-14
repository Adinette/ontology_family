<?php

use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\OntologyController;
use App\Http\Controllers\Api\RelationController;
use App\Http\Controllers\Api\SparqlController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Middleware\EnsureOntologyLoaded;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API « Ontologie familiale » — préfixe automatique : /api
|--------------------------------------------------------------------------
*/

Route::middleware([ForceJsonResponse::class, 'throttle:240,1'])->group(function () {

    // Santé : accessible même si Fuseki est arrêté
    Route::get('health', [OntologyController::class, 'health']);

    // Tout le reste charge l'ontologie dans Fuseki si la base est vide
    Route::middleware(EnsureOntologyLoaded::class)->group(function () {

        // Membres (CRUD)
        Route::get('members', [MemberController::class, 'index']);
        Route::post('members', [MemberController::class, 'store']);
        Route::get('members/{id}', [MemberController::class, 'show'])->where('id', '[A-Za-z0-9_\-]+');
        Route::match(['put', 'patch'], 'members/{id}', [MemberController::class, 'update'])->where('id', '[A-Za-z0-9_\-]+');
        Route::delete('members/{id}', [MemberController::class, 'destroy'])->where('id', '[A-Za-z0-9_\-]+');

        // Relations déduites
        Route::get('members/{id}/relations', [RelationController::class, 'relations'])->where('id', '[A-Za-z0-9_\-]+');
        Route::get('search/types', [RelationController::class, 'types']);
        Route::get('search', [RelationController::class, 'search']);
        Route::get('relationship', [RelationController::class, 'relationship']);

        // Tableau de bord
        Route::get('stats', StatsController::class);

        // SPARQL
        Route::get('sparql/presets', [SparqlController::class, 'presets']);
        Route::post('sparql', [SparqlController::class, 'run']);

        // Ontologie
        Route::get('ontology', [OntologyController::class, 'summary']);
        Route::get('export/turtle', [OntologyController::class, 'exportTurtle']);
        Route::post('reset', [OntologyController::class, 'reset']);
    });
});

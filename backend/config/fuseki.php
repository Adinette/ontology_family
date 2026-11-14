<?php

return [
    // URL du serveur Fuseki et nom du dataset (voir README)
    'url'      => rtrim(env('FUSEKI_URL', 'http://localhost:3030'), '/'),
    'dataset'  => env('FUSEKI_DATASET', 'famille'),

    // Optionnel : authentification HTTP Basic si Fuseki est protégé
    'user'     => env('FUSEKI_USER'),
    'password' => env('FUSEKI_PASSWORD'),

    'timeout'  => (int) env('FUSEKI_TIMEOUT', 15),

    // Espace de noms de l'ontologie (celui du fichier OWL de Protégé, suivi de '#')
    'namespace' => env('ONTOLOGY_NAMESPACE', 'http://www.semanticweb.org/dossou/ontologies/2026/famille-dossou#'),

    // Fichier Turtle chargé dans Fuseki (relatif à la racine du projet Laravel)
    'ontology_file' => env('ONTOLOGY_FILE', 'ontology/famille-dossou.ttl'),

    // Autorise POST /api/reset (réinitialisation de la base) — pratique pour la démo
    'allow_reset' => (bool) env('FUSEKI_ALLOW_RESET', true),
];

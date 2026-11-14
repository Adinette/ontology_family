<?php

namespace App\Console\Commands;

use App\Services\Family\FamilyService;
use Illuminate\Console\Command;
use Throwable;

class ImportOntology extends Command
{
    protected $signature = 'ontology:import';

    protected $description = "Charge (ou recharge) l'ontologie de la famille dans Apache Jena Fuseki";

    public function handle(FamilyService $family): int
    {
        try {
            $triples = $family->importOntology();
        } catch (Throwable $e) {
            $this->error('Échec : ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Ontologie chargée dans Fuseki : {$triples} triplets.");

        return self::SUCCESS;
    }
}

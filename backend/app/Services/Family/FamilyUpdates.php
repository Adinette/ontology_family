<?php

namespace App\Services\Family;

/**
 * Construit les requêtes SPARQL Update (écriture) : ajout, modification, suppression d'un membre.
 * Aucune logique HTTP, uniquement des chaînes SPARQL.
 */
class FamilyUpdates
{
    /** Prédicats "enfant -> parent" et "parent -> enfant" présents dans l'ontologie. */
    private const CHILD_PREDICATES = ['enfantDe', 'enfantDe1', 'enfantDe2', 'enfantDe3'];
    private const PARENT_PREDICATES = ['parentDe', 'parentDe1', 'parentDe2', 'parentDe3'];
    private const MARRIAGE_PREDICATES = ['marieA', 'marieA1', 'marieA2', 'marieA3', 'marieA4'];

    /** propriété de données => [clé du tableau, type] */
    private const DATA_PROPERTIES = [
        'prenom'         => ['firstName', 'string'],
        'nom'            => ['lastName', 'string'],
        'nomMarital'     => ['marriedName', 'string'],
        'age'            => ['age', 'int'],
        'estDecede'      => ['isDeceased', 'boolean'],
        'anneeNaissance' => ['birthYear', 'int'],
        'anneeDeces'     => ['deathYear', 'int'],
        'profession'     => ['profession', 'string'],
        'avatar'         => ['avatar', 'string'],
    ];

    public function __construct(private FamilyQueries $q)
    {
    }

    /** Identifiant (fragment d'IRI) : « Marie-Heureuse », « Dossou » => MarieHeureuse_DOSSOU. */
    public function makeId(string $firstName, string $lastName): string
    {
        $first = preg_replace('/[^A-Za-z0-9]/', '', $this->ascii($firstName));
        $last = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->ascii($lastName)));
        $id = $last !== '' ? "{$first}_{$last}" : $first;

        return $id !== '' ? $id : 'Personne';
    }

    public function ascii(string $s): string
    {
        return strtr($s, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ú' => 'u', 'ù' => 'u',
            'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae',
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Á' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ñ' => 'N', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ù' => 'U',
            'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Œ' => 'OE', 'Æ' => 'AE',
        ]);
    }

    // ------------------------------------------------------------------ littéraux

    private function str(string $s): string
    {
        $s = str_replace(['\\', '"', "\r", "\n", "\t"], ['\\\\', '\\"', '\\r', '\\n', '\\t'], $s);

        return '"' . $s . '"';
    }

    private function literal(string $type, mixed $value): string
    {
        return match ($type) {
            'int'     => '"' . (int) $value . '"^^xsd:int',
            'boolean' => '"' . ($value ? 'true' : 'false') . '"^^xsd:boolean',
            default   => $this->str((string) $value),
        };
    }

    // ------------------------------------------------------------------ écriture

    /** INSERT DATA d'un nouveau membre. $d : firstName, lastName, gender (M|F), + champs facultatifs. */
    public function insertMember(string $id, array $d): string
    {
        $me = $this->q->iri($id);
        $class = ($d['gender'] ?? 'M') === 'F' ? ':Feminin' : ':Masculin';

        $triples = ["  $me a owl:NamedIndividual , $class"];
        foreach (self::DATA_PROPERTIES as $prop => [$key, $type]) {
            if (isset($d[$key]) && $d[$key] !== '' && $d[$key] !== null) {
                $triples[] = "    :$prop " . $this->literal($type, $d[$key]);
            }
        }
        $body = implode(" ;\n", $triples) . ' .';

        foreach (['fatherId', 'motherId'] as $key) {
            if (!empty($d[$key])) {
                $body .= "\n  $me :enfantDe " . $this->q->iri($d[$key]) . ' .';
            }
        }
        if (!empty($d['spouseId'])) {
            $body .= "\n  $me :marieA " . $this->q->iri($d['spouseId']) . ' .';
        }

        return $this->q->prefixes() . "INSERT DATA {\n$body\n}";
    }

    /** Mise à jour partielle : seules les clés présentes dans $d sont modifiées (null = effacer). */
    public function updateMember(string $id, array $d): string
    {
        $me = $this->q->iri($id);
        $ops = [];

        foreach (self::DATA_PROPERTIES as $prop => [$key, $type]) {
            if (!array_key_exists($key, $d)) {
                continue;
            }
            $ops[] = "DELETE WHERE { $me :$prop ?o }";
            if ($d[$key] !== null && $d[$key] !== '') {
                $ops[] = "INSERT DATA { $me :$prop " . $this->literal($type, $d[$key]) . ' }';
            }
        }

        if (array_key_exists('gender', $d) && in_array($d['gender'], ['M', 'F'], true)) {
            $ops[] = "DELETE WHERE { $me a :Masculin }";
            $ops[] = "DELETE WHERE { $me a :Feminin }";
            $ops[] = "INSERT DATA { $me a " . ($d['gender'] === 'F' ? ':Feminin' : ':Masculin') . ' }';
        }

        foreach (['fatherId' => ':Masculin', 'motherId' => ':Feminin'] as $key => $class) {
            if (!array_key_exists($key, $d)) {
                continue;
            }
            foreach (self::CHILD_PREDICATES as $pred) {
                $ops[] = "DELETE { $me :$pred ?x } WHERE { $me :$pred ?x . ?x a $class }";
            }
            foreach (self::PARENT_PREDICATES as $pred) {
                $ops[] = "DELETE { ?x :$pred $me } WHERE { ?x :$pred $me . ?x a $class }";
            }
            if (!empty($d[$key])) {
                $ops[] = "INSERT DATA { $me :enfantDe " . $this->q->iri($d[$key]) . ' }';
            }
        }

        if (array_key_exists('spouseId', $d)) {
            foreach (self::MARRIAGE_PREDICATES as $pred) {
                $ops[] = "DELETE WHERE { $me :$pred ?x }";
                $ops[] = "DELETE WHERE { ?x :$pred $me }";
            }
            if (!empty($d['spouseId'])) {
                $ops[] = "INSERT DATA { $me :marieA " . $this->q->iri($d['spouseId']) . ' }';
            }
        }

        return $ops ? $this->q->prefixes() . implode(" ;\n", $ops) : '';
    }

    /** Supprime l'individu et toutes les relations où il apparaît. */
    public function deleteMember(string $id): string
    {
        $me = $this->q->iri($id);

        return $this->q->prefixes() . "DELETE WHERE { $me ?p ?o } ;\nDELETE WHERE { ?s ?p $me }";
    }
}

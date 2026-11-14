<?php

namespace App\Services\Family;

use App\Exceptions\ApiException;
use App\Services\Fuseki\FusekiClient;

/**
 * Cœur de l'application : transforme les résultats SPARQL (Fuseki) en objets JSON
 * au format attendu par le frontend Vue.js (voir README, section « Format d'un membre »).
 */
class FamilyService
{
    private FamilyQueries $q;
    private FamilyUpdates $u;

    /** @var array<string,array>|null cache des membres pour la durée de la requête HTTP */
    private ?array $members = null;
    /** @var array<int,array{0:string,1:string}>|null couples (enfant, parent) */
    private ?array $parentLinks = null;

    private const DEFAULT_AVATAR = [
        'M' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160&auto=format&fit=crop&q=80',
        'F' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=160&auto=format&fit=crop&q=80',
    ];

    public function __construct(private FusekiClient $fuseki)
    {
        $this->q = new FamilyQueries((string) config('fuseki.namespace'));
        $this->u = new FamilyUpdates($this->q);
    }

    // =====================================================================
    // Lecture des membres
    // =====================================================================

    /** @return array<string,array> id => membre */
    public function members(): array
    {
        if ($this->members !== null) {
            return $this->members;
        }

        $persons = $this->bindings($this->fuseki->select($this->q->allPersons()));
        $parentRows = $this->bindings($this->fuseki->select($this->q->allParentLinks()));
        $spouseRows = $this->bindings($this->fuseki->select($this->q->allSpouseLinks()));

        $year = (int) date('Y');
        $members = [];
        foreach ($persons as $row) {
            $id = $this->local($row['p']['value']);
            $genreIri = $row['genre']['value'] ?? '';
            $gender = str_ends_with($genreIri, 'Feminin') ? 'F' : (str_ends_with($genreIri, 'Masculin') ? 'M' : ($members[$id]['gender'] ?? 'M'));
            $age = isset($row['age']) ? (int) $row['age']['value'] : null;
            $first = $row['prenom']['value'];
            $last = $row['nom']['value'] ?? '';

            $members[$id] = [
                'id'          => $id,
                'name'        => trim($first . ' ' . $last),
                'firstName'   => $first,
                'lastName'    => $last,
                'marriedName' => $row['nomMarital']['value'] ?? null,
                'gender'      => $gender,
                'age'         => $age,
                'birthYear'   => isset($row['anneeNaissance']) ? (int) $row['anneeNaissance']['value'] : ($age !== null ? $year - $age : null),
                'deathYear'   => isset($row['anneeDeces']) ? (int) $row['anneeDeces']['value'] : null,
                'isDeceased'  => ($row['decede']['value'] ?? 'false') === 'true',
                'avatar'      => $row['avatar']['value'] ?? self::DEFAULT_AVATAR[$gender],
                'profession'  => $row['profession']['value'] ?? 'Membre de la famille',
                'fatherId'    => null,
                'motherId'    => null,
                'spouseId'    => null,
                'spouseIds'   => [],
                'generation'  => 1,
            ];
        }

        // Parents : père = parent masculin, mère = parent féminin
        $this->parentLinks = [];
        $parentsOf = [];
        foreach ($parentRows as $row) {
            $child = $this->local($row['child']['value']);
            $parent = $this->local($row['parent']['value']);
            if (!isset($members[$child], $members[$parent])) {
                continue;
            }
            $parentsOf[$child][$parent] = true;
            $this->parentLinks[] = [$child, $parent];
        }
        foreach ($parentsOf as $child => $set) {
            $ids = array_keys($set);
            sort($ids);
            foreach ($ids as $pid) {
                if ($members[$pid]['gender'] === 'M' && $members[$child]['fatherId'] === null) {
                    $members[$child]['fatherId'] = $pid;
                } elseif ($members[$pid]['gender'] === 'F' && $members[$child]['motherId'] === null) {
                    $members[$child]['motherId'] = $pid;
                }
            }
        }

        // Conjoints : spouseId = le conjoint vivant s'il y en a un, sinon le premier
        $spousesOf = [];
        foreach ($spouseRows as $row) {
            $a = $this->local($row['a']['value']);
            $b = $this->local($row['b']['value']);
            if (isset($members[$a], $members[$b])) {
                $spousesOf[$a][$b] = true;
            }
        }
        foreach ($spousesOf as $id => $set) {
            $ids = array_keys($set);
            sort($ids);
            $members[$id]['spouseIds'] = $ids;
            $living = array_values(array_filter($ids, fn ($s) => !$members[$s]['isDeceased']));
            $members[$id]['spouseId'] = $living[0] ?? $ids[0];
        }

        // Générations
        foreach ($this->computeGenerations($members, $parentsOf, $spousesOf) as $id => $gen) {
            $members[$id]['generation'] = $gen;
        }

        return $this->members = $members;
    }

    /**
     * Génération = 1 + max(génération des parents). Une personne sans parent connu (conjoint "entrant")
     * prend la génération de son conjoint si celui-ci a des parents, sinon 1.
     *
     * @return array<string,int>
     */
    private function computeGenerations(array $members, array $parentsOf, array $spousesOf): array
    {
        $memo = [];
        $gens = [];
        foreach (array_keys($members) as $id) {
            $gens[$id] = $this->generationOf($id, $parentsOf, $spousesOf, $memo, []);
        }

        return $gens;
    }

    private function generationOf(string $id, array $parentsOf, array $spousesOf, array &$memo, array $stack): int
    {
        if (isset($memo[$id])) {
            return $memo[$id];
        }
        if (isset($stack[$id])) {
            return 1; // cycle dans les données : on arrête la récursion
        }
        $stack[$id] = true;

        $generation = 1;
        if (!empty($parentsOf[$id])) {
            $best = 0;
            foreach (array_keys($parentsOf[$id]) as $parent) {
                $best = max($best, $this->generationOf($parent, $parentsOf, $spousesOf, $memo, $stack));
            }
            $generation = $best + 1;
        } else {
            foreach (array_keys($spousesOf[$id] ?? []) as $spouse) {
                if (!empty($parentsOf[$spouse])) {
                    $generation = max($generation, $this->generationOf($spouse, $parentsOf, $spousesOf, $memo, $stack));
                }
            }
        }

        return $memo[$id] = $generation;
    }

    /** @return array<int,array> membres filtrés et triés (génération, puis nom) */
    public function listMembers(array $filters = []): array
    {
        $list = array_values($this->members());

        if (!empty($filters['gender'])) {
            $list = array_values(array_filter($list, fn ($m) => $m['gender'] === $filters['gender']));
        }
        if (!empty($filters['generation'])) {
            $list = array_values(array_filter($list, fn ($m) => $m['generation'] === (int) $filters['generation']));
        }
        if (!empty($filters['q'])) {
            $needle = mb_strtolower($this->u->ascii(trim($filters['q'])));
            $list = array_values(array_filter($list, function ($m) use ($needle) {
                $hay = mb_strtolower($this->u->ascii($m['name'] . ' ' . ($m['marriedName'] ?? '') . ' ' . $m['profession']));

                return str_contains($hay, $needle);
            }));
        }
        usort($list, fn ($a, $b) => [$a['generation'], $a['name']] <=> [$b['generation'], $b['name']]);

        return $list;
    }

    public function member(string $id): array
    {
        $members = $this->members();
        if (!isset($members[$id])) {
            throw new ApiException("Membre introuvable : {$id}", 404);
        }

        return $members[$id];
    }

    // =====================================================================
    // Relations déduites (SPARQL)
    // =====================================================================

    /** @return string[] identifiants des personnes liées à $id par $relation */
    public function relationIds(string $relation, string $id): array
    {
        $this->member($id); // 404 si inconnu
        $rows = $this->bindings($this->fuseki->select($this->q->relationSelect($relation, $id)));
        $members = $this->members();
        $ids = [];
        foreach ($rows as $row) {
            $rid = $this->local($row['r']['value']);
            if (isset($members[$rid])) {
                $ids[] = $rid;
            }
        }

        return array_values(array_unique($ids));
    }

    /** @return array<int,array> membres liés, triés par génération puis nom */
    public function relationMembers(string $relation, string $id): array
    {
        $members = $this->members();
        $list = array_map(fn ($rid) => $members[$rid], $this->relationIds($relation, $id));
        usort($list, fn ($a, $b) => [$a['generation'], $a['name']] <=> [$b['generation'], $b['name']]);

        return $list;
    }

    /** Toutes les relations d'un membre (même structure que le store du frontend). */
    public function relationsOf(string $id): array
    {
        $person = $this->member($id);
        $members = $this->members();

        $out = ['person' => $person];
        $out['father'] = $person['fatherId'] ? $members[$person['fatherId']] : null;
        $out['mother'] = $person['motherId'] ? $members[$person['motherId']] : null;
        $out['spouse'] = $person['spouseId'] ? $members[$person['spouseId']] : null;
        $out['spouses'] = array_map(fn ($s) => $members[$s], $person['spouseIds']);

        $map = [
            'parents' => 'parents', 'children' => 'children', 'siblings' => 'siblings',
            'brothers' => 'brothers', 'sisters' => 'sisters', 'grandparents' => 'grandparents',
            'grandchildren' => 'grandchildren', 'unclesAndAunts' => 'uncles_aunts',
            'nephewsAndNieces' => 'nephews_nieces', 'cousins' => 'cousins',
            'ancestors' => 'ancestors', 'descendants' => 'descendants',
            'stepParents' => 'step_parents', 'stepChildren' => 'step_children',
        ];
        foreach ($map as $key => $relation) {
            $out[$key] = $this->relationMembers($relation, $id);
        }
        $out['sons'] = array_values(array_filter($out['children'], fn ($m) => $m['gender'] === 'M'));
        $out['daughters'] = array_values(array_filter($out['children'], fn ($m) => $m['gender'] === 'F'));

        return $out;
    }

    /** Recherche sémantique « Trouver les [relation] de [personne] » + requête SPARQL exécutée. */
    public function search(string $relation, string $id): array
    {
        $this->assertRelation($relation);
        $person = $this->member($id);
        $results = $this->relationMembers($relation, $id);
        $meta = FamilyQueries::RELATIONS[$relation];

        return [
            'relation' => $relation,
            'label'    => $meta['label'],
            'rule'     => $meta['rule'],
            'person'   => $person,
            'count'    => count($results),
            'results'  => $results,
            'sparql'   => $this->q->relationSelect($relation, $id),
        ];
    }

    public function searchTypes(): array
    {
        $out = [];
        foreach (FamilyQueries::RELATIONS as $key => $meta) {
            $out[] = ['value' => $key, 'label' => $meta['label'], 'rule' => $meta['rule']];
        }

        return $out;
    }

    /** « Quel est le lien entre A et B ? » */
    public function relationship(string $aId, string $bId): array
    {
        $a = $this->member($aId);
        $b = $this->member($bId);

        if ($aId === $bId) {
            return ['found' => false, 'from' => $a, 'to' => $b, 'relation' => null, 'label' => null,
                'sentence' => 'Il s\'agit de la même personne.', 'rule' => null, 'sparql' => null];
        }

        foreach (FamilyQueries::DETECTION_ORDER as $relation) {
            $ask = $this->q->relationAsk($relation, $aId, $bId);
            if ($this->fuseki->ask($ask)) {
                $meta = FamilyQueries::RELATIONS[$relation];
                $word = $meta['words'][$a['gender']] ?? $meta['words']['default'];

                return [
                    'found'    => true,
                    'from'     => $a,
                    'to'       => $b,
                    'relation' => $relation,
                    'label'    => $word,
                    'sentence' => "{$a['name']} est " . $this->article($word, $a['gender']) . "{$word} de {$b['name']}.",
                    'rule'     => $meta['rule'],
                    'sparql'   => $ask,
                ];
            }
        }

        $ask = $this->q->commonAncestorAsk($aId, $bId);
        $common = $this->fuseki->ask($ask);

        return [
            'found'    => $common,
            'from'     => $a,
            'to'       => $b,
            'relation' => $common ? 'common_ancestor' : null,
            'label'    => $common ? 'parenté éloignée' : null,
            'sentence' => $common
                ? "{$a['name']} et {$b['name']} descendent d'un ancêtre commun (parenté éloignée)."
                : "Aucun lien de parenté trouvé entre {$a['name']} et {$b['name']} dans la base.",
            'rule'     => $common ? "Les deux personnes ont au moins un ancêtre commun (chemin enfantDe+)." : null,
            'sparql'   => $ask,
        ];
    }

    private function article(string $word, string $gender): string
    {
        if (preg_match('/^[aeiouyhàâéèêîôû]/iu', $word)) {
            return "l'";
        }

        return $gender === 'F' ? 'la ' : 'le ';
    }

    private function assertRelation(string $relation): void
    {
        if (!isset(FamilyQueries::RELATIONS[$relation])) {
            throw new ApiException(
                'Relation inconnue : ' . $relation,
                422,
                ['relation' => ['Valeurs possibles : ' . implode(', ', array_keys(FamilyQueries::RELATIONS))]]
            );
        }
    }

    // =====================================================================
    // Statistiques (tableau de bord / raisonnement)
    // =====================================================================

    public function stats(): array
    {
        $members = $this->members();
        $list = array_values($members);

        $generations = [];
        foreach ($list as $m) {
            $generations[$m['generation']] = ($generations[$m['generation']] ?? 0) + 1;
        }
        ksort($generations);

        $ages = array_filter(array_column($list, 'age'), fn ($a) => $a !== null);

        $breakdown = [];
        foreach (FamilyQueries::INFERRED_COUNTED as $relation) {
            $breakdown[$relation] = $this->scalar($this->q->relationCount($relation));
        }

        $parentIds = array_unique(array_column($this->parentLinks ?? [], 1));
        $links = count($this->parentLinks ?? []);

        return [
            'totalMembers'           => count($list),
            'menCount'               => count(array_filter($list, fn ($m) => $m['gender'] === 'M')),
            'womenCount'             => count(array_filter($list, fn ($m) => $m['gender'] === 'F')),
            'deceasedCount'          => count(array_filter($list, fn ($m) => $m['isDeceased'])),
            'averageAge'             => $ages ? round(array_sum($ages) / count($ages), 1) : null,
            'generationCount'        => max(count($generations), 1),
            'generations'            => $generations,
            'assertedRelationsCount' => $this->scalar($this->q->assertedRelationCount()),
            'inferredRelationsCount' => array_sum($breakdown),
            'inferredBreakdown'      => $breakdown,
            'parentsCount'           => count($parentIds),
            'avgChildren'            => $parentIds ? round($links / count($parentIds), 1) : 0,
        ];
    }

    // =====================================================================
    // Écriture
    // =====================================================================

    public function createMember(array $data): array
    {
        $data = $this->normalize($data);
        if (($data['firstName'] ?? '') === '') {
            throw new ApiException('Le prénom (ou le nom complet) est obligatoire.', 422, ['name' => ['Prénom manquant.']]);
        }
        $this->checkLinks(null, $data);

        $members = $this->members();
        $base = $this->u->makeId($data['firstName'], $data['lastName'] ?? '');
        $id = $base;
        for ($i = 2; isset($members[$id]); $i++) {
            $id = "{$base}_{$i}";
        }

        $this->fuseki->update($this->u->insertMember($id, $data));
        $this->forget();

        return $this->member($id);
    }

    public function updateMember(string $id, array $data): array
    {
        $this->member($id);
        $data = $this->normalize($data, true);
        $this->checkLinks($id, $data);

        $update = $this->u->updateMember($id, $data);
        if ($update !== '') {
            $this->fuseki->update($update);
            $this->forget();
        }

        return $this->member($id);
    }

    public function deleteMember(string $id): void
    {
        $this->member($id);
        $this->fuseki->update($this->u->deleteMember($id));
        $this->forget();
    }

    /** Uniformise les clés reçues (le frontend envoie "name", "gender", "fatherId"...). */
    private function normalize(array $in, bool $partial = false): array
    {
        $d = [];
        $copy = ['firstName', 'lastName', 'marriedName', 'gender', 'age', 'birthYear', 'deathYear',
            'isDeceased', 'profession', 'avatar', 'fatherId', 'motherId', 'spouseId'];
        foreach ($copy as $key) {
            if (array_key_exists($key, $in)) {
                $d[$key] = $in[$key] === '' ? null : $in[$key];
            }
        }
        // nom complet "Prénom(s) NOM" : le dernier mot est le nom de famille
        if (!empty($in['name']) && !isset($d['firstName']) && !isset($d['lastName'])) {
            $parts = preg_split('/\s+/u', trim($in['name']));
            if (count($parts) > 1) {
                $d['lastName'] = array_pop($parts);
            }
            $d['firstName'] = implode(' ', $parts);
        }
        // âge / année de naissance cohérents
        if (!$partial) {
            $year = (int) date('Y');
            if (!isset($d['age']) && isset($d['birthYear'])) {
                $d['age'] = $year - (int) $d['birthYear'];
            }
        }

        return $d;
    }

    /** Vérifie l'existence, le sexe et l'absence de cycle des liens père / mère / conjoint. */
    private function checkLinks(?string $selfId, array $d): void
    {
        $members = $this->members();
        $errors = [];

        foreach (['fatherId' => 'M', 'motherId' => 'F'] as $key => $gender) {
            $pid = $d[$key] ?? null;
            if (!$pid) {
                continue;
            }
            if (!isset($members[$pid])) {
                $errors[$key][] = "Membre introuvable : {$pid}";
            } elseif ($members[$pid]['gender'] !== $gender) {
                $errors[$key][] = $key === 'fatherId' ? 'Le père doit être un homme.' : 'La mère doit être une femme.';
            } elseif ($selfId !== null && ($pid === $selfId || in_array($pid, $this->relationIds('descendants', $selfId), true))) {
                $errors[$key][] = 'Un membre ne peut pas être son propre ascendant.';
            }
        }

        $sid = $d['spouseId'] ?? null;
        if ($sid) {
            if (!isset($members[$sid])) {
                $errors['spouseId'][] = "Membre introuvable : {$sid}";
            } elseif ($sid === $selfId) {
                $errors['spouseId'][] = 'Un membre ne peut pas être son propre conjoint.';
            }
        }

        if (isset($d['gender']) && !in_array($d['gender'], ['M', 'F'], true)) {
            $errors['gender'][] = 'Valeurs possibles : M, F.';
        }

        if ($errors) {
            throw new ApiException('Données invalides.', 422, $errors);
        }
    }

    // =====================================================================
    // Console SPARQL
    // =====================================================================

    public function presets(): array
    {
        return $this->q->presets();
    }

    /** Exécute une requête SELECT/ASK saisie par l'utilisateur (lecture seule). */
    public function runSparql(string $query): array
    {
        $query = trim($query);
        if ($query === '' || strlen($query) > 10000) {
            throw new ApiException('Requête vide ou trop longue (10 000 caractères maximum).', 422);
        }

        // On retire l'en-tête (PREFIX / BASE / commentaires) pour trouver le type de requête
        $rest = $query;
        do {
            $before = $rest;
            $rest = preg_replace('/^\s*(PREFIX\s+[^\s:]*:\s*<[^>]*>|BASE\s*<[^>]*>|#[^\n]*)/i', '', $rest, 1);
        } while ($rest !== $before);
        if (!preg_match('/^\s*(SELECT|ASK)\b/i', $rest)) {
            throw new ApiException('Seules les requêtes SELECT et ASK sont autorisées dans la console (lecture seule).', 422);
        }

        // Préfixes usuels ajoutés automatiquement s'ils ne sont pas déclarés
        $ns = $this->q->ns();
        $defaults = [
            ''     => $ns,
            'rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'owl'  => 'http://www.w3.org/2002/07/owl#',
            'xsd'  => 'http://www.w3.org/2001/XMLSchema#',
        ];
        $header = '';
        foreach ($defaults as $prefix => $iri) {
            if (!preg_match('/PREFIX\s+' . preg_quote($prefix, '/') . '\s*:/i', $query)) {
                $header .= "PREFIX {$prefix}: <{$iri}>\n";
            }
        }
        $final = $header . $query;

        $start = microtime(true);
        $json = $this->fuseki->select($final);
        $ms = (int) round((microtime(true) - $start) * 1000);

        if (array_key_exists('boolean', $json)) {
            return ['type' => 'ask', 'boolean' => (bool) $json['boolean'], 'executionMs' => $ms, 'query' => $final];
        }

        $vars = $json['head']['vars'] ?? [];
        $rows = [];
        foreach ($this->bindings($json) as $b) {
            $row = [];
            foreach ($vars as $v) {
                $row[$v] = isset($b[$v]) ? $this->display($b[$v]) : null;
            }
            $rows[] = $row;
        }

        return ['type' => 'select', 'variables' => $vars, 'rows' => $rows, 'count' => count($rows), 'executionMs' => $ms, 'query' => $final];
    }

    // =====================================================================
    // Ontologie, import / export, santé
    // =====================================================================

    /** Résumé du schéma OWL (classes, propriétés, axiomes) pour la vue « Raisonnement ». */
    public function ontology(): array
    {
        $counts = $this->bindings($this->fuseki->select($this->q->ontologyCounts()))[0] ?? [];

        $classes = [];
        foreach ($this->bindings($this->fuseki->select($this->q->ontologyClasses())) as $row) {
            $name = $this->local($row['c']['value']);
            $classes[$name] ??= ['name' => $name, 'subClassOf' => []];
            if (isset($row['super'])) {
                $classes[$name]['subClassOf'][] = $this->local($row['super']['value']);
            }
        }

        $props = [];
        foreach ($this->bindings($this->fuseki->select($this->q->ontologyProperties())) as $row) {
            $name = $this->local($row['p']['value']);
            $props[$name] ??= [
                'name' => $name, 'type' => $row['kind']['value'] === 'objet' ? 'object' : 'data',
                'inverseOf' => null, 'subPropertyOf' => null, 'domain' => null, 'range' => null,
                'symmetric' => false, 'transitive' => false, 'functional' => false,
            ];
            foreach (['inverse' => 'inverseOf', 'super' => 'subPropertyOf', 'domain' => 'domain', 'range' => 'range'] as $var => $key) {
                if (isset($row[$var])) {
                    $props[$name][$key] = $this->display($row[$var]);
                }
            }
            foreach (['symmetric', 'transitive', 'functional'] as $flag) {
                $props[$name][$flag] = ($row[$flag]['value'] ?? 'false') === 'true';
            }
        }
        $props = array_values($props);

        $chains = [];
        foreach ($this->bindings($this->fuseki->select($this->q->ontologyChains())) as $row) {
            $chains[] = [
                'chain'   => [$this->local($row['p1']['value']), $this->local($row['p2']['value'])],
                'implies' => $this->local($row['implied']['value']),
            ];
        }

        return [
            'iri'    => rtrim($this->q->ns(), '#'),
            'counts' => [
                'classes' => (int) ($counts['classes']['value'] ?? 0),
                'objectProperties' => (int) ($counts['objectProperties']['value'] ?? 0),
                'dataProperties' => (int) ($counts['dataProperties']['value'] ?? 0),
                'individuals' => (int) ($counts['individuals']['value'] ?? 0),
            ],
            'classes'    => array_values($classes),
            'properties' => $props,
            'axioms'     => [
                'inverse'    => array_values(array_map(fn ($p) => [$p['name'], $p['inverseOf']], array_filter($props, fn ($p) => $p['inverseOf']))),
                'symmetric'  => array_values(array_map(fn ($p) => $p['name'], array_filter($props, fn ($p) => $p['symmetric']))),
                'transitive' => array_values(array_map(fn ($p) => $p['name'], array_filter($props, fn ($p) => $p['transitive']))),
                'chains'     => $chains,
            ],
        ];
    }

    public function exportTurtle(): string
    {
        return $this->fuseki->exportTurtle();
    }

    /** (Re)charge l'ontologie du projet dans Fuseki. Renvoie le nombre de triplets. */
    public function importOntology(): int
    {
        $main = base_path((string) config('fuseki.ontology_file'));
        if (!is_file($main)) {
            throw new ApiException("Fichier d'ontologie introuvable : {$main}", 500);
        }
        $turtle = file_get_contents($main);

        $extension = dirname($main) . DIRECTORY_SEPARATOR . 'extension-app.ttl';
        if (is_file($extension)) {
            $turtle .= "\n\n" . file_get_contents($extension);
        }

        $this->fuseki->replaceWithTurtle($turtle);
        $this->forget();

        return $this->scalar($this->q->tripleCount());
    }

    /** Charge l'ontologie si le dataset Fuseki est vide (premier démarrage). */
    public function ensureLoaded(): bool
    {
        if ($this->fuseki->ask($this->q->anyTriple())) {
            return false;
        }
        $this->importOntology();

        return true;
    }

    public function health(): array
    {
        if (!$this->fuseki->isReachable()) {
            return ['status' => 'down', 'fuseki' => $this->fuseki->endpoint(), 'reachable' => false,
                'message' => 'Fuseki est injoignable : lancez le serveur (voir README).'];
        }
        $triples = $this->scalar($this->q->tripleCount());

        return ['status' => 'ok', 'fuseki' => $this->fuseki->endpoint(), 'reachable' => true,
            'triples' => $triples, 'loaded' => $triples > 0];
    }

    // =====================================================================
    // Outils internes
    // =====================================================================

    private function forget(): void
    {
        $this->members = null;
        $this->parentLinks = null;
    }

    /** @return array<int,array> liste des bindings SPARQL JSON */
    private function bindings(array $json): array
    {
        return $json['results']['bindings'] ?? [];
    }

    private function scalar(string $query): int
    {
        $row = $this->bindings($this->fuseki->select($query))[0] ?? null;

        return $row ? (int) ($row['n']['value'] ?? 0) : 0;
    }

    /** Partie locale d'une IRI de l'ontologie (Perseverance_DOSSOU). */
    private function local(string $iri): string
    {
        $ns = $this->q->ns();

        return str_starts_with($iri, $ns) ? substr($iri, strlen($ns)) : $iri;
    }

    /** Valeur lisible d'un terme SPARQL (IRI raccourcie, littéral, nœud blanc). */
    private function display(array $term): string
    {
        $value = $term['value'] ?? '';
        if (($term['type'] ?? '') === 'uri') {
            $ns = $this->q->ns();
            if (str_starts_with($value, $ns)) {
                return substr($value, strlen($ns));
            }
            foreach ([
                'rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'owl'  => 'http://www.w3.org/2002/07/owl#',
                'xsd'  => 'http://www.w3.org/2001/XMLSchema#',
            ] as $prefix => $iri) {
                if (str_starts_with($value, $iri)) {
                    return $prefix . ':' . substr($value, strlen($iri));
                }
            }

            return $value;
        }
        if (($term['type'] ?? '') === 'bnode') {
            return '_:' . $value;
        }

        return $value;
    }
}

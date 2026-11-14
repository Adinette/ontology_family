<?php

namespace App\Services\Family;

use InvalidArgumentException;

/**
 * Toutes les requêtes SPARQL du projet sont construites ici (aucune logique HTTP).
 *
 * Idée centrale : l'ontologie (Protégé) définit les relations de base
 * (enfantDe / parentDe, marieA, frereDe / soeurDe ...) et leurs sous-propriétés
 * (enfantDe1, enfantDe2, ...). Plutôt que de dépendre d'un raisonneur à configurer côté serveur,
 * les relations *déduites* (grands-parents, oncles, cousins ...) sont exprimées en SPARQL 1.1
 * avec des chemins de propriétés, en reprenant les définitions OWL de l'ontologie.
 */
class FamilyQueries
{
    /**
     * Relations interrogeables : clé => libellé, règle d'inférence (en français),
     * et formes du mot selon le sexe de la personne trouvée (pour les phrases).
     */
    public const RELATIONS = [
        'parents' => [
            'label' => 'les parents',
            'words' => ['M' => 'père', 'F' => 'mère', 'default' => 'parent'],
            'rule'  => "enfantDe est l'inverse de parentDe (owl:inverseOf) ; enfantDe1, enfantDe2, enfantDe3 sont des sous-propriétés de enfantDe.",
        ],
        'children' => [
            'label' => 'les enfants',
            'words' => ['M' => 'fils', 'F' => 'fille', 'default' => 'enfant'],
            'rule'  => "Inversion de la relation parent : si X est enfantDe Y alors Y est parentDe X (owl:inverseOf).",
        ],
        'spouse' => [
            'label' => 'le ou les conjoints',
            'words' => ['M' => 'époux', 'F' => 'épouse', 'default' => 'conjoint'],
            'rule'  => "marieA est une propriété symétrique (owl:SymmetricProperty) : si X marieA Y alors Y marieA X.",
        ],
        'siblings' => [
            'label' => 'les frères et sœurs',
            'words' => ['M' => 'frère', 'F' => 'sœur', 'default' => 'frère ou sœur'],
            'rule'  => "Classes Frere et Soeur : lien frereDe / soeurDe déclaré dans l'ontologie, ou parent commun (X enfantDe P et Y enfantDe P avec X ≠ Y).",
        ],
        'brothers' => [
            'label' => 'les frères',
            'words' => ['M' => 'frère', 'F' => 'frère', 'default' => 'frère'],
            'rule'  => "Frere ≡ Masculin ⊓ ∃frereDe.Personne.",
        ],
        'sisters' => [
            'label' => 'les sœurs',
            'words' => ['M' => 'sœur', 'F' => 'sœur', 'default' => 'sœur'],
            'rule'  => "Soeur ≡ Feminin ⊓ ∃soeurDe.Personne.",
        ],
        'grandparents' => [
            'label' => 'les grands-parents',
            'words' => ['M' => 'grand-père', 'F' => 'grand-mère', 'default' => 'grand-parent'],
            'rule'  => "Chaîne de propriétés : parentDe ∘ parentDe ⊑ grandParentDe ; GrandParent ≡ Personne ⊓ ∃parentDe.Parent.",
        ],
        'grandchildren' => [
            'label' => 'les petits-enfants',
            'words' => ['M' => 'petit-fils', 'F' => 'petite-fille', 'default' => 'petit-enfant'],
            'rule'  => "PetitEnfant ≡ Personne ⊓ ∃enfantDe.Enfant (enfant d'un enfant).",
        ],
        'uncles_aunts' => [
            'label' => 'les oncles et tantes',
            'words' => ['M' => 'oncle', 'F' => 'tante', 'default' => 'oncle ou tante'],
            'rule'  => "Oncle ≡ Masculin ⊓ ∃frereDe.Parent ; Tante ≡ Feminin ⊓ ∃soeurDe.Parent (frère ou sœur d'un parent, ou leur conjoint).",
        ],
        'uncles' => [
            'label' => 'les oncles',
            'words' => ['M' => 'oncle', 'F' => 'oncle', 'default' => 'oncle'],
            'rule'  => "Oncle ≡ Masculin ⊓ ∃frereDe.Parent.",
        ],
        'aunts' => [
            'label' => 'les tantes',
            'words' => ['M' => 'tante', 'F' => 'tante', 'default' => 'tante'],
            'rule'  => "Tante ≡ Feminin ⊓ ∃soeurDe.Parent.",
        ],
        'nephews_nieces' => [
            'label' => 'les neveux et nièces',
            'words' => ['M' => 'neveu', 'F' => 'nièce', 'default' => 'neveu ou nièce'],
            'rule'  => "Neveu ≡ Masculin ⊓ ∃enfantDe.(Frere ⊔ Soeur) ; Niece ≡ Feminin ⊓ ∃enfantDe.(Frere ⊔ Soeur) (enfants d'un frère ou d'une sœur, ou du frère/sœur du conjoint).",
        ],
        'cousins' => [
            'label' => 'les cousins et cousines',
            'words' => ['M' => 'cousin', 'F' => 'cousine', 'default' => 'cousin ou cousine'],
            'rule'  => "Cousin ≡ Masculin ⊓ ∃enfantDe.(Oncle ⊔ Tante) ; Cousine ≡ Feminin ⊓ ∃enfantDe.(Oncle ⊔ Tante).",
        ],
        'step_parents' => [
            'label' => 'les beaux-parents (parents par alliance)',
            'words' => ['M' => 'beau-père', 'F' => 'belle-mère', 'default' => 'beau-parent'],
            'rule'  => "BeauParent ≡ Personne ⊓ ∃beauParentDe.Personne ; beauParentDe est l'inverse de beauEnfantDe.",
        ],
        'step_children' => [
            'label' => 'les beaux-enfants',
            'words' => ['M' => 'beau-fils', 'F' => 'belle-fille', 'default' => 'beau-fils ou belle-fille'],
            'rule'  => "Inversion de beauParentDe (owl:inverseOf beauEnfantDe).",
        ],
        'ancestors' => [
            'label' => 'tous les ancêtres directs',
            'words' => ['M' => 'ancêtre', 'F' => 'ancêtre', 'default' => 'ancêtre'],
            'rule'  => "Fermeture transitive de la relation parent (chemin SPARQL enfantDe+).",
        ],
        'descendants' => [
            'label' => 'tous les descendants',
            'words' => ['M' => 'descendant', 'F' => 'descendante', 'default' => 'descendant'],
            'rule'  => "Fermeture transitive de la relation enfant (chemin SPARQL inverse de enfantDe+).",
        ],
    ];

    /** Ordre dans lequel on teste les liens pour trouver "la" relation entre deux personnes. */
    public const DETECTION_ORDER = [
        'parents', 'children', 'spouse', 'siblings', 'grandparents', 'grandchildren',
        'uncles_aunts', 'nephews_nieces', 'cousins', 'step_parents', 'step_children',
        'ancestors', 'descendants',
    ];

    /** Relations dont on compte les paires pour l'indicateur « relations déduites ». */
    public const INFERRED_COUNTED = ['children', 'siblings', 'grandparents', 'uncles_aunts', 'cousins'];

    public function __construct(private string $ns)
    {
    }

    public function ns(): string
    {
        return $this->ns;
    }

    // ------------------------------------------------------------------ utilitaires

    public function prefixes(): string
    {
        return "PREFIX : <{$this->ns}>\n"
            . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
            . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n"
            . "PREFIX owl: <http://www.w3.org/2002/07/owl#>\n"
            . "PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>\n";
    }

    /** IRI complète d'un individu à partir de son identifiant (ex. Perseverance_DOSSOU). */
    public function iri(string $id): string
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $id)) {
            throw new InvalidArgumentException("Identifiant invalide : {$id}");
        }

        return '<' . $this->ns . $id . '>';
    }

    /** Chemin : x --parent--> y (y est un parent de x). Couvre sous-propriétés et inverses. */
    public function parentPath(): string
    {
        return '(:enfantDe|:enfantDe1|:enfantDe2|:enfantDe3|^:parentDe|^:parentDe1|^:parentDe2|^:parentDe3)';
    }

    /** Chemin : x --conjoint--> y (propriété symétrique + sous-propriétés marieA1..4). */
    public function spousePath(): string
    {
        return '(:marieA|:marieA1|:marieA2|:marieA3|:marieA4|^:marieA|^:marieA1|^:marieA2|^:marieA3|^:marieA4)';
    }

    /** Chemin : x --fratrie--> y (frereDe / soeurDe dans les deux sens, ou parent commun). */
    public function siblingPath(): string
    {
        $p = $this->parentPath();

        return "(:frereDe|:soeurDe|^:frereDe|^:soeurDe|{$p}/^{$p})";
    }

    // ------------------------------------------------------------------ requêtes de lecture

    /** Toutes les personnes avec leurs propriétés de données. */
    public function allPersons(): string
    {
        return $this->prefixes() . <<<'SPARQL'
SELECT ?p ?prenom ?nom ?nomMarital ?age ?decede ?profession ?avatar ?anneeNaissance ?anneeDeces ?genre WHERE {
  ?p :prenom ?prenom .
  OPTIONAL { ?p :nom ?nom }
  OPTIONAL { ?p :nomMarital ?nomMarital }
  OPTIONAL { ?p :age ?age }
  OPTIONAL { ?p :estDecede ?decede }
  OPTIONAL { ?p :profession ?profession }
  OPTIONAL { ?p :avatar ?avatar }
  OPTIONAL { ?p :anneeNaissance ?anneeNaissance }
  OPTIONAL { ?p :anneeDeces ?anneeDeces }
  OPTIONAL { ?p a ?genre . FILTER(?genre IN (:Masculin, :Feminin)) }
}
ORDER BY ?p
SPARQL;
    }

    /** Tous les couples (enfant, parent). */
    public function allParentLinks(): string
    {
        return $this->prefixes() . 'SELECT DISTINCT ?child ?parent WHERE { ?child ' . $this->parentPath() . ' ?parent . }';
    }

    /** Tous les couples de conjoints (dans les deux sens). */
    public function allSpouseLinks(): string
    {
        return $this->prefixes() . 'SELECT DISTINCT ?a ?b WHERE { ?a ' . $this->spousePath() . ' ?b . FILTER(?a != ?b) }';
    }

    /**
     * Motif de graphe qui lie ?r à la relation $relation de $x.
     * $x = IRI (<...>) ou variable (?x). Exemple : pattern('grandparents', $iri) lie ?r aux grands-parents.
     */
    public function pattern(string $relation, string $x): string
    {
        $P = $this->parentPath();
        $S = $this->spousePath();
        $SIB = $this->siblingPath();

        $unclesAunts = "{ $x $P ?par . ?par $SIB ?r . FILTER(?r != ?par && ?r != $x) FILTER NOT EXISTS { $x $P ?r } }"
            . " UNION { $x $P ?par . ?par $SIB ?sib . FILTER(?sib != ?par) ?sib $S ?r . FILTER(?r != ?par && ?r != $x) FILTER NOT EXISTS { $x $P ?r } }";

        return match ($relation) {
            'parents'        => "$x $P ?r .",
            'children'       => "?r $P $x .",
            'spouse'         => "$x $S ?r . FILTER(?r != $x)",
            'siblings'       => "$x $SIB ?r . FILTER(?r != $x)",
            'brothers'       => "$x $SIB ?r . FILTER(?r != $x) ?r a :Masculin .",
            'sisters'        => "$x $SIB ?r . FILTER(?r != $x) ?r a :Feminin .",
            'grandparents'   => "$x $P/$P ?r . FILTER(?r != $x)",
            'grandchildren'  => "?r $P/$P $x . FILTER(?r != $x)",
            'uncles_aunts'   => $unclesAunts,
            'uncles'         => "{ $unclesAunts } ?r a :Masculin .",
            'aunts'          => "{ $unclesAunts } ?r a :Feminin .",
            'nephews_nieces' => "{ $x $SIB ?s . FILTER(?s != $x) ?r $P ?s . FILTER(?r != $x) }"
                              . " UNION { $x $S ?sp . ?sp $SIB ?s . FILTER(?s != ?sp) ?r $P ?s . FILTER(?r != $x && ?r != ?sp) FILTER NOT EXISTS { ?r $P $x } }",
            'cousins'        => "$x $P ?par . ?par $SIB ?u . FILTER(?u != ?par) FILTER NOT EXISTS { $x $P ?u } ?r $P ?u . FILTER(?r != $x)",
            'step_parents'   => "$x (^:beauParentDe|:beauEnfantDe) ?r .",
            'step_children'  => "$x (:beauParentDe|^:beauEnfantDe) ?r .",
            'ancestors'      => "$x {$P}+ ?r . FILTER(?r != $x)",
            'descendants'    => "?r {$P}+ $x . FILTER(?r != $x)",
            default          => throw new InvalidArgumentException("Relation inconnue : {$relation}"),
        };
    }

    /** SELECT des personnes liées à $id par $relation. */
    public function relationSelect(string $relation, string $id): string
    {
        return $this->prefixes() . "SELECT DISTINCT ?r WHERE {\n  " . $this->pattern($relation, $this->iri($id)) . "\n}\nORDER BY ?r";
    }

    /** ASK : "$aId est-il/elle (relation) de $bId ?" — ex. askRelation('uncles_aunts', 'Victorin_DOSSOU', 'Perseverance_DOSSOU'). */
    public function relationAsk(string $relation, string $aId, string $bId): string
    {
        return $this->prefixes() . "ASK {\n  VALUES ?r { " . $this->iri($aId) . " }\n  " . $this->pattern($relation, $this->iri($bId)) . "\n}";
    }

    /** Nombre de paires (x, r) pour une relation, sur toute la famille. */
    public function relationCount(string $relation): string
    {
        return $this->prefixes()
            . "SELECT (COUNT(*) AS ?n) WHERE {\n  SELECT DISTINCT ?x ?r WHERE {\n    ?x :prenom ?px .\n    "
            . $this->pattern($relation, '?x') . "\n  }\n}";
    }

    /** Nombre de relations explicitement saisies (assertions de propriétés objet entre individus). */
    public function assertedRelationCount(): string
    {
        return $this->prefixes() . <<<'SPARQL'
SELECT (COUNT(*) AS ?n) WHERE {
  ?a ?p ?b .
  ?p a owl:ObjectProperty .
  ?a :prenom ?pa .
  ?b :prenom ?pb .
}
SPARQL;
    }

    /** ASK : A et B ont-ils au moins un ancêtre commun ? (parenté éloignée) */
    public function commonAncestorAsk(string $aId, string $bId): string
    {
        $P = $this->parentPath();

        return $this->prefixes() . 'ASK { ' . $this->iri($aId) . " {$P}+ ?c . " . $this->iri($bId) . " {$P}+ ?c . }";
    }

    /** Nombre total de triplets du dataset. */
    public function tripleCount(): string
    {
        return $this->prefixes() . 'SELECT (COUNT(*) AS ?n) WHERE { ?s ?p ?o }';
    }

    /** Dataset vide ? */
    public function anyTriple(): string
    {
        return 'ASK { ?s ?p ?o }';
    }

    // ------------------------------------------------------------------ ontologie (schéma)

    public function ontologyCounts(): string
    {
        return $this->prefixes() . <<<'SPARQL'
SELECT
  (COUNT(DISTINCT ?class) AS ?classes)
  (COUNT(DISTINCT ?oprop) AS ?objectProperties)
  (COUNT(DISTINCT ?dprop) AS ?dataProperties)
  (COUNT(DISTINCT ?ind) AS ?individuals)
WHERE {
  { ?class a owl:Class . FILTER(isIRI(?class)) }
  UNION { ?oprop a owl:ObjectProperty }
  UNION { ?dprop a owl:DatatypeProperty }
  UNION { ?ind a owl:NamedIndividual }
}
SPARQL;
    }

    public function ontologyClasses(): string
    {
        return $this->prefixes() . 'SELECT ?c ?super WHERE { ?c a owl:Class . FILTER(isIRI(?c)) OPTIONAL { ?c rdfs:subClassOf ?super . FILTER(isIRI(?super)) } } ORDER BY ?c';
    }

    public function ontologyProperties(): string
    {
        return $this->prefixes() . <<<'SPARQL'
SELECT ?p ?kind ?inverse ?super ?domain ?range ?symmetric ?transitive ?functional WHERE {
  { ?p a owl:ObjectProperty . BIND("objet" AS ?kind) }
  UNION { ?p a owl:DatatypeProperty . BIND("donnee" AS ?kind) }
  OPTIONAL { ?p owl:inverseOf ?inverse }
  OPTIONAL { ?p rdfs:subPropertyOf ?super }
  OPTIONAL { ?p rdfs:domain ?domain . FILTER(isIRI(?domain)) }
  OPTIONAL { ?p rdfs:range ?range . FILTER(isIRI(?range)) }
  BIND(EXISTS { ?p a owl:SymmetricProperty } AS ?symmetric)
  BIND(EXISTS { ?p a owl:TransitiveProperty } AS ?transitive)
  BIND(EXISTS { ?p a owl:FunctionalProperty } AS ?functional)
}
ORDER BY ?p
SPARQL;
    }

    public function ontologyChains(): string
    {
        return $this->prefixes() . 'SELECT ?implied ?p1 ?p2 WHERE { ?implied owl:propertyChainAxiom ?l . ?l rdf:first ?p1 ; rdf:rest/rdf:first ?p2 }';
    }

    // ------------------------------------------------------------------ présélections pour la console SPARQL

    /** Requêtes d'exemple (valides sur l'ontologie du projet) proposées dans la console SPARQL. */
    public function presets(): array
    {
        $p = "PREFIX : <{$this->ns}>\nPREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n\n";

        return [
            [
                'id' => 'q1',
                'title' => 'Toutes les personnes (prénom, nom, âge)',
                'description' => 'Lecture simple des propriétés de données de la classe Personne.',
                'query' => $p . "SELECT ?personne ?prenom ?nom ?age WHERE {\n  ?personne :prenom ?prenom ;\n            :nom ?nom ;\n            :age ?age .\n}\nORDER BY DESC(?age)",
            ],
            [
                'id' => 'q2',
                'title' => 'Parents et enfants (relation enfantDe)',
                'description' => 'Les triplets enfantDe de la base, sous-propriétés comprises (enfantDe1, enfantDe2, enfantDe3).',
                'query' => $p . "SELECT ?enfant ?parent WHERE {\n  ?enfant (:enfantDe|:enfantDe1|:enfantDe2|:enfantDe3) ?parent .\n}\nORDER BY ?parent",
            ],
            [
                'id' => 'q3',
                'title' => 'Grands-parents (parent ∘ parent)',
                'description' => 'Relation déduite : le parent du parent, par composition de chemins.',
                'query' => $p . "SELECT ?petitEnfant ?grandParent WHERE {\n  ?petitEnfant (:enfantDe|:enfantDe1|:enfantDe2|:enfantDe3)/(:enfantDe|:enfantDe1|:enfantDe2|:enfantDe3) ?grandParent .\n}\nORDER BY ?grandParent",
            ],
            [
                'id' => 'q4',
                'title' => 'Couples mariés (propriété symétrique)',
                'description' => 'marieA est symétrique : chaque mariage est lisible dans les deux sens.',
                'query' => $p . "SELECT DISTINCT ?epoux ?epouse WHERE {\n  ?epoux (:marieA|:marieA1|:marieA2|:marieA3|:marieA4) ?epouse .\n  ?epoux a :Masculin .\n}",
            ],
            [
                'id' => 'q5',
                'title' => 'Frères et sœurs (frereDe / soeurDe)',
                'description' => 'Les liens de fratrie déclarés dans l\'ontologie.',
                'query' => $p . "SELECT ?personne ?lien ?fratrie WHERE {\n  { ?personne :frereDe ?fratrie . BIND(\"frère de\" AS ?lien) }\n  UNION\n  { ?personne :soeurDe ?fratrie . BIND(\"sœur de\" AS ?lien) }\n}\nORDER BY ?fratrie",
            ],
            [
                'id' => 'q6',
                'title' => 'Hommes et femmes (classes Masculin / Féminin)',
                'description' => 'Décompte par classe.',
                'query' => $p . "SELECT ?genre (COUNT(?personne) AS ?nombre) WHERE {\n  ?personne a ?genre .\n  FILTER(?genre IN (:Masculin, :Feminin))\n}\nGROUP BY ?genre",
            ],
            [
                'id' => 'q7',
                'title' => 'Définitions OWL de l\'ontologie (axiomes)',
                'description' => 'Les classes définies par équivalence (ex. Oncle, Cousin).',
                'query' => $p . "PREFIX owl: <http://www.w3.org/2002/07/owl#>\nSELECT ?classe WHERE {\n  ?classe owl:equivalentClass ?definition .\n}\nORDER BY ?classe",
            ],
        ];
    }
}

# Ontologie familiale — API sémantique (Laravel + Apache Jena Fuseki)

> Projet du cours **Données multimédia et Web sémantique** — Master 2 Génie Logiciel, IFRI / Université d'Abomey-Calavi (2026-2027).
> Enseignant : Dr TCHANTCHOU S. Yannick Ulrich.

## Équipe

- Backend (API, SPARQL) : AGBOGBA ZOUNON Silas
- Ontologie (Protégé, base sémantique) : DOSSOU PERSEVERANCE
- Frontend (Vue.js, SPARQL) : DOUGLOUI ADINETTE

## 1. Présentation

Application web de gestion d'une **famille béninoise** (30 personnes, 3 générations) construite autour d'une **ontologie OWL** créée avec Protégé.

L'idée centrale du Web sémantique : on n'enregistre que des **faits de base** (qui est l'enfant de qui, qui est marié à qui, qui est frère ou sœur de qui). Toutes les autres relations — grands-parents, oncles, tantes, cousins, neveux, beaux-parents, ancêtres… — ne sont **jamais saisies** : elles sont **déduites** par des requêtes SPARQL qui reprennent les définitions de l'ontologie.

Ce dépôt contient le **backend** (API REST + base sémantique). L'interface Vue.js est dans un dépôt séparé : https://github.com/Adinette/ontology_family (fichiers d'intégration dans `frontend-integration/`).

## 2. Fonctionnalités

- Gestion des membres : lister, filtrer, consulter, **ajouter, modifier, supprimer** (écriture SPARQL Update dans la base sémantique).
- **Relations déduites** d'un membre : parents, conjoints, enfants, fratrie, grands-parents, oncles et tantes (par le sang et par alliance), neveux et nièces, cousins, ancêtres, descendants, beaux-parents.
- **Recherche sémantique** : « Trouver les cousins de X », avec affichage de la requête SPARQL exécutée et de la règle de l'ontologie correspondante.
- **Lien entre deux personnes** : « Victorin est l'oncle de Persévérance » (requêtes `ASK`).
- **Console SPARQL** en lecture seule (SELECT / ASK) sur la vraie base.
- Tableau de bord (statistiques), résumé de l'ontologie (classes, propriétés, axiomes), export Turtle de toute la base.

## 3. Architecture

```
 Vue.js (interface)  ──HTTP / JSON──▶  Laravel (API REST)  ──SPARQL 1.1──▶  Apache Jena Fuseki
 http://localhost:5173                 http://localhost:8000               http://localhost:3030
                                                                                  ▲
                                                                  ontology/famille-dossou.ttl
                                                             (ontologie de Protégé, chargée au 1er appel)
```

| Couche      | Rôle                                                                                              |
| ----------- | ------------------------------------------------------------------------------------------------- |
| **Fuseki**  | stocke l'ontologie et les membres sous forme de triplets RDF ; répond au SPARQL                   |
| **Laravel** | reçoit les requêtes du frontend, construit les requêtes SPARQL, interroge Fuseki, renvoie du JSON |
| **Vue.js**  | affiche l'interface ; ne connaît pas SPARQL                                                       |

## 4. Prérequis

- **PHP 8.2+** et **Composer**
- **Java 17+** et **Apache Jena Fuseki** (archive `apache-jena-fuseki-X.Y.Z.zip` sur https://jena.apache.org/download/)
- **Node.js 18+** (uniquement pour lancer l'interface Vue)

## 5. Lancer le projet

Trois terminaux ouverts en même temps, **dans cet ordre** :

**Terminal 1 — Fuseki** (la base sémantique)

```bash
# Décompresser Apache Jena Fuseki dans ./fuseki (à la racine du projet), puis :
scripts/start-fuseki.sh        # Linux / macOS
scripts\start-fuseki.bat       # Windows
# équivalent manuel :  fuseki-server --update --mem /famille
```

Vérification : http://localhost:3030 affiche Fuseki avec la base `/famille`.

**Terminal 2 — API Laravel**

```bash
composer install
cp .env.example .env            # puis vérifier la présence des variables FUSEKI_* (voir ci-dessous)
php artisan key:generate
php artisan serve               # http://localhost:8000
```

Variables d'environnement utiles (`.env`) :

```
FUSEKI_URL=http://localhost:3030
FUSEKI_DATASET=famille
FUSEKI_ALLOW_RESET=true
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173
```

Vérification : http://localhost:8000/api/health → `{"status":"ok", ... "triples":780}` puis http://localhost:8000/api/members → 30 membres.
Au premier appel, l'API charge **automatiquement** l'ontologie dans Fuseki (commande manuelle : `php artisan ontology:import`).

**Terminal 3 — Interface Vue.js** (dépôt `ontology_family`, après intégration des fichiers de `frontend-integration/`)

```bash
npm install
npm run dev                     # http://localhost:5173
```

La pastille en bas à gauche de l'interface doit être **verte** (« API + Fuseki connectés »).

> Les données sont gardées en mémoire par Fuseki (`--mem`) : à chaque redémarrage de Fuseki, la base repart de l'ontologie d'origine (rechargée automatiquement). Pour conserver les membres ajoutés : `fuseki-server --update --loc=./databases/famille /famille`.

## 6. Comment les relations sont déduites

Les faits saisis : `enfantDe` (et ses sous-propriétés `enfantDe1/2/3`), `marieA`, `frereDe` / `soeurDe`, classes `Masculin` / `Feminin`.
Les relations déduites réécrivent en **SPARQL 1.1** (chemins de propriétés) les axiomes de l'ontologie :

| Relation              | Définition dans l'ontologie                     | Idée en SPARQL                               |
| --------------------- | ----------------------------------------------- | -------------------------------------------- |
| grands-parents        | `parentDe ∘ parentDe ⊑ grandParentDe`           | parent / parent                              |
| oncle, tante          | `Oncle ≡ Masculin ⊓ ∃frereDe.Parent`            | frère ou sœur d'un parent (ou leur conjoint) |
| cousin                | `Cousin ≡ Masculin ⊓ ∃enfantDe.(Oncle ⊔ Tante)` | enfant d'un oncle ou d'une tante             |
| conjoint              | `marieA` symétrique                             | `marieA` dans les deux sens                  |
| ancêtres, descendants | transitivité                                    | `enfantDe+`                                  |

Le code est dans `app/Services/Family/FamilyQueries.php` ; `GET /api/ontology` expose les axiomes ; l'API renvoie la requête SPARQL utilisée pour chaque recherche.

**Limite assumée** : Fuseki n'exécute pas ici de raisonneur OWL DL (type HermiT). Les définitions de classes sont donc **traduites en SPARQL** plutôt que classifiées automatiquement, ce qui donne les mêmes résultats pour ces relations.

## 7. L'API en bref

Toutes les routes sont préfixées par `/api`. Exemples d'appels prêts à l'emploi : `docs/requests.http` (extension VS Code « REST Client »).

| Méthode            | Route                         | Rôle                                    |
| ------------------ | ----------------------------- | --------------------------------------- |
| GET                | `/health`                     | état de l'API et de Fuseki              |
| GET / POST         | `/members`                    | lister / ajouter                        |
| GET / PUT / DELETE | `/members/{id}`               | consulter / modifier / supprimer        |
| GET                | `/members/{id}/relations`     | toutes les relations déduites           |
| GET                | `/search?person=…&relation=…` | recherche sémantique (+ requête SPARQL) |
| GET                | `/relationship?from=…&to=…`   | lien entre deux personnes               |
| GET                | `/stats`                      | chiffres du tableau de bord             |
| POST               | `/sparql`                     | console SPARQL (lecture seule)          |
| GET                | `/ontology`                   | classes, propriétés, axiomes            |
| GET                | `/export/turtle`              | export de toute la base                 |
| POST               | `/reset`                      | recharger l'ontologie d'origine         |

Détail de toutes les routes, du format JSON et du dépannage : [`docs/INSTALLATION.md`](docs/INSTALLATION.md).

## 8. Structure du dépôt

```
app/Services/Fuseki/FusekiClient.php     client HTTP de Fuseki (query, update, data)
app/Services/Family/FamilyQueries.php    toutes les requêtes SPARQL de lecture
app/Services/Family/FamilyUpdates.php    requêtes SPARQL Update (ajout, modification, suppression)
app/Services/Family/FamilyService.php    logique métier : SPARQL -> JSON pour le frontend
app/Http/Controllers/Api/                contrôleurs (membres, relations, stats, SPARQL, ontologie)
app/Http/Middleware/                     JSON forcé ; chargement automatique de l'ontologie
app/Console/Commands/ImportOntology.php  php artisan ontology:import
routes/api.php                           routes de l'API
config/fuseki.php, config/cors.php       configuration
ontology/                                ontologie .owl (Protégé), .ttl (converti pour Fuseki), extension
tools/owlxml_to_ttl.py                   convertisseur OWL/XML -> Turtle
scripts/                                 lancement de Fuseki (Linux/macOS/Windows)
frontend-integration/                    fichiers à intégrer dans le dépôt Vue.js
docs/                                    installation détaillée, guide frontend, exemples d'appels, résumé du travail
```

## 9. Vérifications réalisées

- Les **510 requêtes de relation** (17 relations × 30 personnes) ont été comparées à un calcul indépendant : aucune différence.
- Le code d'accès à la base (lecture, ajout, modification, suppression, erreurs, export, réinitialisation) a été exécuté contre un serveur imitant Fuseki : 67 vérifications réussies.
- L'API a été lancée avec un vrai Fuseki : `/api/health` indique 780 triplets et `/api/members` renvoie les 30 membres.

## 10. Limites et pistes

- L'ontologie contient 3 générations ; `Kadukpe_LAWINI` n'a qu'un parent déclaré.
- Pas d'authentification (projet académique) ; la console SPARQL est en lecture seule.
- Piste : activer un raisonneur OWL côté Fuseki, ou exporter l'ontologie inférée depuis Protégé (HermiT).

## 11. Équipe

- Backend (API, SPARQL) : AGBOGBA ZOUNON Silas
- Ontologie (Protégé, base sémantique) : DOSSOU PERSEVERANCE
- Frontend (Vue.js, SPARQL) : DOUGLOUI ADINETTE

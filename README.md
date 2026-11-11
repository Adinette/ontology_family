# 🌿 AncesTree — Dashboard & Ontologie Familiale (IFRI)

Projet complet réalisé avec **Vue.js 3**, **Vite** et **Tailwind CSS**, inspiré de la maquette *AncesTree*, dédié à la modélisation, la gestion et le raisonnement ontologique d'une famille fictive (Web Sémantique, OWL, RDF).

---

## 📋 Fonctionnalités & Menu

L'application intègre fidèlement le menu et les exigences demandées :

1. **🏠 Tableau de bord** :
   - Nombre total de personnes
   - Nombre d'hommes (classe ontologique `:Homme`)
   - Nombre de femmes (classe ontologique `:Femme`)
   - Nombre de familles / générations (G1 à G4)
   - Nombre de relations connues (assertées) + relations inférées
   - Répartition graphique par génération et statistiques de parenté

2. **👨‍👩‍👧 Membres de la famille** :
   - Annuaire complet avec filtres par genre, génération et recherche textuelle
   - Double affichage : **Cartes (Grid)** ou **Tableau détaillé**
   - Consultation des profils, édition et suppression

3. **➕ Ajouter un membre** :
   - Formulaire complet avec :
     - **Nom complet**
     - **Sexe** (Homme / Femme)
     - **Âge** et année de naissance (calculs synchronisés)
     - **Père** (sélection parmi les hommes de la famille)
     - **Mère** (sélection parmi les femmes de la famille)
     - **Conjoint ou conjointe** (propriété symétrique automatique)
     - **Profession** et sélection d'avatar
   - Prévisualisation dynamique de la carte

4. **🔗 Relations familiales (Arbre Généalogique interactif)** :
   - Canvas vert sauge/menthe avec motif organique fidèle à la capture
   - Cartes avec photo, nom et dates de vie
   - Lignes de descendance directes (vert émeraude) et liens de mariage pointillés (rose/corail)
   - Zoom avant / arrière, recentrage et sélection interactive d'un membre avec surbrillance de ses proches

5. **🔍 Recherche sémantique** :
   - Générateur de requêtes en langage naturel : *"Trouver tous les [oncles / cousins / petits-enfants...] de [Personne]"*
   - Détecteur de lien de parenté entre deux individus arbitraires (A ↔ B)
   - Console d'exécution de requêtes **SPARQL** avec résultats tabulaires

6. **🧠 Raisonnement Ontologique (OWL-DL)** :
   - Explication des axiomes formels :
     - Inversion (`owl:inverseOf`) : `aPourParent` ↔ `aPourEnfant`
     - Symétrie (`owl:SymmetricProperty`) : `aPourConjoint`
     - Chaîne de propriétés (`owl:propertyChainAxiom`) : `parent o parent ⇒ grandParent`
     - Règles de fratrie, d'oncle/tante et cousins
     - Transitivité (`owl:TransitiveProperty`) : `aPourAncetre`
   - Comparaison chiffrée : faits assertés vs faits inférés
   - Exportation directe au format **Turtle (`.ttl`)** compatible avec le logiciel **Protégé**

---

## 🚀 Lancement du Projet

### 1. Installation des dépendances (déjà effectuée) :
```bash
npm install
```

### 2. Démarrage en mode Développement :
```bash
npm run dev
```
L'application sera accessible sur `http://localhost:5173/`.

### 3. Construction pour la production :
```bash
npm run build
npm run preview
```
Accessible sur `http://localhost:4173/`.

---

## 📁 Structure du Projet

```text
src/
├── assets/                 # Styles et icônes
├── components/
│   ├── Sidebar.vue         # Menu latéral émeraude AncesTree
│   ├── TopHeader.vue       # En-tête de navigation, recherche et profil
│   ├── DashboardView.vue   # Vue 🏠 Tableau de bord avec statistiques
│   ├── MembersView.vue     # Vue 👨‍👩‍👧 Annuaire des membres
│   ├── FamilyTreeView.vue  # Vue 🔗 Arbre généalogique style capture
│   ├── AddMemberModal.vue  # Modal ➕ Formulaire d'ajout
│   ├── SemanticSearchView.vue # Vue 🔍 Recherche sémantique & SPARQL
│   ├── ReasoningView.vue   # Vue 🧠 Moteur d'inférence & export OWL/Turtle
│   └── MemberDetailDrawer.vue # Tiroir latéral de parenté d'un membre
├── composables/
│   └── useFamilyStore.js   # État global réactif & persistance LocalStorage
├── data/
│   └── familyData.js       # Données initiales Carter Family (G1 à G4)
├── services/
│   └── ontologyReasoner.js # Moteur d'inférence sémantique & export RDF
├── App.vue                 # Composant racine
├── main.js                 # Point d'entrée Vue 3
└── style.css               # Configuration Tailwind CSS
```


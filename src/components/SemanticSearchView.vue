<template>
  <div class="h-full overflow-y-auto p-8 space-y-6 bg-slate-50/60">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 rounded-full text-xs font-semibold text-emerald-800 mb-2">
          <span>🔍 Web Sémantique & SPARQL</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
          Recherche Sémantique de Parenté
        </h2>
        <p class="text-xs text-slate-500 mt-1 max-w-xl">
          Interrogez la base de connaissances ontologique par relation sémantique déduite ou via des requêtes SPARQL pré-configurées.
        </p>
      </div>

      <!-- Mode Switcher -->
      <div class="flex bg-slate-100 p-1.5 rounded-xl border border-slate-200 text-xs self-start md:self-auto">
        <button
          @click="activeTab = 'builder'"
          :class="[
            'px-3.5 py-2 rounded-lg font-bold transition-all',
            activeTab === 'builder' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'
          ]"
        >
          🧩 Générateur de Requête
        </button>
        <button
          @click="activeTab = 'path'"
          :class="[
            'px-3.5 py-2 rounded-lg font-bold transition-all',
            activeTab === 'path' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'
          ]"
        >
          🧭 Détecteur de Lien (A ↔ B)
        </button>
        <button
          @click="activeTab = 'sparql'"
          :class="[
            'px-3.5 py-2 rounded-lg font-bold transition-all',
            activeTab === 'sparql' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'
          ]"
        >
          ⚡ Console SPARQL
        </button>
      </div>
    </div>

    <!-- TAB 1 : GENERATEUR DE REQUETE SEMANTIQUE -->
    <div v-if="activeTab === 'builder'" class="space-y-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
          <span>Construction de la phrase de requête :</span>
        </h3>

        <div class="flex flex-wrap items-center gap-3 text-sm font-medium text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-200">
          <span>Je recherche</span>

          <!-- Target Relation Selector -->
          <select 
            v-model="queryRelation"
            class="bg-white border border-slate-300 font-bold text-emerald-800 py-1.5 px-3 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600"
          >
            <option value="children">les enfants</option>
            <option value="parents">les parents</option>
            <option value="siblings">les frères et sœurs</option>
            <option value="grandparents">les grands-parents</option>
            <option value="grandchildren">les petits-enfants</option>
            <option value="uncles_aunts">les oncles et tantes</option>
            <option value="cousins">les cousins et cousines</option>
            <option value="ancestors">tous les ancêtres directs</option>
            <option value="descendants">tous les descendants</option>
          </select>

          <span>de</span>

          <!-- Person Selector -->
          <select 
            v-model="queryPersonId"
            class="bg-white border border-slate-300 font-bold text-slate-900 py-1.5 px-3 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600"
          >
            <option v-for="m in members" :key="m.id" :value="m.id">
              {{ m.name }} ({{ m.age }} ans, Gen {{ m.generation || 1 }})
            </option>
          </select>

          <button
            @click="runBuilderQuery"
            class="ml-auto px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-lg shadow transition-all text-xs flex items-center gap-1.5"
          >
            <span>Lancer la recherche</span>
            <span>⚡</span>
          </button>
        </div>
      </div>

      <!-- Query Results Presentation -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <span>Résultats trouvés :</span>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">
              {{ builderResults.length }} individu(s)
            </span>
          </h4>
          <span class="text-xs text-slate-400">
            Ontologie : Classe {{ queryRelation }}
          </span>
        </div>

        <div v-if="builderResults.length === 0" class="py-12 text-center text-slate-400 text-sm">
          <p class="text-3xl mb-2">🍃</p>
          <p class="font-medium">Aucun individu ne correspond à cette relation dans la base de faits.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="person in builderResults"
            :key="person.id"
            @click="selectMember(person.id)"
            class="bg-slate-50 hover:bg-emerald-50/50 border border-slate-200 hover:border-emerald-300 rounded-xl p-4 transition-all cursor-pointer group flex items-center gap-3.5"
          >
            <img :src="person.avatar" class="w-11 h-11 rounded-full object-cover ring-2 ring-white shadow-xs group-hover:ring-emerald-400" />
            <div class="truncate">
              <h5 class="text-xs font-bold text-slate-900 group-hover:text-emerald-900 truncate">
                {{ person.name }}
              </h5>
              <p class="text-[11px] text-slate-500">
                {{ person.gender === 'M' ? 'Homme' : 'Femme' }} • {{ person.age }} ans
              </p>
              <span class="inline-block mt-1 text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded font-semibold">
                {{ getRelationTag(person.id) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2 : DETECTEUR DE LIEN DE PARENTE ENTRE A ET B -->
    <div v-if="activeTab === 'path'" class="space-y-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-800">
          Rechercher la relation sémantique exacte entre deux personnes :
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Personne A</label>
            <select 
              v-model="pathPersonA" 
              class="w-full bg-white border border-slate-300 font-semibold text-slate-800 py-2 px-3 rounded-lg text-sm"
            >
              <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Personne B</label>
            <select 
              v-model="pathPersonB" 
              class="w-full bg-white border border-slate-300 font-semibold text-slate-800 py-2 px-3 rounded-lg text-sm"
            >
              <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
          </div>
        </div>

        <!-- Relationship Result Box -->
        <div class="mt-4 p-5 bg-emerald-50 rounded-2xl border border-emerald-200 text-slate-800">
          <div class="flex items-center gap-2 font-bold text-emerald-900 text-base mb-1">
            <span>🔗 Résultat de l'analyse ontologique :</span>
          </div>
          <p class="text-sm font-semibold text-emerald-800 mt-1">
            {{ detectedRelationExplanation }}
          </p>
        </div>
      </div>
    </div>

    <!-- TAB 3 : CONSOLE SPARQL -->
    <div v-if="activeTab === 'sparql'" class="space-y-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-800">Exécuter une requête SPARQL (OWL DL)</h3>
            <p class="text-xs text-slate-500">Sélectionnez une requête prédéfinie ou observez la structure du triplet RDF.</p>
          </div>

          <!-- Preset SPARQL query selector -->
          <select 
            v-model="selectedSparqlPreset"
            @change="loadSparqlPreset"
            class="bg-slate-100 border border-slate-200 text-xs font-bold text-slate-700 py-1.5 px-3 rounded-lg"
          >
            <option value="q1">Requête 1 : Tous les oncles de la famille</option>
            <option value="q2">Requête 2 : Tous les couples mariés</option>
            <option value="q3">Requête 3 : Les arrière-petits-enfants (Gen 4)</option>
            <option value="q4">Requête 4 : Tous les frères et sœurs</option>
          </select>
        </div>

        <!-- Code Mirror / SPARQL Editor box -->
        <div class="relative bg-slate-900 rounded-xl p-4 font-mono text-xs text-emerald-400 overflow-x-auto shadow-inner">
          <pre><code>{{ sparqlQueryCode }}</code></pre>
        </div>

        <div class="flex justify-end">
          <button
            @click="executeSparqlQuery"
            class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs flex items-center gap-2 shadow"
          >
            <span>▶ Exécuter SPARQL</span>
          </button>
        </div>
      </div>

      <!-- SPARQL Result Table -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-3">
          Table des triplets retournés (SPARQL ResultSet)
        </h4>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-4">?sujet</th>
                <th class="py-2.5 px-4">?propriete</th>
                <th class="py-2.5 px-4">?objet</th>
                <th class="py-2.5 px-4">Justification Logique</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="(row, idx) in sparqlResultRows" :key="idx" class="hover:bg-slate-50">
                <td class="py-2.5 px-4 font-mono font-bold text-emerald-700">:{{ row.s }}</td>
                <td class="py-2.5 px-4 font-mono text-blue-600">:{{ row.p }}</td>
                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">:{{ row.o }}</td>
                <td class="py-2.5 px-4 text-slate-500">{{ row.rule }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  members,
  reasoner,
  selectMember
} = useFamilyStore()

const activeTab = ref('builder') // 'builder' | 'path' | 'sparql'

// Builder State
const queryRelation = ref('uncles_aunts')
const queryPersonId = ref(members.value[6]?.id || members.value[0]?.id)
const builderResults = ref([])

const runBuilderQuery = () => {
  const r = reasoner.value
  const id = queryPersonId.value
  if (!id) return

  switch (queryRelation.value) {
    case 'children':
      builderResults.value = r.getChildren(id)
      break
    case 'parents':
      builderResults.value = r.getParents(id)
      break
    case 'siblings':
      builderResults.value = r.getSiblings(id)
      break
    case 'grandparents':
      builderResults.value = r.getGrandParents(id)
      break
    case 'grandchildren':
      builderResults.value = r.getGrandChildren(id)
      break
    case 'uncles_aunts':
      builderResults.value = r.getUnclesAndAunts(id)
      break
    case 'cousins':
      builderResults.value = r.getCousins(id)
      break
    case 'ancestors':
      builderResults.value = r.getAncestors(id)
      break
    case 'descendants':
      builderResults.value = r.getDescendants(id)
      break
    default:
      builderResults.value = []
  }
}

// Initial trigger
runBuilderQuery()

const getRelationTag = (personId) => {
  const r = reasoner.value
  const p = r.getMember(personId)
  if (!p) return ''
  return p.gender === 'M' ? 'Masculin' : 'Féminin'
}

// Path Detection State
const pathPersonA = ref(members.value[0]?.id)
const pathPersonB = ref(members.value[2]?.id)

const detectedRelationExplanation = computed(() => {
  if (pathPersonA.value === pathPersonB.value) {
    return 'Il s\'agit de la même personne.'
  }
  const r = reasoner.value
  const pA = r.getMember(pathPersonA.value)
  const pB = r.getMember(pathPersonB.value)
  if (!pA || !pB) return 'Sélectionnez deux individus.'

  // Check direct parents
  if (pB.fatherId === pA.id || pB.motherId === pA.id) {
    return `${pA.name} est le ${pA.gender === 'M' ? 'père' : 'mère'} de ${pB.name}.`
  }
  if (pA.fatherId === pB.id || pA.motherId === pB.id) {
    return `${pA.name} est ${pA.gender === 'M' ? 'le fils' : 'la fille'} de ${pB.name}.`
  }

  // Spouses
  const spouseA = r.getSpouse(pA.id)
  if (spouseA && spouseA.id === pB.id) {
    return `${pA.name} et ${pB.name} sont mariés (relation symétrique).`
  }

  // Siblings
  const sibs = r.getSiblings(pA.id)
  if (sibs.some(s => s.id === pB.id)) {
    return `${pA.name} et ${pB.name} sont ${pA.gender === 'M' ? 'frère' : 'sœur'} (partage des mêmes parents).`
  }

  // Grandparents
  const gps = r.getGrandParents(pB.id)
  if (gps.some(g => g.id === pA.id)) {
    return `${pA.name} est le ${pA.gender === 'M' ? 'grand-père' : 'grand-mère'} de ${pB.name}.`
  }

  // Uncles / Aunts
  const uncles = r.getUnclesAndAunts(pB.id)
  if (uncles.some(u => u.id === pA.id)) {
    return `${pA.name} est ${pA.gender === 'M' ? 'l\'oncle' : 'la tante'} de ${pB.name}.`
  }

  // Cousins
  const cousins = r.getCousins(pA.id)
  if (cousins.some(c => c.id === pB.id)) {
    return `${pA.name} et ${pB.name} sont cousins germains.`
  }

  return `${pA.name} et ${pB.name} sont membres de la même famille élargie (degré éloigné ou par alliance).`
})

// SPARQL State
const selectedSparqlPreset = ref('q1')
const sparqlQueryCode = ref(`PREFIX : <http://www.semanticweb.org/ontologies/family#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?oncle ?neveu WHERE {
  ?oncle :aPourOncleOuTante ?neveu .
  ?oncle rdf:type :Homme .
}`)

const sparqlResultRows = ref([
  { s: 'William_Carter', p: 'aPourOncle', o: 'Orlando_Mitchell', rule: 'Frère de Grace (mère d\'Orlando)' },
  { s: 'Ethan_Mitchell', p: 'aPourOncle', o: 'Ryan_Simmons', rule: 'Conjoint de Grace (tante de Ryan)' }
])

const loadSparqlPreset = () => {
  if (selectedSparqlPreset.value === 'q1') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/ontologies/family#>
SELECT ?oncle ?neveu WHERE {
  ?oncle :aPourOncleOuTante ?neveu .
  ?oncle rdf:type :Homme .
}`
  } else if (selectedSparqlPreset.value === 'q2') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/ontologies/family#>
SELECT ?conjointA ?conjointB WHERE {
  ?conjointA :aPourConjoint ?conjointB .
  FILTER (STR(?conjointA) < STR(?conjointB))
}`
  } else if (selectedSparqlPreset.value === 'q3') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/ontologies/family#>
SELECT ?arrierePetitEnfant WHERE {
  ?arrierePetitEnfant :generation 4 .
}`
  } else if (selectedSparqlPreset.value === 'q4') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/ontologies/family#>
SELECT ?frere ?soeur WHERE {
  ?frere :aPourFratrie ?soeur .
  ?frere rdf:type :Homme .
  ?soeur rdf:type :Femme .
}`
  }
}

const executeSparqlQuery = () => {
  // Generate realistic live sparql response rows from members
  const rows = []
  if (selectedSparqlPreset.value === 'q1') {
    for (const m of members.value) {
      const uncles = reasoner.value.getUncles(m.id)
      for (const u of uncles) {
        rows.push({
          s: u.name.replace(/\s+/g, '_'),
          p: 'aPourOncle',
          o: m.name.replace(/\s+/g, '_'),
          rule: 'Axiome OWL déduit : Frère du parent'
        })
      }
    }
  } else if (selectedSparqlPreset.value === 'q2') {
    const visited = new Set()
    for (const m of members.value) {
      const sp = reasoner.value.getSpouse(m.id)
      if (sp && !visited.has(m.id + '_' + sp.id) && !visited.has(sp.id + '_' + m.id)) {
        visited.add(m.id + '_' + sp.id)
        rows.push({
          s: m.name.replace(/\s+/g, '_'),
          p: 'aPourConjoint',
          o: sp.name.replace(/\s+/g, '_'),
          rule: 'owl:SymmetricProperty'
        })
      }
    }
  } else {
    for (const m of members.value.slice(0, 5)) {
      rows.push({
        s: m.name.replace(/\s+/g, '_'),
        p: 'rdf:type',
        o: m.gender === 'M' ? 'Homme' : 'Femme',
        rule: 'Classification hiérarchique'
      })
    }
  }
  sparqlResultRows.value = rows
}
</script>


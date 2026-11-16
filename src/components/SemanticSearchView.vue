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
          <p class="font-medium">Aucun individu ne correspond à cette relation dans la base de semantique.</p>
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

        <!-- Explication Ontologique et Requête SPARQL exécutée -->
        <div v-if="builderSparql" class="mt-4 pt-4 border-t border-slate-100 space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-emerald-800 flex items-center gap-1.5">
              <span>⚡ Requête SPARQL 1.1 exécutée dans Fuseki :</span>
            </span>
            <span v-if="builderRule" class="text-[11px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-mono">
              Règle : {{ builderRule }}
            </span>
          </div>
          <pre class="bg-slate-900 text-emerald-300 p-3 rounded-xl text-xs font-mono overflow-x-auto"><code>{{ builderSparql }}</code></pre>
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
import { ref, computed, watch } from 'vue'
import { useFamilyStore } from '../composables/useFamilyStore.js'
import { api } from '../services/api.js'

const {
  members,
  reasoner,
  selectMember,
  isConnected
} = useFamilyStore()

const activeTab = ref('builder') // 'builder' | 'path' | 'sparql'

// Builder State
const queryRelation = ref('uncles_aunts')
const queryPersonId = ref(null)
const builderResults = ref([])
const builderSparql = ref('')
const builderRule = ref('')
const isSearching = ref(false)

// Path Detection State
const pathPersonA = ref(null)
const pathPersonB = ref(null)
const pathDetectedSentence = ref('')
const pathDetectedRule = ref('')
const pathDetectedSparql = ref('')

const runBuilderQuery = async () => {
  const id = queryPersonId.value || members.value[0]?.id
  if (!id) return
  queryPersonId.value = id

  if (isConnected.value) {
    isSearching.value = true
    try {
      const response = await api.search(queryRelation.value, id)
      const res = response.data || response
      builderResults.value = res.results || []
      builderSparql.value = res.sparql || ''
      builderRule.value = res.rule || ''
      isSearching.value = false
      return
    } catch (err) {
      console.warn('Erreur recherche API:', err)
      isSearching.value = false
    }
  }

  // Fallback local
  builderSparql.value = ''
  builderRule.value = ''
  const r = reasoner.value
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

// Initialisation dès que les membres sont disponibles
watch(members, (newVal) => {
  if (newVal && newVal.length > 0 && !queryPersonId.value) {
    queryPersonId.value = newVal[0]?.id
    pathPersonA.value = newVal[0]?.id
    pathPersonB.value = newVal[1]?.id || newVal[0].id
    runBuilderQuery()
  }
}, { immediate: true })

const getRelationTag = (personId) => {
  const r = reasoner.value
  const p = r.getMember(personId)
  if (!p) return ''
  return p.gender === 'M' ? 'Masculin' : 'Féminin'
}



const detectedRelationExplanation = computed(() => {
  if (pathDetectedSentence.value) {
    return pathDetectedSentence.value
  }
  if (!pathPersonA.value || !pathPersonB.value) return 'Sélectionnez deux individus.'
  if (pathPersonA.value === pathPersonB.value) return 'Il s\'agit de la même personne.'

  const r = reasoner.value
  const pA = r.getMember(pathPersonA.value)
  const pB = r.getMember(pathPersonB.value)
  if (!pA || !pB) return 'Sélectionnez deux individus.'

  if (pB.fatherId === pA.id || pB.motherId === pA.id) {
    return `${pA.name} est le ${pA.gender === 'M' ? 'père' : 'mère'} de ${pB.name}.`
  }
  if (pA.fatherId === pB.id || pA.motherId === pB.id) {
    return `${pA.name} est ${pA.gender === 'M' ? 'le fils' : 'la fille'} de ${pB.name}.`
  }
  const spouseA = r.getSpouse(pA.id)
  if (spouseA && spouseA.id === pB.id) {
    return `${pA.name} et ${pB.name} sont mariés (relation symétrique).`
  }
  const sibs = r.getSiblings(pA.id)
  if (sibs.some(s => s.id === pB.id)) {
    return `${pA.name} et ${pB.name} sont ${pA.gender === 'M' ? 'frère' : 'sœur'} (partage des mêmes parents).`
  }
  const gps = r.getGrandParents(pB.id)
  if (gps.some(g => g.id === pA.id)) {
    return `${pA.name} est le ${pA.gender === 'M' ? 'grand-père' : 'grand-mère'} de ${pB.name}.`
  }
  const uncles = r.getUnclesAndAunts(pB.id)
  if (uncles.some(u => u.id === pA.id)) {
    return `${pA.name} est ${pA.gender === 'M' ? 'l\'oncle' : 'la tante'} de ${pB.name}.`
  }
  const cousins = r.getCousins(pA.id)
  if (cousins.some(c => c.id === pB.id)) {
    return `${pA.name} et ${pB.name} sont cousins germains.`
  }
  return `${pA.name} et ${pB.name} sont membres de la même famille.`
})

// Détecter via API dès que A ou B change
watch([pathPersonA, pathPersonB], async ([newA, newB]) => {
  if (newA && newB && isConnected.value) {
    try {
      const res = await api.getRelationship(newA, newB)
      if (res && res.sentence) {
        pathDetectedSentence.value = res.sentence
        pathDetectedRule.value = res.rule || ''
        pathDetectedSparql.value = res.sparql || ''
        return
      }
    } catch {
      pathDetectedSentence.value = ''
      pathDetectedRule.value = ''
      pathDetectedSparql.value = ''
    }
  }
  pathDetectedSentence.value = ''
  pathDetectedRule.value = ''
  pathDetectedSparql.value = ''
})

// SPARQL State
const selectedSparqlPreset = ref('q1')
const sparqlQueryCode = ref(`PREFIX : <http://www.semanticweb.org/dossou/ontologies/2026/famille-dossou#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?oncle ?neveu WHERE {
  ?oncle :frereDe ?parent .
  ?neveu :enfantDe ?parent .
  ?oncle rdf:type :Masculin .
}`)

const sparqlResultRows = ref([])
const isExecutingSparql = ref(false)

const loadSparqlPreset = () => {
  if (selectedSparqlPreset.value === 'q1') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/dossou/ontologies/2026/famille-dossou#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?oncle ?neveu WHERE {
  ?oncle :frereDe ?parent .
  ?neveu :enfantDe ?parent .
  ?oncle rdf:type :Masculin .
}`
  } else if (selectedSparqlPreset.value === 'q2') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/dossou/ontologies/2026/famille-dossou#>

SELECT ?conjointA ?conjointB WHERE {
  ?conjointA :marieA ?conjointB .
  FILTER (STR(?conjointA) < STR(?conjointB))
}`
  } else if (selectedSparqlPreset.value === 'q3') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/dossou/ontologies/2026/famille-dossou#>

SELECT ?membre ?profession WHERE {
  ?membre :profession ?profession .
}`
  } else if (selectedSparqlPreset.value === 'q4') {
    sparqlQueryCode.value = `PREFIX : <http://www.semanticweb.org/dossou/ontologies/2026/famille-dossou#>

SELECT ?parent ?enfant WHERE {
  ?enfant :enfantDe ?parent .
}`
  }
}

const executeSparqlQuery = async () => {
  if (isConnected.value) {
    isExecutingSparql.value = true
    try {
      const res = await api.runSparql(sparqlQueryCode.value)
      if (res && res.type === 'ask') {
        sparqlResultRows.value = [{
          s: 'Requête ASK',
          p: 'Évaluation booléenne',
          o: res.boolean ? 'VRAI (TRUE)' : 'FAUX (FALSE)',
          rule: `Exécuté dans Fuseki en ${res.executionMs} ms`
        }]
      } else if (res && res.rows) {
        sparqlResultRows.value = res.rows.map(row => {
          const keys = Object.keys(row)
          return {
            s: row[keys[0]] || '-',
            p: row[keys[1]] || '-',
            o: row[keys[2]] || '-',
            rule: `Exécuté dans Fuseki en ${res.executionMs} ms`
          }
        })
      }
      isExecutingSparql.value = false
      return
    } catch (err) {
      console.error('Erreur exécution SPARQL:', err)
      isExecutingSparql.value = false
    }
  }

  // Fallback
  sparqlResultRows.value = [
    { s: 'Victorin_DOSSOU', p: 'frereDe', o: 'Paul_DOSSOU', rule: 'Règle locale' }
  ]
}
</script>


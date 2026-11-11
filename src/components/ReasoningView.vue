<template>
  <div class="h-full overflow-y-auto p-8 space-y-6 bg-slate-50/60">
    <!-- Header with Export actions -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 rounded-full text-xs font-bold text-amber-800 mb-2">
          <span>🧠 Moteur de Raisonnement OWL-DL</span>
          <span>•</span>
          <span>IFRI TP</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
          Règles d'Inférence & Raisonnement Ontologique
        </h2>
        <p class="text-xs text-slate-500 mt-1 max-w-xl">
          Visualisez comment les axiomes formels du Web Sémantique permettent de déduire de nouvelles connaissances à partir des faits élémentaires.
        </p>
      </div>

      <!-- Export Buttons -->
      <div class="flex items-center gap-3">
        <button
          @click="downloadTurtle"
          class="flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Exporter OWL / Turtle (.ttl)</span>
        </button>

        <button
          @click="showTurtleCode = !showTurtleCode"
          class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all"
        >
          {{ showTurtleCode ? 'Masquer le code' : 'Voir Code OWL' }}
        </button>
      </div>
    </div>

    <!-- Turtle Viewer Drawer / Modal (if opened) -->
    <div v-if="showTurtleCode" class="bg-slate-900 rounded-2xl p-5 border border-slate-800 shadow-xl space-y-3">
      <div class="flex items-center justify-between text-xs text-slate-400 border-b border-slate-800 pb-2">
        <span class="font-mono text-emerald-400 font-bold">family_ontology.ttl (W3C Turtle Syntax)</span>
        <button @click="copyTurtleCode" class="text-xs bg-slate-800 hover:bg-slate-700 text-white px-2.5 py-1 rounded">
          {{ copied ? 'Copié !' : 'Copier dans le presse-papier' }}
        </button>
      </div>
      <pre class="text-xs text-emerald-300 font-mono max-h-72 overflow-y-auto leading-relaxed"><code>{{ turtleCode }}</code></pre>
    </div>

    <!-- Reasoning Performance Metric Comparison -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold">
          📥
        </div>
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Faits Assertés (A-Box)</span>
          <p class="text-2xl font-extrabold text-slate-900">{{ stats.assertedRelationsCount }} liens</p>
          <p class="text-[11px] text-slate-500">Saisis manuellement</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold">
          ⚡
        </div>
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Faits Déduits (Inférence)</span>
          <p class="text-2xl font-extrabold text-amber-600">+{{ stats.inferredRelationsCount }} liens</p>
          <p class="text-[11px] text-slate-500">Générés par les règles OWL</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold">
          📈
        </div>
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Multiplicateur de Savoir</span>
          <p class="text-2xl font-extrabold text-emerald-700">
            x{{ stats.assertedRelationsCount > 0 ? ((stats.assertedRelationsCount + stats.inferredRelationsCount) / stats.assertedRelationsCount).toFixed(1) : 1 }}
          </p>
          <p class="text-[11px] text-slate-500">Gain de complétude</p>
        </div>
      </div>
    </div>

    <!-- Formal Axiom Rules Explained -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <span>📖 Axiomes et Règles Logiques Implémentés :</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Rule 1: Inverse Property -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-emerald-800">1. Propriété Inverse</span>
            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-mono">owl:inverseOf</span>
          </div>
          <p class="text-xs font-mono text-slate-700 bg-white p-2 rounded border border-slate-200">
            aPourParent ≡ inverseOf(aPourEnfant)
          </p>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Si X a pour parent Y, alors le raisonneur déduit automatiquement que Y a pour enfant X.
          </p>
        </div>

        <!-- Rule 2: Symmetric Property -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-rose-800">2. Propriété Symétrique</span>
            <span class="text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-mono">owl:SymmetricProperty</span>
          </div>
          <p class="text-xs font-mono text-slate-700 bg-white p-2 rounded border border-slate-200">
            aPourConjoint(X, Y) ⇒ aPourConjoint(Y, X)
          </p>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Le mariage est bidirectionnel : définir John comme époux de Willow implique réciproquement le statut d'épouse.
          </p>
        </div>

        <!-- Rule 3: Property Chain (Grandparent) -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-blue-800">3. Chaîne de Propriétés</span>
            <span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono">propertyChainAxiom</span>
          </div>
          <p class="text-xs font-mono text-slate-700 bg-white p-2 rounded border border-slate-200">
            parent(X, Y) ∧ parent(Y, Z) ⇒ grandParent(X, Z)
          </p>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Composition relationnelle permettant de détecter automatiquement les 4 grands-parents de chaque petit-enfant.
          </p>
        </div>

        <!-- Rule 4: Sibling Rule -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-amber-800">4. Règle de Fratrie (SWRL)</span>
            <span class="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-mono">swrl:rule</span>
          </div>
          <p class="text-xs font-mono text-slate-700 bg-white p-2 rounded border border-slate-200">
            parent(P, X) ∧ parent(P, Y) ∧ (X ≠ Y) ⇒ fratrie(X, Y)
          </p>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Deux personnes distinctes partageant au moins un parent sont catégorisées comme frères ou sœurs.
          </p>
        </div>

        <!-- Rule 5: Uncle & Aunt Rule -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-purple-800">5. Règle Oncle / Tante</span>
            <span class="text-[10px] bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded font-mono">swrl:rule</span>
          </div>
          <p class="text-xs font-mono text-slate-700 bg-white p-2 rounded border border-slate-200">
            fratrie(U, P) ∧ parent(P, X) ⇒ oncleOuTante(U, X)
          </p>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Le frère ou la sœur du parent d'un individu est déduit(e) comme oncle ou tante (avec spécialisation de genre).
          </p>
        </div>

        <!-- Rule 6: Transitive Ancestor -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-indigo-800">6. Propriété Transitive</span>
            <span class="text-[10px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded font-mono">owl:TransitiveProperty</span>
          </div>
          <p class="text-xs font-mono text-slate-700 bg-white p-2 rounded border border-slate-200">
            ancetre(X, Y) ∧ ancetre(Y, Z) ⇒ ancetre(X, Z)
          </p>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Fermeture transitive sur l'arbre généalogique jusqu'aux fondateurs les plus anciens.
          </p>
        </div>
      </div>
    </div>

    <!-- Live Inferred Facts Explorer -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-800">
          Tableau des Connaissances Inférees en Direct
        </h3>
        <span class="text-xs text-slate-400">
          Évalué automatiquement par le moteur JavaScript du TP
        </span>
      </div>

      <div class="overflow-x-auto border border-slate-200 rounded-xl">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
            <tr>
              <th class="py-2.5 px-4">Individu 1</th>
              <th class="py-2.5 px-4">Relation Déduite</th>
              <th class="py-2.5 px-4">Individu 2</th>
              <th class="py-2.5 px-4">Axiome Validé</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(fact, idx) in sampleInferredFacts" :key="idx" class="hover:bg-slate-50">
              <td class="py-2.5 px-4 font-bold text-slate-900">{{ fact.person1 }}</td>
              <td class="py-2.5 px-4">
                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-emerald-100 text-emerald-800">
                  {{ fact.relation }}
                </span>
              </td>
              <td class="py-2.5 px-4 font-bold text-slate-700">{{ fact.person2 }}</td>
              <td class="py-2.5 px-4 text-slate-500 font-mono text-[11px]">{{ fact.axiom }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  members,
  stats,
  reasoner
} = useFamilyStore()

const showTurtleCode = ref(false)
const copied = ref(false)

const turtleCode = computed(() => {
  return reasoner.value.exportTurtle()
})

const copyTurtleCode = () => {
  navigator.clipboard.writeText(turtleCode.value)
  copied.value = true
  setTimeout(() => {
    copied.value = false
  }, 2000)
}

const downloadTurtle = () => {
  const blob = new Blob([turtleCode.value], { type: 'text/turtle;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', 'ontologie_famille_carter.ttl')
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const sampleInferredFacts = computed(() => {
  const list = []
  const r = reasoner.value

  for (const m of members.value) {
    // Grandparents
    const gps = r.getGrandParents(m.id)
    for (const gp of gps) {
      list.push({
        person1: gp.name,
        relation: gp.gender === 'M' ? 'Grand-Père de' : 'Grand-Mère de',
        person2: m.name,
        axiom: 'propertyChainAxiom (parent o parent)'
      })
    }

    // Uncles and Aunts
    const uncles = r.getUncles(m.id)
    for (const u of uncles) {
      list.push({
        person1: u.name,
        relation: 'Oncle de',
        person2: m.name,
        axiom: 'swrl:fratrie(u, p) ^ parent(p, x) => oncle(u, x)'
      })
    }

    // Siblings
    const sibs = r.getSiblings(m.id)
    for (const s of sibs) {
      if (m.gender === 'M') {
        list.push({
          person1: m.name,
          relation: 'Frère de',
          person2: s.name,
          axiom: 'swrl:shareParents(x, y) ^ Male(x) => brother(x, y)'
        })
      }
    }
  }

  // De-duplicate and take top 15
  return list.slice(0, 15)
})
</script>


<template>
  <div class="h-full overflow-y-auto p-8 space-y-8 bg-slate-50/60">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-[#1e563d] to-[#2d7d59] rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
      <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
        <svg class="w-64 h-64" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
      </div>

      <div class="relative z-10 max-w-2xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold text-emerald-200 mb-3 backdrop-blur-sm">
          <span>🌿 Arbre Généalogique Sémantique</span>
          <span>•</span>
          <span>IFRI 2026</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight">
          Tableau de Bord Généalogique
        </h1>
        <p class="text-emerald-100/90 text-sm mt-2 leading-relaxed">
          Bienvenue sur l'interface de gestion et de raisonnement sémantique. Visualisez la structure de la famille fictive, les axiomes OWL déduits et explorez les liens intergénérationnels.
        </p>

        <div class="flex flex-wrap items-center gap-3 mt-5">
          <button
            @click="activeMenu = 'tree'"
            class="px-4 py-2 bg-white text-emerald-800 hover:bg-emerald-50 text-xs font-bold rounded-xl transition-all shadow flex items-center gap-2"
          >
            <span>🔗 Voir l'Arbre Familial</span>
          </button>
          <button
            @click="openAddModal"
            class="px-4 py-2 bg-emerald-700/80 hover:bg-emerald-700 text-white border border-emerald-500/50 text-xs font-semibold rounded-xl transition-all flex items-center gap-2"
          >
            <span>➕ Ajouter un nouveau membre</span>
          </button>
          <button
            @click="activeMenu = 'reasoning'"
            class="px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-900 text-xs font-bold rounded-xl transition-all flex items-center gap-2"
          >
            <span>🧠 Voir le raisonnement (OWL)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 5 Specific Summary Cards requested by the user -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <!-- 1. Total Personnes -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Personnes</span>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
            👥
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.totalMembers }}</span>
          <span class="text-xs font-medium text-emerald-600">individus</span>
        </div>
        <div class="mt-2 text-[11px] text-slate-400">
          Base de connaissances active
        </div>
      </div>

      <!-- 2. Nombre d'Hommes -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Hommes</span>
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
            👨
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.menCount }}</span>
          <span class="text-xs font-medium text-blue-600">
            {{ stats.totalMembers > 0 ? Math.round((stats.menCount / stats.totalMembers) * 100) : 0 }}%
          </span>
        </div>
        <div class="mt-2 text-[11px] text-slate-400">
          Classe ontologique :Homme
        </div>
      </div>

      <!-- 3. Nombre de Femmes -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Femmes</span>
          <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
            👩
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.womenCount }}</span>
          <span class="text-xs font-medium text-rose-600">
            {{ stats.totalMembers > 0 ? Math.round((stats.womenCount / stats.totalMembers) * 100) : 0 }}%
          </span>
        </div>
        <div class="mt-2 text-[11px] text-slate-400">
          Classe ontologique :Femme
        </div>
      </div>

      <!-- 4. Familles ou Générations -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Générations</span>
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
            🏛️
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.generationCount }}</span>
          <span class="text-xs font-medium text-amber-600">paliers</span>
        </div>
        <div class="mt-2 text-[11px] text-slate-400">
          De Gen 1 aux Arrière-petits-enfants
        </div>
      </div>

      <!-- 5. Relations Connues (Assertées) -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Relations Connues</span>
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
            🔗
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.assertedRelationsCount }}</span>
          <span class="text-xs font-medium text-purple-600">+{{ stats.inferredRelationsCount }} inférées</span>
        </div>
        <div class="mt-2 text-[11px] text-slate-400">
          Père, Mère, Conjoints directs
        </div>
      </div>
    </div>

    <!-- Secondary Analytic Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Generations Breakdown Card -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-800 flex items-center gap-2 text-base">
            <span>📊 Répartition par Génération</span>
          </h3>
          <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium">
            {{ stats.generationCount }} Niveaux
          </span>
        </div>

        <div class="space-y-3">
          <div 
            v-for="gen in [1, 2, 3, 4]" 
            :key="gen"
            class="p-3 rounded-xl bg-slate-50/80 hover:bg-slate-50 transition-colors border border-slate-100 flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold text-xs flex items-center justify-center">
                G{{ gen }}
              </span>
              <div>
                <p class="text-xs font-bold text-slate-800">{{ getGenLabel(gen) }}</p>
                <p class="text-[11px] text-slate-500">{{ getGenMembers(gen).length }} membre(s)</p>
              </div>
            </div>
            <div class="flex -space-x-2">
              <img
                v-for="m in getGenMembers(gen).slice(0, 3)"
                :key="m.id"
                :src="m.avatar"
                :title="m.name"
                class="w-7 h-7 rounded-full border-2 border-white object-cover shadow-sm"
              />
              <span 
                v-if="getGenMembers(gen).length > 3"
                class="w-7 h-7 rounded-full bg-slate-200 text-[10px] font-bold text-slate-600 flex items-center justify-center border-2 border-white"
              >
                +{{ getGenMembers(gen).length - 3 }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Ontological Inferences Power Card -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-800 flex items-center gap-2 text-base">
            <span>🧠 Raisonnement Sémantique</span>
          </h3>
          <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-0.5 rounded-full">
            Inférence Active
          </span>
        </div>

        <div class="space-y-2 text-xs">
          <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-200/60">
            <div class="font-bold text-amber-900 mb-1 flex items-center gap-1.5">
              <span>⚡ Règle d'inversion & symétrie</span>
            </div>
            <p class="text-amber-800/80 leading-relaxed text-[11px]">
              À partir de <strong>{{ stats.assertedRelationsCount }}</strong> liens saisis manuellement, le moteur a déduit automatiquement <strong>{{ stats.inferredRelationsCount }}</strong> nouvelles relations familiales.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-1">
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Fratries déduites</span>
              <span class="text-lg font-extrabold text-slate-800">{{ statsSiblingCount }}</span>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Oncles & Tantes</span>
              <span class="text-lg font-extrabold text-slate-800">{{ statsUncleAuntCount }}</span>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Cousins déduits</span>
              <span class="text-lg font-extrabold text-slate-800">{{ statsCousinCount }}</span>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Grands-Parents</span>
              <span class="text-lg font-extrabold text-slate-800">{{ statsGrandParentsCount }}</span>
            </div>
          </div>
        </div>

        <button
          @click="activeMenu = 'reasoning'"
          class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-all"
        >
          Consulter toutes les règles et axiomes →
        </button>
      </div>

      <!-- Recent Members Quick List -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-800 flex items-center gap-2 text-base">
            <span>👨‍👩‍👧 Membres de la Famille</span>
          </h3>
          <button
            @click="activeMenu = 'members'"
            class="text-xs text-emerald-700 hover:underline font-semibold"
          >
            Tout voir
          </button>
        </div>

        <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto pr-1">
          <div 
            v-for="m in members.slice(0, 5)" 
            :key="m.id"
            @click="selectMember(m.id)"
            class="py-2.5 flex items-center justify-between hover:bg-slate-50 px-2 rounded-lg cursor-pointer transition-colors"
          >
            <div class="flex items-center gap-3">
              <img :src="m.avatar" class="w-8 h-8 rounded-full object-cover shadow-sm" />
              <div>
                <p class="text-xs font-bold text-slate-800">{{ m.name }}</p>
                <p class="text-[11px] text-slate-400">{{ m.profession || 'Gén. ' + m.generation }}</p>
              </div>
            </div>
            <div class="text-right">
              <span 
                :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded-full',
                  m.gender === 'M' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700'
                ]"
              >
                {{ m.gender === 'M' ? 'Homme' : 'Femme' }}
              </span>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ m.birthYear }}</p>
            </div>
          </div>
        </div>

        <button
          @click="openAddModal"
          class="w-full py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2"
        >
          <span>➕ Ajouter une personne</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  members,
  activeMenu,
  stats,
  reasoner,
  openAddModal,
  selectMember
} = useFamilyStore()

const getGenLabel = (gen) => {
  switch (gen) {
    case 1: return 'Génération 1 — Patriarches / Fondateurs'
    case 2: return 'Génération 2 — Enfants & Conjoints'
    case 3: return 'Génération 3 — Petits-Enfants'
    case 4: return 'Génération 4 — Arrière-Petits-Enfants'
    default: return `Génération ${gen}`
  }
}

const getGenMembers = (gen) => {
  return members.value.filter(m => {
    const calculatedGen = stats.value.genMap.get(m.id) || m.generation || 1
    return calculatedGen === gen
  })
}

const statsSiblingCount = computed(() => {
  let count = 0
  for (const m of members.value) {
    count += reasoner.value.getSiblings(m.id).length
  }
  return Math.floor(count / 2) // paires
})

const statsUncleAuntCount = computed(() => {
  let count = 0
  for (const m of members.value) {
    count += reasoner.value.getUnclesAndAunts(m.id).length
  }
  return count
})

const statsCousinCount = computed(() => {
  let count = 0
  for (const m of members.value) {
    count += reasoner.value.getCousins(m.id).length
  }
  return Math.floor(count / 2) // paires
})

const statsGrandParentsCount = computed(() => {
  let count = 0
  for (const m of members.value) {
    count += reasoner.value.getGrandParents(m.id).length
  }
  return count
})
</script>


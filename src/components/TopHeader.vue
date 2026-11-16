<template>
  <header class="h-20 bg-white border-b border-slate-200/80 px-8 flex items-center justify-between shrink-0 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
    <!-- Left Section: Title & Breadcrumbs -->
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2 text-slate-400 hover:text-slate-600 cursor-pointer lg:hidden">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </div>

      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-800 flex items-center gap-2">
          <span>{{ familyTitle }}</span>
          <span class="text-xs bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full font-semibold">
            {{ currentViewLabel }}
          </span>
        </h2>
        <p class="text-xs text-slate-500 font-medium">
          {{ stats.totalMembers }} individus • {{ stats.generationCount }} générations • {{ stats.assertedRelationsCount }} liens directs (+{{ stats.inferredRelationsCount }} inférés)
        </p>
      </div>
    </div>

    <!-- Right Section: Search & Actions -->
    <div class="flex items-center gap-4">
      <!-- Search Bar -->
      <div class="relative w-64 md:w-80">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </span>
        <input
          v-model="globalSearch"
          type="text"
          placeholder="Rechercher une personne, métier..."
          class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all shadow-sm"
        />
        <button
          v-if="globalSearch"
          @click="globalSearch = ''"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- View Switchers (Arbre / Liste) -->
      <div class="hidden sm:flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
        <button
          @click="activeMenu = 'tree'"
          title="Vue Arbre Généalogique"
          :class="[
            'p-1.5 rounded-md text-sm transition-all',
            activeMenu === 'tree' ? 'bg-white text-emerald-700 shadow-sm font-medium' : 'text-slate-500 hover:text-slate-800'
          ]"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="5" r="3"></circle>
            <circle cx="6" cy="19" r="3"></circle>
            <circle cx="18" cy="19" r="3"></circle>
            <path d="M12 8v4M6 16v-2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"></path>
          </svg>
        </button>
        <button
          @click="activeMenu = 'members'"
          title="Vue Liste / Grille"
          :class="[
            'p-1.5 rounded-md text-sm transition-all',
            activeMenu === 'members' ? 'bg-white text-emerald-700 shadow-sm font-medium' : 'text-slate-500 hover:text-slate-800'
          ]"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Add Member Pill Button -->
      <button
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 border-2 border-emerald-600 hover:border-emerald-700 text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50/70 font-semibold text-sm rounded-full transition-all shadow-sm active:scale-95"
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Ajouter un membre</span>
      </button>

      <!-- Profile Avatar (Étudiante / Utilisateur) -->
      <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
        <div class="relative">
          <img
            :src="currentUser?.avatar || 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=160&auto=format&fit=crop&q=80'"
            :alt="currentUser?.name || 'Persévérance DOSSOU'"
            class="w-9 h-9 rounded-full object-cover ring-2 ring-emerald-500/30 shadow-xs"
          />
          <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full"></span>
        </div>
        <div class="hidden xl:block text-left">
          <span class="text-xs font-bold text-slate-800 block leading-tight">
            {{ currentUser?.name || 'Persévérance DOSSOU' }}
          </span>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  members,
  activeMenu,
  globalSearch,
  stats,
  openAddModal
} = useFamilyStore()

// Titre dynamique de la famille basé sur l'ontologie
const familyTitle = computed(() => {
  const list = members.value || []
  if (list.length > 0) {
    const counts = {}
    for (const m of list) {
      const ln = m.lastName || (m.name ? m.name.split(' ').slice(1).join(' ') : '')
      if (ln) counts[ln] = (counts[ln] || 0) + 1
    }
    const top = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0]
    if (top) return `Famille ${top}`
  }
  return 'Famille DOSSOU'
})

// Profil actif (ex: Persévérance DOSSOU qui a conçu l'ontologie)
const currentUser = computed(() => {
  const list = members.value || []
  return list.find(m => m.id === 'Perseverance_DOSSOU' || m.name?.includes('Persévérance')) || list[0] || null
})

const currentViewLabel = computed(() => {
  switch (activeMenu.value) {
    case 'dashboard': return 'Tableau de bord'
    case 'members': return 'Annuaire des Membres'
    case 'tree': return 'Arbre Généalogique'
    case 'search': return 'Requêtes Sémantiques'
    case 'reasoning': return 'Moteur d\'Inférence'
    default: return 'Famille'
  }
})
</script>

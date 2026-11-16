<template>
  <aside class="w-64 bg-[#1e563d] text-white flex flex-col justify-between select-none shadow-xl z-20 shrink-0">
    <!-- Brand Header -->
    <div>
      <div class="h-20 flex items-center px-6 gap-3 border-b border-[#2a6d4f]">
        <!-- Brand Leaf Icon -->
        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-emerald-300 shadow-inner">
          <svg class="w-6 h-6 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white flex items-center">
            <span>Ances</span><span class="text-emerald-300 font-extrabold">Tree</span>
          </h1>
        </div>
      </div>

      <!-- Navigation List -->
      <nav class="p-3 space-y-1.5 mt-2">
        <!-- 🏠 Tableau de bord -->
        <button
          @click="activeMenu = 'dashboard'"
          :class="[
            'w-full flex items-center gap-3 px-3.5 py-3 rounded-lg text-sm font-medium transition-all text-left relative group',
            activeMenu === 'dashboard' 
              ? 'bg-[#296a4d] text-white shadow-sm' 
              : 'text-emerald-100/80 hover:bg-[#256247] hover:text-white'
          ]"
        >
          <!-- Active orange vertical indicator line like screenshot -->
          <span 
            v-if="activeMenu === 'dashboard'" 
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-amber-400 rounded-r-full"
          ></span>
          <span class="text-lg">🏠</span>
          <span class="flex-1">Tableau de bord</span>
          <span class="text-xs bg-emerald-950/40 text-emerald-200 px-2 py-0.5 rounded-full font-mono">
            {{ stats.totalMembers }}
          </span>
        </button>

        <!-- 👨‍👩‍👧 Membres de la famille -->
        <button
          @click="activeMenu = 'members'"
          :class="[
            'w-full flex items-center gap-3 px-3.5 py-3 rounded-lg text-sm font-medium transition-all text-left relative group',
            activeMenu === 'members' 
              ? 'bg-[#296a4d] text-white shadow-sm' 
              : 'text-emerald-100/80 hover:bg-[#256247] hover:text-white'
          ]"
        >
          <span 
            v-if="activeMenu === 'members'" 
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-amber-400 rounded-r-full"
          ></span>
          <span class="text-lg">👨‍👩‍👧</span>
          <span class="flex-1">Membres de la famille</span>
        </button>

        <!-- 🔗 Relations familiales (Family Tree) -->
        <button
          @click="activeMenu = 'tree'"
          :class="[
            'w-full flex items-center gap-3 px-3.5 py-3 rounded-lg text-sm font-medium transition-all text-left relative group',
            activeMenu === 'tree' 
              ? 'bg-[#296a4d] text-white shadow-sm' 
              : 'text-emerald-100/80 hover:bg-[#256247] hover:text-white'
          ]"
        >
          <span 
            v-if="activeMenu === 'tree'" 
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-amber-400 rounded-r-full"
          ></span>
          <span class="text-lg">🔗</span>
          <span class="flex-1">Relations familiales</span>
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        </button>

        <!-- 🔍 Recherche sémantique -->
        <button
          @click="activeMenu = 'search'"
          :class="[
            'w-full flex items-center gap-3 px-3.5 py-3 rounded-lg text-sm font-medium transition-all text-left relative group',
            activeMenu === 'search' 
              ? 'bg-[#296a4d] text-white shadow-sm' 
              : 'text-emerald-100/80 hover:bg-[#256247] hover:text-white'
          ]"
        >
          <span 
            v-if="activeMenu === 'search'" 
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-amber-400 rounded-r-full"
          ></span>
          <span class="text-lg">🔍</span>
          <span class="flex-1">Recherche sémantique</span>
        </button>

        <!-- 🧠 Raisonnement -->
        <button
          @click="activeMenu = 'reasoning'"
          :class="[
            'w-full flex items-center gap-3 px-3.5 py-3 rounded-lg text-sm font-medium transition-all text-left relative group',
            activeMenu === 'reasoning' 
              ? 'bg-[#296a4d] text-white shadow-sm' 
              : 'text-emerald-100/80 hover:bg-[#256247] hover:text-white'
          ]"
        >
          <span 
            v-if="activeMenu === 'reasoning'" 
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-amber-400 rounded-r-full"
          ></span>
          <span class="text-lg">🧠</span>
          <span class="flex-1">Raisonnement</span>
          <span class="text-xs bg-amber-400 text-emerald-950 font-bold px-1.5 py-0.5 rounded-full">
            OWL
          </span>
        </button>
      </nav>
    </div>

    <!-- Bottom Footer Card & Actions -->
    <div class="p-4 border-t border-[#2a6d4f] space-y-3">

      <!-- Pastille Statut Connexion API + Fuseki -->
      <div 
        @click="checkConnection"
        :title="isConnected ? 'Cliquez pour réactualiser l\'état' : 'Cliquez pour tenter de vous reconnecter à l\'API'"
        :class="[
          'p-2.5 rounded-xl border text-xs cursor-pointer transition-all flex items-center justify-between',
          isConnected 
            ? 'bg-emerald-950/60 border-emerald-600/40 text-emerald-200 hover:bg-emerald-950/80' 
            : 'bg-rose-950/50 border-rose-700/40 text-rose-200 hover:bg-rose-950/70'
        ]"
      >
        <div class="flex items-center gap-2.5">
          <span 
            :class="[
              'w-2.5 h-2.5 rounded-full shrink-0',
              isCheckingConnection ? 'bg-amber-400 animate-ping' : (isConnected ? 'bg-emerald-400 ring-2 ring-emerald-400/40' : 'bg-rose-500')
            ]"
          ></span>
          <div>
            <div class="font-bold text-[11px] leading-tight">
              {{ isConnected ? 'API + Fuseki connectés' : 'Déconnecté du backend' }}
            </div>
            <div class="text-[10px] opacity-75">
              {{ isConnected ? `${tripleCount} triplets RDF actifs` : 'Mode local (cliquez pour tester)' }}
            </div>
          </div>
        </div>
        <span class="text-xs opacity-70 hover:opacity-100">
          🔄
        </span>
      </div>

      <!-- Quick Reset Button -->
      <button
        @click="resetToDefault"
        title="Réinitialiser l'arbre aux données d'origine"
        class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-medium text-emerald-200/80 hover:text-white hover:bg-[#256247] transition-all"
      >
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
          <path d="M3 3v5h5"/>
        </svg>
        <span>Réinitialiser les données</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  activeMenu,
  stats,
  openAddModal,
  resetToDefault,
  isConnected,
  isCheckingConnection,
  tripleCount,
  checkConnection
} = useFamilyStore()
</script>


<template>
  <div class="h-full overflow-y-auto p-8 space-y-6 bg-slate-50/60">
    <!-- Header with controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span>👨‍👩‍👧 Gestion des Membres</span>
          <span class="text-xs font-semibold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">
            {{ filteredMembers.length }} / {{ members.length }}
          </span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
          Consultez, modifiez ou ajoutez les membres de la famille fictive.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Gender Filter -->
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
          <button
            @click="genderFilter = 'ALL'"
            :class="[
              'px-3 py-1.5 rounded-lg font-semibold transition-all',
              genderFilter === 'ALL' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            Tous
          </button>
          <button
            @click="genderFilter = 'M'"
            :class="[
              'px-3 py-1.5 rounded-lg font-semibold transition-all',
              genderFilter === 'M' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            👨 Hommes ({{ stats.menCount }})
          </button>
          <button
            @click="genderFilter = 'F'"
            :class="[
              'px-3 py-1.5 rounded-lg font-semibold transition-all',
              genderFilter === 'F' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            👩 Femmes ({{ stats.womenCount }})
          </button>
        </div>

        <!-- Generation Filter -->
        <select
          v-model="generationFilter"
          class="bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 py-2 px-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="ALL">Toutes les générations</option>
          <option value="1">Génération 1 (Patriarches)</option>
          <option value="2">Génération 2 (Enfants)</option>
          <option value="3">Génération 3 (Petits-enfants)</option>
          <option value="4">Génération 4 (Arrière-petits-enfants)</option>
        </select>

        <!-- View Mode: Cards or Table -->
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button
            @click="viewMode = 'grid'"
            title="Affichage Cartes"
            :class="[
              'p-1.5 rounded-lg text-xs transition-all',
              viewMode === 'grid' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500'
            ]"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
          </button>
          <button
            @click="viewMode = 'table'"
            title="Affichage Tableau"
            :class="[
              'p-1.5 rounded-lg text-xs transition-all',
              viewMode === 'table' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500'
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

      </div>
    </div>

    <!-- Grid Cards View -->
    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <div
        v-for="person in filteredMembers"
        :key="person.id"
        class="bg-white rounded-2xl border border-slate-200/90 hover:border-emerald-300 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group relative"
      >
        <!-- Top info -->
        <div>
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="relative">
                <img
                  :src="person.avatar"
                  :alt="person.name"
                  class="w-13 h-13 w-12 h-12 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-emerald-400 transition-all shadow-sm"
                />
                <span
                  :class="[
                    'absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white',
                    person.gender === 'M' ? 'bg-blue-500' : 'bg-rose-500'
                  ]"
                ></span>
              </div>
              <div>
                <h3 class="font-bold text-slate-900 text-sm group-hover:text-emerald-800 transition-colors">
                  {{ person.name }}
                </h3>
                <p class="text-xs text-slate-500">
                  {{ person.birthYear }} {{ person.deathYear ? '– ' + person.deathYear : '(Âge: ' + person.age + ' ans)' }}
                </p>
              </div>
            </div>

            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
              Gen {{ person.generation || 1 }}
            </span>
          </div>

          <!-- Profession / Title -->
          <div class="mt-3 text-xs text-emerald-700 font-medium bg-emerald-50/60 px-2.5 py-1 rounded-lg">
            💼 {{ person.profession || 'Membre de la famille' }}
          </div>

          <!-- Direct Relations Summary -->
          <div class="mt-4 space-y-1.5 text-xs border-t border-slate-100 pt-3">
            <!-- Parents -->
            <div class="flex items-center justify-between text-slate-600">
              <span class="text-slate-400 font-medium">Père :</span>
              <span class="font-semibold text-slate-800 truncate max-w-[150px]">
                {{ getFatherName(person.id) || '—' }}
              </span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span class="text-slate-400 font-medium">Mère :</span>
              <span class="font-semibold text-slate-800 truncate max-w-[150px]">
                {{ getMotherName(person.id) || '—' }}
              </span>
            </div>
            <!-- Conjoint -->
            <div class="flex items-center justify-between text-slate-600">
              <span class="text-slate-400 font-medium">Conjoint(e) :</span>
              <span class="font-semibold text-rose-600 truncate max-w-[150px]">
                {{ getSpouseName(person.id) || '—' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Card Bottom Actions -->
        <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button
            @click="selectMember(person.id)"
            class="flex-1 py-1.5 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-800 text-slate-700 text-xs font-bold rounded-lg transition-all text-center"
          >
            Voir profil
          </button>
          <button
            @click="goToTree(person.id)"
            title="Afficher dans l'arbre"
            class="p-1.5 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="5" r="3"></circle>
              <circle cx="6" cy="19" r="3"></circle>
              <circle cx="18" cy="19" r="3"></circle>
              <path d="M12 8v4M6 16v-2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
          <button
            @click="confirmDelete(person)"
            title="Supprimer ce membre"
            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-700">
          <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
            <tr>
              <th class="py-3.5 px-4">Membre</th>
              <th class="py-3.5 px-4">Sexe</th>
              <th class="py-3.5 px-4">Âge / Année</th>
              <th class="py-3.5 px-4">Génération</th>
              <th class="py-3.5 px-4">Père</th>
              <th class="py-3.5 px-4">Mère</th>
              <th class="py-3.5 px-4">Conjoint(e)</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="person in filteredMembers"
              :key="person.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="py-3 px-4 flex items-center gap-3">
                <img :src="person.avatar" class="w-8 h-8 rounded-full object-cover shadow-sm" />
                <div>
                  <p class="font-bold text-slate-900">{{ person.name }}</p>
                  <p class="text-[11px] text-slate-400">{{ person.profession }}</p>
                </div>
              </td>
              <td class="py-3 px-4">
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full font-bold text-[10px]',
                    person.gender === 'M' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700'
                  ]"
                >
                  {{ person.gender === 'M' ? 'Homme' : 'Femme' }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium">
                {{ person.age }} ans <span class="text-slate-400">({{ person.birthYear }})</span>
              </td>
              <td class="py-3 px-4 font-bold text-emerald-800">
                G{{ person.generation || 1 }}
              </td>
              <td class="py-3 px-4 text-slate-600">
                {{ getFatherName(person.id) || '—' }}
              </td>
              <td class="py-3 px-4 text-slate-600">
                {{ getMotherName(person.id) || '—' }}
              </td>
              <td class="py-3 px-4 font-semibold text-rose-600">
                {{ getSpouseName(person.id) || '—' }}
              </td>
              <td class="py-3 px-4 text-right space-x-2">
                <button
                  @click="selectMember(person.id)"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 font-bold rounded"
                >
                  Détails
                </button>
                <button
                  @click="confirmDelete(person)"
                  class="px-2 py-1 text-rose-600 hover:bg-rose-50 font-bold rounded"
                >
                  Supprimer
                </button>
              </td>
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
  activeMenu,
  globalSearch,
  stats,
  reasoner,
  openAddModal,
  selectMember,
  deleteMember
} = useFamilyStore()

const viewMode = ref('grid') // 'grid' | 'table'
const genderFilter = ref('ALL') // 'ALL' | 'M' | 'F'
const generationFilter = ref('ALL') // 'ALL' | 1 | 2 | 3 | 4

const filteredMembers = computed(() => {
  return members.value.filter(m => {
    // Search query
    if (globalSearch.value) {
      const q = globalSearch.value.toLowerCase()
      const matchName = m.name.toLowerCase().includes(q)
      const matchProf = (m.profession || '').toLowerCase().includes(q)
      if (!matchName && !matchProf) return false
    }

    // Gender filter
    if (genderFilter.value !== 'ALL' && m.gender !== genderFilter.value) {
      return false
    }

    // Generation filter
    if (generationFilter.value !== 'ALL') {
      const gen = Number(generationFilter.value)
      if ((m.generation || 1) !== gen) return false
    }

    return true
  })
})

const getFatherName = (id) => {
  const f = reasoner.value.getFather(id)
  return f ? f.name : null
}

const getMotherName = (id) => {
  const m = reasoner.value.getMother(id)
  return m ? m.name : null
}

const getSpouseName = (id) => {
  const s = reasoner.value.getSpouse(id)
  return s ? s.name : null
}

const goToTree = (id) => {
  selectMember(id)
  activeMenu.value = 'tree'
}

const confirmDelete = (person) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer ${person.name} de la famille ? Ses liens familiaux seront déliés.`)) {
    deleteMember(person.id)
  }
}
</script>


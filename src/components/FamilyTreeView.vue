<template>
  <div class="relative w-full h-full overflow-hidden bg-[#d7ebe2] select-none flex flex-col">
    <!-- Top Floating Toolbar inside the canvas -->
    <div class="absolute top-4 left-6 right-6 z-10 flex items-center justify-between pointer-events-none">
      <!-- Title & Legend -->
      <div class="pointer-events-auto bg-white/90 backdrop-blur-md px-4 py-2.5 rounded-2xl border border-emerald-900/10 shadow-sm flex items-center gap-4 text-xs">
        <div>
          <span class="font-bold text-slate-800 text-sm">Arbre Généalogique</span>
          <span class="text-slate-500 block text-[11px]">Représentation visuelle des liens d'ascendance et d'alliance</span>
        </div>
        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
        <!-- Legend -->
        <div class="hidden md:flex items-center gap-4 text-[11px] font-medium text-slate-600">
          <div class="flex items-center gap-1.5">
            <span class="w-4 h-0.5 bg-emerald-600 rounded"></span>
            <span>Filiation (Parent-Enfant)</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-4 h-0.5 border-t-2 border-dashed border-rose-500"></span>
            <span>Alliance (Conjoint)</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span>Sélection active</span>
          </div>
        </div>
      </div>

      <!-- Canvas Controls: Zoom, Reset, Add -->
      <div class="pointer-events-auto flex items-center gap-2 bg-white/90 backdrop-blur-md p-1.5 rounded-2xl border border-emerald-900/10 shadow-sm">
        <button
          @click="zoomIn"
          title="Zoomer (+)"
          class="w-8 h-8 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-700 transition-colors"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            <line x1="11" y1="8" x2="11" y2="14"></line>
            <line x1="8" y1="11" x2="14" y2="11"></line>
          </svg>
        </button>
        <button
          @click="zoomOut"
          title="Dézoomer (-)"
          class="w-8 h-8 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-700 transition-colors"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            <line x1="8" y1="11" x2="14" y2="11"></line>
          </svg>
        </button>
        <button
          @click="resetZoom"
          title="Réinitialiser l'échelle"
          class="px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
        >
          {{ Math.round(zoomLevel * 100) }}%
        </button>
        <div class="h-4 w-px bg-slate-200"></div>
        <button
          @click="openAddModal"
          class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs rounded-xl transition-all shadow-sm flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>Nouveau membre</span>
        </button>
      </div>
    </div>

    <!-- Scrollable Canvas Area with Subtle Organic Pattern Background -->
    <div 
      ref="canvasContainer"
      class="flex-1 overflow-auto p-12 cursor-grab active:cursor-grabbing relative"
      style="background: radial-gradient(circle at 10% 20%, rgba(30, 86, 61, 0.05) 0%, transparent 20%), radial-gradient(circle at 90% 80%, rgba(30, 86, 61, 0.05) 0%, transparent 25%), #d7ebe2;"
    >
      <!-- Background subtle network graphics like screenshot -->
      <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-25" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="network-dots" x="0" y="0" width="120" height="120" patternUnits="userSpaceOnUse">
            <circle cx="20" cy="20" r="1.5" fill="#2d7d59" opacity="0.4" />
            <circle cx="80" cy="60" r="2.5" fill="#2d7d59" opacity="0.5" />
            <circle cx="40" cy="100" r="1.5" fill="#2d7d59" opacity="0.3" />
            <path d="M20 20 L80 60 L40 100" fill="none" stroke="#2d7d59" stroke-width="0.8" opacity="0.25" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#network-dots)" />
      </svg>

      <!-- Zoomable Scaled Tree Container -->
      <div 
        class="min-w-[1200px] min-h-[850px] mx-auto py-12 px-8 transition-transform duration-150 origin-top flex flex-col items-center justify-start space-y-16"
        :style="{ transform: `scale(${zoomLevel})` }"
      >

        <!-- Generation 1 (Patriarches / Fondateurs) -->
        <div class="flex flex-col items-center relative">
          <div class="mb-3 px-3 py-1 bg-white/70 backdrop-blur-sm rounded-full text-[11px] font-bold text-emerald-900 border border-emerald-900/10 shadow-xs">
            Génération 1 — Les Fondateurs
          </div>

          <div class="flex items-center gap-10 relative">
            <template v-for="person in gen1Members" :key="person.id">
              <!-- Member Card -->
              <div 
                @click="handleCardClick(person.id)"
                :class="[
                  'group bg-white rounded-full py-1.5 pl-1.5 pr-5 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-3 relative z-10',
                  selectedMemberId === person.id 
                    ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                    : isRelated(person.id)
                    ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                    : 'border-slate-200 hover:border-emerald-300'
                ]"
                style="min-width: 210px;"
              >
                <img 
                  :src="person.avatar" 
                  class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-emerald-400 transition-all shadow-xs" 
                />
                <div class="truncate text-left">
                  <h4 class="text-xs font-bold text-slate-800 group-hover:text-emerald-900 truncate">
                    {{ person.name }}
                  </h4>
                  <p class="text-[10px] text-slate-400">
                    {{ person.birthYear }} – {{ person.deathYear || person.age + ' ans' }}
                  </p>
                </div>
              </div>
            </template>

            <!-- Spousal Line between Willow Carter & John Carter -->
            <div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 w-10 border-t-2 border-dashed border-rose-400 z-0"></div>
          </div>

          <!-- Vertical Branch Line descending from Gen 1 to Gen 2 -->
          <div class="w-0.5 h-16 bg-emerald-600/70 mt-2 relative">
            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-emerald-600"></span>
          </div>
        </div>

        <!-- Generation 2 (Enfants & Leurs Conjoints) -->
        <div class="w-full flex flex-col items-center relative">
          <div class="mb-3 px-3 py-1 bg-white/70 backdrop-blur-sm rounded-full text-[11px] font-bold text-emerald-900 border border-emerald-900/10 shadow-xs">
            Génération 2 — Les Enfants & Alliances
          </div>

          <!-- Horizontal descent bar across branches -->
          <div class="w-4/5 h-0.5 bg-emerald-600/70 mb-6 relative">
            <!-- Downward connectors to children couples -->
            <span class="absolute left-1/4 top-0 w-0.5 h-6 bg-emerald-600/70"></span>
            <span class="absolute right-1/4 top-0 w-0.5 h-6 bg-emerald-600/70"></span>
          </div>

          <div class="w-full flex justify-around items-start gap-12">
            <!-- Branch A : William Carter & Sophia Adams -->
            <div class="flex flex-col items-center">
              <div class="flex items-center gap-6 relative">
                <template v-for="person in branchA_Gen2" :key="person.id">
                  <div 
                    @click="handleCardClick(person.id)"
                    :class="[
                      'group bg-white rounded-full py-1.5 pl-1.5 pr-5 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-3 relative z-10',
                      selectedMemberId === person.id 
                        ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                        : isRelated(person.id)
                        ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                        : 'border-slate-200 hover:border-emerald-300'
                    ]"
                    style="min-width: 200px;"
                  >
                    <img 
                      :src="person.avatar" 
                      class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-emerald-400 transition-all shadow-xs" 
                    />
                    <div class="truncate text-left">
                      <h4 class="text-xs font-bold text-slate-800 group-hover:text-emerald-900 truncate">
                        {{ person.name }}
                      </h4>
                      <p class="text-[10px] text-slate-400">
                        {{ person.birthYear }} {{ person.deathYear ? '– ' + person.deathYear : '(Âge: ' + person.age + ')' }}
                      </p>
                    </div>
                  </div>
                </template>
                <!-- Dotted spouse link -->
                <div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 w-6 border-t-2 border-dashed border-rose-400 z-0"></div>
              </div>

              <!-- Descent line to Gen 3 Branch A -->
              <div class="w-0.5 h-14 bg-emerald-600/70 mt-2"></div>
            </div>

            <!-- Branch B : Grace Carter & Ethan Mitchell -->
            <div class="flex flex-col items-center">
              <div class="flex items-center gap-6 relative">
                <template v-for="person in branchB_Gen2" :key="person.id">
                  <div 
                    @click="handleCardClick(person.id)"
                    :class="[
                      'group bg-white rounded-full py-1.5 pl-1.5 pr-5 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-3 relative z-10',
                      selectedMemberId === person.id 
                        ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                        : isRelated(person.id)
                        ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                        : 'border-slate-200 hover:border-emerald-300'
                    ]"
                    style="min-width: 200px;"
                  >
                    <img 
                      :src="person.avatar" 
                      class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-emerald-400 transition-all shadow-xs" 
                    />
                    <div class="truncate text-left">
                      <h4 class="text-xs font-bold text-slate-800 group-hover:text-emerald-900 truncate">
                        {{ person.name }}
                      </h4>
                      <p class="text-[10px] text-slate-400">
                        {{ person.birthYear }} {{ person.deathYear ? '– ' + person.deathYear : '(Âge: ' + person.age + ')' }}
                      </p>
                    </div>
                  </div>
                </template>
                <!-- Dotted spouse link -->
                <div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 w-6 border-t-2 border-dashed border-rose-400 z-0"></div>
              </div>

              <!-- Descent line to Gen 3 Branch B -->
              <div class="w-0.5 h-14 bg-emerald-600/70 mt-2"></div>
            </div>
          </div>
        </div>

        <!-- Generation 3 (Petits-enfants & Conjoints) -->
        <div class="w-full flex flex-col items-center relative">
          <div class="mb-3 px-3 py-1 bg-white/70 backdrop-blur-sm rounded-full text-[11px] font-bold text-emerald-900 border border-emerald-900/10 shadow-xs">
            Génération 3 — Les Petits-Enfants
          </div>

          <div class="w-full flex justify-around items-start gap-8">
            <!-- Branch A.1: Ryan Simmons & Olivia Baker + Sierra Adams -->
            <div class="flex flex-col items-center space-y-4">
              <div class="flex items-center gap-4">
                <!-- Sierra Adams (Célibataire) -->
                <div 
                  v-if="sierraPerson"
                  @click="handleCardClick(sierraPerson.id)"
                  :class="[
                    'group bg-white rounded-full py-1.5 pl-1.5 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-2.5 relative z-10',
                    selectedMemberId === sierraPerson.id 
                      ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                      : isRelated(sierraPerson.id)
                      ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                      : 'border-slate-200 hover:border-emerald-300'
                  ]"
                  style="min-width: 185px;"
                >
                  <img :src="sierraPerson.avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 shadow-xs" />
                  <div class="truncate text-left">
                    <h4 class="text-xs font-bold text-slate-800 truncate">{{ sierraPerson.name }}</h4>
                    <p class="text-[10px] text-slate-400">{{ sierraPerson.age }} ans</p>
                  </div>
                </div>

                <!-- Ryan Simmons & Olivia Baker (Couple) -->
                <div class="flex items-center gap-4 relative">
                  <template v-for="person in ryanOliviaCouple" :key="person.id">
                    <div 
                      @click="handleCardClick(person.id)"
                      :class="[
                        'group bg-white rounded-full py-1.5 pl-1.5 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-2.5 relative z-10',
                        selectedMemberId === person.id 
                          ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                          : isRelated(person.id)
                          ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                          : 'border-slate-200 hover:border-emerald-300'
                      ]"
                      style="min-width: 185px;"
                    >
                      <img :src="person.avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 shadow-xs" />
                      <div class="truncate text-left">
                        <h4 class="text-xs font-bold text-slate-800 truncate">{{ person.name }}</h4>
                        <p class="text-[10px] text-slate-400">{{ person.age }} ans</p>
                      </div>
                    </div>
                  </template>
                  <div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 w-4 border-t-2 border-dashed border-rose-400 z-0"></div>
                </div>
              </div>

              <!-- Descent to Gen 4 (Daniel Harrison & Lana Del Ray) -->
              <div class="w-0.5 h-12 bg-emerald-600/70"></div>
            </div>

            <!-- Branch B.1: Orlando Mitchell & Nithya Menon -->
            <div class="flex flex-col items-center space-y-4">
              <div class="flex items-center gap-4 relative">
                <template v-for="person in orlandoNithyaCouple" :key="person.id">
                  <div 
                    @click="handleCardClick(person.id)"
                    :class="[
                      'group bg-white rounded-full py-1.5 pl-1.5 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-2.5 relative z-10',
                      selectedMemberId === person.id 
                        ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                        : isRelated(person.id)
                        ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                        : 'border-slate-200 hover:border-emerald-300'
                    ]"
                    style="min-width: 185px;"
                  >
                    <img :src="person.avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 shadow-xs" />
                    <div class="truncate text-left">
                      <h4 class="text-xs font-bold text-slate-800 truncate">{{ person.name }}</h4>
                      <p class="text-[10px] text-slate-400">{{ person.age }} ans</p>
                    </div>
                  </div>
                </template>
                <div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 w-4 border-t-2 border-dashed border-rose-400 z-0"></div>
              </div>

              <!-- Descent to Gen 4 (Ethan Mitchell Jr.) -->
              <div class="w-0.5 h-12 bg-emerald-600/70"></div>
            </div>
          </div>
        </div>

        <!-- Generation 4 (Arrière-petits-enfants) -->
        <div class="w-full flex flex-col items-center relative">
          <div class="mb-3 px-3 py-1 bg-white/70 backdrop-blur-sm rounded-full text-[11px] font-bold text-emerald-900 border border-emerald-900/10 shadow-xs">
            Génération 4 — Les Arrière-Petits-Enfants
          </div>

          <div class="w-full flex justify-around items-center">
            <!-- Children of Ryan & Olivia -->
            <div class="flex items-center gap-6">
              <template v-for="person in ryanChildren" :key="person.id">
                <div 
                  @click="handleCardClick(person.id)"
                  :class="[
                    'group bg-white rounded-full py-1.5 pl-1.5 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-2.5 relative z-10',
                    selectedMemberId === person.id 
                      ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                      : isRelated(person.id)
                      ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                      : 'border-slate-200 hover:border-emerald-300'
                  ]"
                  style="min-width: 180px;"
                >
                  <img :src="person.avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 shadow-xs" />
                  <div class="truncate text-left">
                    <h4 class="text-xs font-bold text-slate-800 truncate">{{ person.name }}</h4>
                    <p class="text-[10px] text-slate-400">{{ person.age }} ans</p>
                  </div>
                </div>
              </template>
            </div>

            <!-- Children of Orlando & Nithya -->
            <div class="flex items-center gap-6">
              <template v-for="person in orlandoChildren" :key="person.id">
                <div 
                  @click="handleCardClick(person.id)"
                  :class="[
                    'group bg-white rounded-full py-1.5 pl-1.5 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-2.5 relative z-10',
                    selectedMemberId === person.id 
                      ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                      : isRelated(person.id)
                      ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/20'
                      : 'border-slate-200 hover:border-emerald-300'
                  ]"
                  style="min-width: 180px;"
                >
                  <img :src="person.avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 shadow-xs" />
                  <div class="truncate text-left">
                    <h4 class="text-xs font-bold text-slate-800 truncate">{{ person.name }}</h4>
                    <p class="text-[10px] text-slate-400">{{ person.age }} ans</p>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Extra / Newly Added Custom Members that don't fit default branches -->
        <div v-if="customMembers.length > 0" class="w-full flex flex-col items-center pt-8 border-t border-emerald-900/10">
          <div class="mb-3 px-3 py-1 bg-white/70 backdrop-blur-sm rounded-full text-[11px] font-bold text-emerald-900 border border-emerald-900/10 shadow-xs">
            ✨ Nouveaux Membres Ajoutés ({{ customMembers.length }})
          </div>
          <div class="flex flex-wrap justify-center gap-4">
            <div 
              v-for="person in customMembers" 
              :key="person.id"
              @click="handleCardClick(person.id)"
              :class="[
                'group bg-white rounded-full py-1.5 pl-1.5 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-2.5 relative z-10',
                selectedMemberId === person.id 
                  ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30 scale-105' 
                  : 'border-slate-200 hover:border-emerald-300'
              ]"
            >
              <img :src="person.avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 shadow-xs" />
              <div class="truncate text-left">
                <h4 class="text-xs font-bold text-slate-800 truncate">{{ person.name }}</h4>
                <p class="text-[10px] text-slate-400">Gen {{ person.generation || 1 }} • {{ person.age }} ans</p>
              </div>
            </div>
          </div>
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
  selectedMemberId,
  selectedMemberRelations,
  reasoner,
  selectMember,
  openAddModal
} = useFamilyStore()

const zoomLevel = ref(1.0)
const canvasContainer = ref(null)

const zoomIn = () => {
  if (zoomLevel.value < 1.5) zoomLevel.value = +(zoomLevel.value + 0.1).toFixed(1)
}

const zoomOut = () => {
  if (zoomLevel.value > 0.6) zoomLevel.value = +(zoomLevel.value - 0.1).toFixed(1)
}

const resetZoom = () => {
  zoomLevel.value = 1.0
}

const handleCardClick = (id) => {
  if (selectedMemberId.value === id) {
    selectMember(null) // toggle off
  } else {
    selectMember(id)
  }
}

// Check if a person is directly related to the currently selected member
const isRelated = (personId) => {
  if (!selectedMemberId.value || selectedMemberId.value === personId) return false
  const rels = selectedMemberRelations.value
  if (!rels) return false

  return (
    rels.parents?.some(p => p.id === personId) ||
    rels.children?.some(c => c.id === personId) ||
    rels.siblings?.some(s => s.id === personId) ||
    rels.spouse?.id === personId
  )
}

// Subsets of members for organized structured layout matching the screenshot
const gen1Members = computed(() => {
  return members.value.filter(m => m.id === 'm1' || m.id === 'm2')
})

const branchA_Gen2 = computed(() => {
  return members.value.filter(m => m.id === 'm3' || m.id === 'm4')
})

const branchB_Gen2 = computed(() => {
  return members.value.filter(m => m.id === 'm5' || m.id === 'm6')
})

const sierraPerson = computed(() => {
  return members.value.find(m => m.id === 'm7')
})

const ryanOliviaCouple = computed(() => {
  return members.value.filter(m => m.id === 'm8' || m.id === 'm9')
})

const orlandoNithyaCouple = computed(() => {
  return members.value.filter(m => m.id === 'm10' || m.id === 'm11')
})

const ryanChildren = computed(() => {
  return members.value.filter(m => m.fatherId === 'm8' || m.motherId === 'm9' || m.id === 'm12' || m.id === 'm13')
})

const orlandoChildren = computed(() => {
  return members.value.filter(m => m.fatherId === 'm10' || m.motherId === 'm11' || m.id === 'm14')
})

// Any other custom member added by the user
const knownStaticIds = new Set(['m1', 'm2', 'm3', 'm4', 'm5', 'm6', 'm7', 'm8', 'm9', 'm10', 'm11', 'm12', 'm13', 'm14'])
const customMembers = computed(() => {
  return members.value.filter(m => !knownStaticIds.has(m.id))
})
</script>


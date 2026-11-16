<template>
  <div class="relative w-full h-full overflow-hidden bg-[#d7ebe2] select-none flex flex-col">
    <!-- Top Floating Toolbar inside the canvas -->
    <div class="absolute top-4 left-6 right-6 z-10 flex items-center justify-between pointer-events-none">
      <!-- Title & Legend -->
      <div class="pointer-events-auto bg-white/95 backdrop-blur-md px-4 py-2.5 rounded-2xl border border-emerald-900/10 shadow-sm flex items-center gap-4 text-xs">
        <div>
          <span class="font-bold text-slate-800 text-sm">Arbre Généalogique Sémantique</span>
          <span class="text-slate-500 block text-[11px]">
            {{ members.length }} membres • Cliquez sur un membre pour révéler toutes ses relations déduites
          </span>
        </div>
        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
        <!-- Legend -->
        <div class="hidden md:flex items-center gap-3 text-[11px] font-medium text-slate-600">
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-300"></span>
            <span>Membre sélectionné</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 ring-2 ring-amber-200"></span>
            <span>Relation inférée</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-4 h-0.5 border-t-2 border-dashed border-rose-400"></span>
            <span>Mariage 💍</span>
          </div>
        </div>
      </div>

      <!-- Canvas Controls: Zoom, Reset, Add -->
      <div class="pointer-events-auto flex items-center gap-2 bg-white/95 backdrop-blur-md p-1.5 rounded-2xl border border-emerald-900/10 shadow-sm">
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
      <!-- Background subtle network graphics -->
      <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-20" xmlns="http://www.w3.org/2000/svg">
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
        class="min-w-[1300px] min-h-[900px] mx-auto py-12 px-8 transition-transform duration-150 origin-top flex flex-col items-center justify-start space-y-14"
        :style="{ transform: `scale(${zoomLevel})` }"
      >
        <!-- Dynamic Generations Render -->
        <div 
          v-for="(genGroup, gIdx) in generationGroups" 
          :key="genGroup.generation"
          class="w-full flex flex-col items-center relative"
        >
          <!-- Generation Header Badge -->
          <div class="mb-5 px-4 py-1 bg-white/90 backdrop-blur-sm rounded-full text-xs font-bold text-emerald-950 border border-emerald-900/10 shadow-xs flex items-center gap-2">
            <span>{{ getGenerationLabel(genGroup.generation) }}</span>
            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.2 rounded-full font-semibold">
              {{ genGroup.membersCount }} membre(s)
            </span>
          </div>

          <!-- Generation Units (Couples & Individuals) -->
          <div class="flex flex-wrap justify-center items-center gap-8 relative z-10 max-w-full">
            <div 
              v-for="unit in genGroup.units" 
              :key="unit.id"
              class="flex items-center gap-3 relative p-2 bg-white/40 backdrop-blur-xs rounded-3xl border border-emerald-900/5 shadow-xs"
            >
              <!-- Card Member 1 -->
              <div 
                @click="handleCardClick(unit.member1.id)"
                :class="[
                  'group bg-white rounded-2xl py-2 pl-2 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-3 relative z-10',
                  selectedMemberId === unit.member1.id 
                    ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/50 scale-105' 
                    : getRel(unit.member1.id)
                    ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/40 scale-102'
                    : 'border-slate-200 hover:border-emerald-300'
                ]"
                style="min-width: 200px;"
              >
                <div class="relative shrink-0">
                  <img 
                    :src="unit.member1.avatar" 
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-emerald-400 transition-all shadow-xs" 
                  />
                  <span 
                    :class="[
                      'absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white',
                      unit.member1.gender === 'M' ? 'bg-blue-500' : 'bg-rose-500'
                    ]"
                  ></span>
                </div>
                <div class="truncate text-left flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <h4 class="text-xs font-bold text-slate-900 group-hover:text-emerald-900 truncate">
                      {{ unit.member1.name }}
                    </h4>
                  </div>
                  <p class="text-[10px] text-slate-500 truncate">
                    {{ unit.member1.birthYear ? unit.member1.birthYear : '' }}
                    {{ unit.member1.deathYear ? '– ' + unit.member1.deathYear : (unit.member1.age ? `(${unit.member1.age} ans)` : '') }}
                  </p>
                  <!-- Relation Tag with Selected Person -->
                  <div v-if="getRel(unit.member1.id)" class="mt-0.5">
                    <span 
                      :class="[
                        'inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.2 rounded-full',
                        getRel(unit.member1.id).badgeClass
                      ]"
                    >
                      <span>{{ getRel(unit.member1.id).icon }}</span>
                      <span>{{ getRel(unit.member1.id).label }}</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Dotted Spouse Connection Line -->
              <div v-if="unit.member2" class="flex items-center justify-center text-rose-500 text-xs px-1 font-bold select-none" title="Marié(e)s">
                <span class="w-4 border-t-2 border-dashed border-rose-400"></span>
                <span class="px-0.5">💍</span>
                <span class="w-4 border-t-2 border-dashed border-rose-400"></span>
              </div>

              <!-- Card Member 2 (Spouse if present) -->
              <div 
                v-if="unit.member2"
                @click="handleCardClick(unit.member2.id)"
                :class="[
                  'group bg-white rounded-2xl py-2 pl-2 pr-4 border shadow-sm hover:shadow-lg transition-all cursor-pointer flex items-center gap-3 relative z-10',
                  selectedMemberId === unit.member2.id 
                    ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/50 scale-105' 
                    : getRel(unit.member2.id)
                    ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50/40 scale-102'
                    : 'border-slate-200 hover:border-emerald-300'
                ]"
                style="min-width: 200px;"
              >
                <div class="relative shrink-0">
                  <img 
                    :src="unit.member2.avatar" 
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 group-hover:ring-emerald-400 transition-all shadow-xs" 
                  />
                  <span 
                    :class="[
                      'absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white',
                      unit.member2.gender === 'M' ? 'bg-blue-500' : 'bg-rose-500'
                    ]"
                  ></span>
                </div>
                <div class="truncate text-left flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <h4 class="text-xs font-bold text-slate-900 group-hover:text-emerald-900 truncate">
                      {{ unit.member2.name }}
                    </h4>
                  </div>
                  <p class="text-[10px] text-slate-500 truncate">
                    {{ unit.member2.birthYear ? unit.member2.birthYear : '' }}
                    {{ unit.member2.deathYear ? '– ' + unit.member2.deathYear : (unit.member2.age ? `(${unit.member2.age} ans)` : '') }}
                  </p>
                  <!-- Relation Tag with Selected Person -->
                  <div v-if="getRel(unit.member2.id)" class="mt-0.5">
                    <span 
                      :class="[
                        'inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.2 rounded-full',
                        getRel(unit.member2.id).badgeClass
                      ]"
                    >
                      <span>{{ getRel(unit.member2.id).icon }}</span>
                      <span>{{ getRel(unit.member2.id).label }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Vertical Connector Line to next generation -->
          <div 
            v-if="gIdx < generationGroups.length - 1" 
            class="w-0.5 h-10 bg-emerald-600/50 mt-4 relative"
          >
            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-emerald-600"></span>
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
  selectMember,
  openAddModal
} = useFamilyStore()

const zoomLevel = ref(1.0)
const canvasContainer = ref(null)

const zoomIn = () => {
  if (zoomLevel.value < 1.6) zoomLevel.value = +(zoomLevel.value + 0.1).toFixed(1)
}

const zoomOut = () => {
  if (zoomLevel.value > 0.5) zoomLevel.value = +(zoomLevel.value - 0.1).toFixed(1)
}

const resetZoom = () => {
  zoomLevel.value = 1.0
}

const handleCardClick = (id) => {
  if (selectedMemberId.value === id) {
    selectMember(null)
  } else {
    selectMember(id)
  }
}

const getGenerationLabel = (gen) => {
  switch (Number(gen)) {
    case 1:
      return 'Génération 1 — Les Patriarches & Grands-Parents'
    case 2:
      return 'Génération 2 — Les Parents, Oncles & Tantes'
    case 3:
      return 'Génération 3 — Les Enfants, Petits-Enfants & Cousins'
    case 4:
      return 'Génération 4 — Arrière-Petits-Enfants'
    default:
      return `Génération ${gen}`
  }
}

// Calcul dynamique du lien exact entre un membre et la personne sélectionnée
const getRel = (personId) => {
  if (!selectedMemberId.value || selectedMemberId.value === personId) return null
  const rels = selectedMemberRelations.value
  if (!rels) return null

  // 1. Conjoint
  if (rels.spouse?.id === personId || rels.spouses?.some(s => s.id === personId)) {
    return { label: 'Conjoint(e)', icon: '💍', badgeClass: 'bg-rose-100 text-rose-800' }
  }

  // 2. Parents
  if (rels.father?.id === personId) {
    return { label: 'Père', icon: '👨', badgeClass: 'bg-blue-100 text-blue-800' }
  }
  if (rels.mother?.id === personId) {
    return { label: 'Mère', icon: '👩', badgeClass: 'bg-rose-100 text-rose-800' }
  }
  if (rels.parents?.some(p => p.id === personId)) {
    const p = rels.parents.find(x => x.id === personId)
    return { label: p?.gender === 'M' ? 'Père' : 'Mère', icon: p?.gender === 'M' ? '👨' : '👩', badgeClass: 'bg-blue-100 text-blue-800' }
  }

  // 3. Enfants
  if (rels.children?.some(c => c.id === personId)) {
    const c = rels.children.find(x => x.id === personId)
    return { label: c?.gender === 'M' ? 'Fils' : 'Fille', icon: c?.gender === 'M' ? '👦' : '👧', badgeClass: 'bg-emerald-100 text-emerald-800' }
  }

  // 4. Petits-Enfants (Raisonnement SPARQL)
  if (rels.grandchildren?.some(gc => gc.id === personId)) {
    const gc = rels.grandchildren.find(x => x.id === personId)
    return { label: gc?.gender === 'M' ? 'Petit-fils' : 'Petite-fille', icon: gc?.gender === 'M' ? '👦' : '👧', badgeClass: 'bg-teal-100 text-teal-800' }
  }

  // 5. Grands-Parents
  if (rels.grandparents?.some(gp => gp.id === personId)) {
    const gp = rels.grandparents.find(x => x.id === personId)
    return { label: gp?.gender === 'M' ? 'Grand-père' : 'Grand-mère', icon: '👴', badgeClass: 'bg-amber-100 text-amber-800' }
  }

  // 6. Frères & Sœurs
  if (rels.siblings?.some(s => s.id === personId)) {
    const s = rels.siblings.find(x => x.id === personId)
    return { label: s?.gender === 'M' ? 'Frère' : 'Sœur', icon: s?.gender === 'M' ? '👨' : '👩', badgeClass: 'bg-indigo-100 text-indigo-800' }
  }

  // 7. Oncles & Tantes
  if (rels.unclesAndAunts?.some(u => u.id === personId)) {
    const u = rels.unclesAndAunts.find(x => x.id === personId)
    return { label: u?.gender === 'M' ? 'Oncle' : 'Tante', icon: '⭐', badgeClass: 'bg-purple-100 text-purple-800' }
  }

  // 8. Neveux & Nièces
  if (rels.nephewsAndNieces?.some(n => n.id === personId)) {
    const n = rels.nephewsAndNieces.find(x => x.id === personId)
    return { label: n?.gender === 'M' ? 'Neveu' : 'Nièce', icon: '🌱', badgeClass: 'bg-emerald-100 text-emerald-800' }
  }

  // 9. Cousins Germains
  if (rels.cousins?.some(co => co.id === personId)) {
    return { label: 'Cousin(e)', icon: '🤝', badgeClass: 'bg-amber-100 text-amber-800' }
  }

  return null
}

// Organisation dynamique des membres par génération et par couple
const generationGroups = computed(() => {
  const list = members.value || []
  if (list.length === 0) return []

  // Trouver toutes les générations distinctes
  const gens = [...new Set(list.map(m => Number(m.generation) || 1))].sort((a, b) => a - b)

  return gens.map(gen => {
    const genMembers = list.filter(m => (Number(m.generation) || 1) === gen)
    const visited = new Set()
    const units = []

    for (const m of genMembers) {
      if (visited.has(m.id)) continue

      // Chercher le conjoint dans la même génération
      let spouse = null
      if (m.spouseId) {
        spouse = genMembers.find(other => other.id === m.spouseId)
      } else {
        spouse = genMembers.find(other => other.spouseId === m.id)
      }

      if (spouse && spouse.id !== m.id && !visited.has(spouse.id)) {
        visited.add(m.id)
        visited.add(spouse.id)
        // Mettre l'homme à gauche s'il y en a un
        const [m1, m2] = m.gender === 'M' ? [m, spouse] : [spouse, m]
        units.push({
          id: `${m1.id}_${m2.id}`,
          member1: m1,
          member2: m2
        })
      } else {
        visited.add(m.id)
        units.push({
          id: m.id,
          member1: m,
          member2: null
        })
      }
    }

    return {
      generation: gen,
      membersCount: genMembers.length,
      units
    }
  })
})
</script>

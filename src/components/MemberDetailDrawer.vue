<template>
  <div 
    v-if="selectedMember" 
    class="fixed inset-0 z-40 overflow-hidden pointer-events-none"
  >
    <!-- Backdrop overlay -->
    <div 
      @click="selectMember(null)" 
      class="absolute inset-0 bg-slate-900/30 backdrop-blur-xs pointer-events-auto transition-opacity"
    ></div>

    <!-- Drawer Panel sliding from right -->
    <div class="absolute inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl pointer-events-auto flex flex-col border-l border-slate-200 animate-in slide-in-from-right duration-200">
      <!-- Top banner with photo -->
      <div class="relative bg-[#1e563d] p-6 text-white pb-8">
        <button
          @click="selectMember(null)"
          class="absolute top-4 right-4 p-2 text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition-colors"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>

        <div class="flex items-center gap-4">
          <div class="relative">
            <img 
              :src="selectedMember.avatar" 
              class="w-16 h-16 rounded-full object-cover ring-4 ring-white/20 shadow-md" 
            />
            <span 
              :class="[
                'absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white',
                selectedMember.gender === 'M' ? 'bg-blue-500' : 'bg-rose-500'
              ]"
            ></span>
          </div>
          <div>
            <span class="text-[10px] font-bold bg-amber-400 text-slate-900 px-2 py-0.5 rounded-full uppercase">
              Génération {{ selectedMember.generation || 1 }}
            </span>
            <h3 class="text-xl font-bold mt-1 text-white leading-tight">
              {{ selectedMember.name }}
            </h3>
            <p class="text-xs text-emerald-200/90 mt-0.5">
              {{ selectedMember.profession || 'Membre de la famille' }}
            </p>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-emerald-700/60 flex items-center justify-between text-xs text-emerald-100">
          <span>Âge : <strong>{{ selectedMember.age }} ans</strong></span>
          <span>Naissance : <strong>{{ selectedMember.birthYear }}</strong></span>
          <span v-if="selectedMember.deathYear">Décès : <strong>{{ selectedMember.deathYear }}</strong></span>
        </div>
      </div>

      <!-- Drawer Relations Content -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
        <!-- Asserted Direct Links -->
        <div class="space-y-3">
          <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center justify-between">
            <span>📥 Faits Assertés Directs</span>
            <span class="text-slate-400 font-normal">Saisis manuellement</span>
          </h4>

          <div class="grid grid-cols-2 gap-2.5">
            <!-- Père -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
              <span class="text-[10px] text-slate-400 font-bold block uppercase">Père</span>
              <span v-if="selectedMemberRelations.father" @click="selectMember(selectedMemberRelations.father.id)" class="font-bold text-slate-800 hover:text-emerald-700 cursor-pointer">
                {{ selectedMemberRelations.father.name }}
              </span>
              <span v-else class="text-slate-400 italic">Non renseigné</span>
            </div>

            <!-- Mère -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
              <span class="text-[10px] text-slate-400 font-bold block uppercase">Mère</span>
              <span v-if="selectedMemberRelations.mother" @click="selectMember(selectedMemberRelations.mother.id)" class="font-bold text-slate-800 hover:text-emerald-700 cursor-pointer">
                {{ selectedMemberRelations.mother.name }}
              </span>
              <span v-else class="text-slate-400 italic">Non renseignée</span>
            </div>

            <!-- Conjoint -->
            <div class="p-3 rounded-xl bg-rose-50/50 border border-rose-200/60 col-span-2">
              <span class="text-[10px] text-rose-500 font-bold block uppercase">Conjoint(e)</span>
              <span v-if="selectedMemberRelations.spouse" @click="selectMember(selectedMemberRelations.spouse.id)" class="font-bold text-rose-800 hover:underline cursor-pointer">
                💍 {{ selectedMemberRelations.spouse.name }} ({{ selectedMemberRelations.spouse.age }} ans)
              </span>
              <span v-else class="text-slate-400 italic">Célibataire / Non marié(e)</span>
            </div>
          </div>
        </div>

        <!-- Inferred Ontological Links -->
        <div class="space-y-3 pt-2 border-t border-slate-100">
          <h4 class="font-bold text-emerald-800 uppercase tracking-wider text-[11px] flex items-center justify-between">
            <span>⚡ Déductions Ontologiques (OWL)</span>
            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">Inférence</span>
          </h4>

          <!-- Fratrie -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <span class="text-[10px] text-slate-500 font-bold uppercase block">
              Frères & Sœurs ({{ selectedMemberRelations.siblings.length }})
            </span>
            <div v-if="selectedMemberRelations.siblings.length === 0" class="text-slate-400 italic text-[11px]">
              Aucun frère ou sœur
            </div>
            <div v-else class="flex flex-wrap gap-1.5">
              <span 
                v-for="s in selectedMemberRelations.siblings" 
                :key="s.id"
                @click="selectMember(s.id)"
                class="px-2 py-1 bg-white border border-slate-200 hover:border-emerald-400 rounded-lg text-slate-800 cursor-pointer font-medium hover:text-emerald-700"
              >
                {{ s.gender === 'M' ? '👨' : '👩' }} {{ s.name }}
              </span>
            </div>
          </div>

          <!-- Enfants -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <span class="text-[10px] text-slate-500 font-bold uppercase block">
              Enfants ({{ selectedMemberRelations.children.length }})
            </span>
            <div v-if="selectedMemberRelations.children.length === 0" class="text-slate-400 italic text-[11px]">
              Aucun enfant répertorié
            </div>
            <div v-else class="flex flex-wrap gap-1.5">
              <span 
                v-for="c in selectedMemberRelations.children" 
                :key="c.id"
                @click="selectMember(c.id)"
                class="px-2 py-1 bg-white border border-slate-200 hover:border-emerald-400 rounded-lg text-slate-800 cursor-pointer font-medium hover:text-emerald-700"
              >
                {{ c.gender === 'M' ? '👦' : '👧' }} {{ c.name }}
              </span>
            </div>
          </div>

          <!-- Grands-Parents -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <span class="text-[10px] text-slate-500 font-bold uppercase block">
              Grands-Parents ({{ selectedMemberRelations.grandparents.length }})
            </span>
            <div v-if="selectedMemberRelations.grandparents.length === 0" class="text-slate-400 italic text-[11px]">
              Non identifiés dans l'arbre
            </div>
            <div v-else class="flex flex-wrap gap-1.5">
              <span 
                v-for="gp in selectedMemberRelations.grandparents" 
                :key="gp.id"
                @click="selectMember(gp.id)"
                class="px-2 py-1 bg-white border border-slate-200 hover:border-emerald-400 rounded-lg text-slate-800 cursor-pointer font-medium hover:text-emerald-700"
              >
                👴 {{ gp.name }}
              </span>
            </div>
          </div>

          <!-- Oncles & Tantes -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <span class="text-[10px] text-slate-500 font-bold uppercase block">
              Oncles & Tantes ({{ selectedMemberRelations.unclesAndAunts.length }})
            </span>
            <div v-if="selectedMemberRelations.unclesAndAunts.length === 0" class="text-slate-400 italic text-[11px]">
              Aucun oncle ou tante
            </div>
            <div v-else class="flex flex-wrap gap-1.5">
              <span 
                v-for="u in selectedMemberRelations.unclesAndAunts" 
                :key="u.id"
                @click="selectMember(u.id)"
                class="px-2 py-1 bg-white border border-slate-200 hover:border-emerald-400 rounded-lg text-slate-800 cursor-pointer font-medium hover:text-emerald-700"
              >
                {{ u.gender === 'M' ? 'Oncle' : 'Tante' }} {{ u.name }}
              </span>
            </div>
          </div>

          <!-- Cousins -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <span class="text-[10px] text-slate-500 font-bold uppercase block">
              Cousins Germains ({{ selectedMemberRelations.cousins.length }})
            </span>
            <div v-if="selectedMemberRelations.cousins.length === 0" class="text-slate-400 italic text-[11px]">
              Aucun cousin germain
            </div>
            <div v-else class="flex flex-wrap gap-1.5">
              <span 
                v-for="co in selectedMemberRelations.cousins" 
                :key="co.id"
                @click="selectMember(co.id)"
                class="px-2 py-1 bg-white border border-slate-200 hover:border-emerald-400 rounded-lg text-slate-800 cursor-pointer font-medium hover:text-emerald-700"
              >
                {{ co.name }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
        <button
          @click="selectMember(null)"
          class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-xl text-xs transition-colors"
        >
          Fermer
        </button>
        <button
          @click="showInTree"
          class="flex-1 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs transition-all shadow-sm text-center"
        >
          Voir dans l'Arbre Familial 🔗
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  activeMenu,
  selectedMember,
  selectedMemberRelations,
  selectMember
} = useFamilyStore()

const showInTree = () => {
  activeMenu.value = 'tree'
}
</script>


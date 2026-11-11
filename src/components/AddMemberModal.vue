<template>
  <div 
    v-if="isAddModalOpen" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-200"
  >
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Modal Header -->
      <div class="px-6 py-5 bg-[#1e563d] text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-xl">
            ➕
          </div>
          <div>
            <h3 class="text-lg font-bold">Ajouter un membre à la famille</h3>
            <p class="text-xs text-emerald-200/80">
              Définissez les faits d'assertion pour le raisonneur ontologique
            </p>
          </div>
        </div>
        <button
          @click="closeAddModal"
          class="text-white/70 hover:text-white p-2 rounded-lg hover:bg-white/10 transition-colors"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="handleSubmit" class="p-6 overflow-y-auto space-y-5">
        <!-- Nom & Sexe -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Nom complet <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Ex: William Carter"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Sexe <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label 
                :class="[
                  'flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border text-xs font-bold cursor-pointer transition-all',
                  form.gender === 'M' 
                    ? 'bg-blue-50 border-blue-500 text-blue-700 shadow-sm' 
                    : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                ]"
              >
                <input type="radio" v-model="form.gender" value="M" class="sr-only" />
                <span>👨 Homme</span>
              </label>

              <label 
                :class="[
                  'flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border text-xs font-bold cursor-pointer transition-all',
                  form.gender === 'F' 
                    ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-sm' 
                    : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                ]"
              >
                <input type="radio" v-model="form.gender" value="F" class="sr-only" />
                <span>👩 Femme</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Âge & Année de Naissance -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Âge (ans) <span class="text-rose-500">*</span>
            </label>
            <input
              v-model.number="form.age"
              type="number"
              min="0"
              max="130"
              required
              @input="updateBirthYear"
              placeholder="Ex: 45"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Année de naissance
            </label>
            <input
              v-model.number="form.birthYear"
              type="number"
              min="1800"
              max="2030"
              @input="updateAge"
              placeholder="Ex: 1980"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
            />
          </div>
        </div>

        <!-- Parents: Père & Mère -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Père (Classe :Homme)
            </label>
            <select
              v-model="form.fatherId"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
            >
              <option :value="null">— Aucun / Non défini —</option>
              <option 
                v-for="p in maleMembers" 
                :key="p.id" 
                :value="p.id"
              >
                {{ p.name }} ({{ p.birthYear }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Mère (Classe :Femme)
            </label>
            <select
              v-model="form.motherId"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
            >
              <option :value="null">— Aucune / Non définie —</option>
              <option 
                v-for="p in femaleMembers" 
                :key="p.id" 
                :value="p.id"
              >
                {{ p.name }} ({{ p.birthYear }})
              </option>
            </select>
          </div>
        </div>

        <!-- Conjoint ou Conjointe -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Conjoint ou Conjointe (Relation symétrique)
          </label>
          <select
            v-model="form.spouseId"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
          >
            <option :value="null">— Célibataire / Aucun(e) —</option>
            <option 
              v-for="p in potentialSpouses" 
              :key="p.id" 
              :value="p.id"
            >
              {{ p.name }} ({{ p.gender === 'M' ? 'Homme' : 'Femme' }}, {{ p.age }} ans)
            </option>
          </select>
        </div>

        <!-- Profession / Rôle optionnel -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Profession / Activité (optionnel)
          </label>
          <input
            v-model="form.profession"
            type="text"
            placeholder="Ex: Architecte, Étudiant(e)..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all"
          />
        </div>

        <!-- Avatar selector (quick presets) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
            Photo de profil
          </label>
          <div class="flex items-center gap-3 overflow-x-auto pb-2">
            <div
              v-for="(img, idx) in avatarPresets"
              :key="idx"
              @click="form.avatar = img"
              :class="[
                'w-12 h-12 rounded-full cursor-pointer overflow-hidden border-2 transition-all shrink-0',
                form.avatar === img ? 'border-emerald-600 ring-2 ring-emerald-500/30 scale-105' : 'border-slate-200 opacity-70 hover:opacity-100'
              ]"
            >
              <img :src="img" class="w-full h-full object-cover" />
            </div>
          </div>
        </div>

        <!-- Live Preview Box -->
        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex items-center gap-3">
          <img
            :src="form.avatar || defaultAvatar"
            class="w-12 h-12 rounded-full object-cover ring-2 ring-emerald-600/30"
          />
          <div class="text-xs">
            <p class="font-bold text-slate-800">{{ form.name || 'Nom du membre' }}</p>
            <p class="text-slate-500">{{ form.gender === 'M' ? 'Homme' : 'Femme' }} • {{ form.age || 0 }} ans</p>
            <p class="text-emerald-700 font-medium text-[11px]">{{ form.profession || 'Membre de la famille' }}</p>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="pt-2 flex items-center justify-end gap-3 border-t border-slate-100">
          <button
            type="button"
            @click="closeAddModal"
            class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl transition-all shadow-md active:scale-95 flex items-center gap-2"
          >
            <span>Enregistrer dans l'ontologie</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { useFamilyStore } from '../composables/useFamilyStore.js'

const {
  members,
  isAddModalOpen,
  closeAddModal,
  addMember
} = useFamilyStore()

const currentYear = new Date().getFullYear()

const avatarPresets = [
  'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=160&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=160&auto=format&fit=crop&q=80'
]

const form = reactive({
  name: '',
  gender: 'M',
  age: 30,
  birthYear: currentYear - 30,
  fatherId: null,
  motherId: null,
  spouseId: null,
  profession: '',
  avatar: avatarPresets[0]
})

const defaultAvatar = computed(() => {
  return form.gender === 'M' ? avatarPresets[0] : avatarPresets[1]
})

// Update avatar preset automatically when gender changes if not manually set
watch(() => form.gender, (newGender) => {
  if (newGender === 'M' && form.avatar === avatarPresets[1]) {
    form.avatar = avatarPresets[0]
  } else if (newGender === 'F' && form.avatar === avatarPresets[0]) {
    form.avatar = avatarPresets[1]
  }
})

const updateBirthYear = () => {
  if (form.age) {
    form.birthYear = currentYear - form.age
  }
}

const updateAge = () => {
  if (form.birthYear) {
    form.age = currentYear - form.birthYear
  }
}

const maleMembers = computed(() => {
  return members.value.filter(m => m.gender === 'M')
})

const femaleMembers = computed(() => {
  return members.value.filter(m => m.gender === 'F')
})

const potentialSpouses = computed(() => {
  // Members can be matched with opposite gender or any member without current spouse
  return members.value
})

const handleSubmit = () => {
  if (!form.name.trim()) return

  addMember({
    name: form.name,
    gender: form.gender,
    age: form.age,
    birthYear: form.birthYear,
    fatherId: form.fatherId,
    motherId: form.motherId,
    spouseId: form.spouseId,
    profession: form.profession,
    avatar: form.avatar
  })

  // Reset form
  form.name = ''
  form.gender = 'M'
  form.age = 30
  form.birthYear = currentYear - 30
  form.fatherId = null
  form.motherId = null
  form.spouseId = null
  form.profession = ''

  closeAddModal()
}
</script>


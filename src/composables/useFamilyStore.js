import { ref, computed, reactive } from 'vue'
import { initialFamilyMembers } from '../data/familyData.js'
import { OntologyReasoner } from '../services/ontologyReasoner.js'

// Global state persisted in memory or localStorage
const LOCAL_STORAGE_KEY = 'ancetree_family_members_v1'

const loadInitialMembers = () => {
  try {
    const saved = localStorage.getItem(LOCAL_STORAGE_KEY)
    if (saved) {
      return JSON.parse(saved)
    }
  } catch (e) {
    console.error('Erreur chargement localStorage:', e)
  }
  return [...initialFamilyMembers]
}

const members = ref(loadInitialMembers())
const activeMenu = ref('dashboard') // dashboard | members | add | tree | search | reasoning
const isAddModalOpen = ref(false)
const selectedMemberId = ref(null)
const globalSearch = ref('')

export function useFamilyStore() {
  const saveToStorage = () => {
    try {
      localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(members.value))
    } catch (e) {
      console.error('Erreur sauvegarde localStorage:', e)
    }
  }

  // Reactive reasoner
  const reasoner = computed(() => new OntologyReasoner(members.value))

  // Dashboard Stats
  const stats = computed(() => {
    return reasoner.value.getDashboardStats()
  })

  // Selected Member Object
  const selectedMember = computed(() => {
    if (!selectedMemberId.value) return null
    return reasoner.value.getMember(selectedMemberId.value)
  })

  // Full semantic relations for the selected member
  const selectedMemberRelations = computed(() => {
    if (!selectedMemberId.value) return null
    const id = selectedMemberId.value
    const r = reasoner.value
    const m = r.getMember(id)
    if (!m) return null

    return {
      person: m,
      father: r.getFather(id),
      mother: r.getMother(id),
      parents: r.getParents(id),
      spouse: r.getSpouse(id),
      children: r.getChildren(id),
      sons: r.getSons(id),
      daughters: r.getDaughters(id),
      siblings: r.getSiblings(id),
      brothers: r.getBrothers(id),
      sisters: r.getSisters(id),
      grandparents: r.getGrandParents(id),
      grandchildren: r.getGrandChildren(id),
      unclesAndAunts: r.getUnclesAndAunts(id),
      nephewsAndNieces: r.getNephewsAndNieces(id),
      cousins: r.getCousins(id),
      ancestors: r.getAncestors(id),
      descendants: r.getDescendants(id)
    }
  })

  // Actions
  const addMember = (data) => {
    const newId = 'm_' + Date.now().toString(36) + Math.random().toString(36).substring(2, 5)
    
    // Auto calculate generation based on parents
    let gen = 1
    if (data.fatherId || data.motherId) {
      const f = members.value.find(m => m.id === data.fatherId)
      const m = members.value.find(m => m.id === data.motherId)
      const maxParentGen = Math.max(f?.generation || 0, m?.generation || 0)
      gen = maxParentGen + 1
    }

    const newMember = {
      id: newId,
      name: data.name.trim(),
      gender: data.gender,
      birthYear: Number(data.birthYear) || (data.age ? new Date().getFullYear() - Number(data.age) : 2000),
      deathYear: data.deathYear ? Number(data.deathYear) : null,
      age: Number(data.age) || (data.birthYear ? new Date().getFullYear() - Number(data.birthYear) : 25),
      avatar: data.avatar || (data.gender === 'M' 
        ? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160&auto=format&fit=crop&q=80'
        : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=160&auto=format&fit=crop&q=80'),
      fatherId: data.fatherId || null,
      motherId: data.motherId || null,
      spouseId: data.spouseId || null,
      generation: gen,
      profession: data.profession || 'Membre de la famille'
    }

    // Si un conjoint est désigné, mettre à jour la réciprocité symétrique
    if (newMember.spouseId) {
      const spouse = members.value.find(m => m.id === newMember.spouseId)
      if (spouse) {
        spouse.spouseId = newId
      }
    }

    members.value.push(newMember)
    saveToStorage()
    return newMember
  }

  const updateMember = (id, data) => {
    const index = members.value.findIndex(m => m.id === id)
    if (index === -1) return

    const existing = members.value[index]
    const oldSpouseId = existing.spouseId

    // Handle reciprocal spouse update
    if (oldSpouseId && oldSpouseId !== data.spouseId) {
      const prevSpouse = members.value.find(m => m.id === oldSpouseId)
      if (prevSpouse && prevSpouse.spouseId === id) {
        prevSpouse.spouseId = null
      }
    }

    if (data.spouseId && data.spouseId !== oldSpouseId) {
      const newSpouse = members.value.find(m => m.id === data.spouseId)
      if (newSpouse) {
        newSpouse.spouseId = id
      }
    }

    members.value[index] = {
      ...existing,
      ...data
    }

    saveToStorage()
  }

  const deleteMember = (id) => {
    // Nettoyer les références père/mère/conjoint
    members.value = members.value
      .filter(m => m.id !== id)
      .map(m => {
        const updated = { ...m }
        if (updated.fatherId === id) updated.fatherId = null
        if (updated.motherId === id) updated.motherId = null
        if (updated.spouseId === id) updated.spouseId = null
        return updated
      })

    if (selectedMemberId.value === id) {
      selectedMemberId.value = null
    }

    saveToStorage()
  }

  const resetToDefault = () => {
    members.value = JSON.parse(JSON.stringify(initialFamilyMembers))
    saveToStorage()
    selectedMemberId.value = null
  }

  const selectMember = (id) => {
    selectedMemberId.value = id
  }

  const openAddModal = () => {
    isAddModalOpen.value = true
  }

  const closeAddModal = () => {
    isAddModalOpen.value = false
  }

  return {
    members,
    activeMenu,
    isAddModalOpen,
    selectedMemberId,
    selectedMember,
    selectedMemberRelations,
    globalSearch,
    reasoner,
    stats,
    addMember,
    updateMember,
    deleteMember,
    resetToDefault,
    selectMember,
    openAddModal,
    closeAddModal
  }
}


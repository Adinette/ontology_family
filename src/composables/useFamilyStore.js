import { ref, computed, watch } from 'vue'
import { initialFamilyMembers } from '../data/familyData.js'
import { OntologyReasoner } from '../services/ontologyReasoner.js'
import { api } from '../services/api.js'

// Global state
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
const selectedMemberRelationsApi = ref(null)
const globalSearch = ref('')

// Connection & API state
const isConnected = ref(false)
const isCheckingConnection = ref(false)
const tripleCount = ref(0)
const apiError = ref(null)
const backendStats = ref(null)
const isLoadingMembers = ref(false)

let hasInitialized = false

export function useFamilyStore() {
  const saveToStorage = () => {
    try {
      localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(members.value))
    } catch (e) {
      console.error('Erreur sauvegarde localStorage:', e)
    }
  }

  // Reactive local reasoner (utilisé pour les calculs d'appoint et le mode hors-ligne)
  const reasoner = computed(() => new OntologyReasoner(members.value))

  // Dashboard Stats (priorité aux stats SPARQL / Fuseki du backend)
  const stats = computed(() => {
    if (backendStats.value) {
      const genCount = backendStats.value.generationCount ?? 3
      const asserted = backendStats.value.assertedRelationsCount ?? 0
      const inferred = backendStats.value.inferredRelationsCount ?? 0
      return {
        totalMembers: backendStats.value.totalMembers ?? members.value.length,
        menCount: backendStats.value.menCount ?? 0,
        womenCount: backendStats.value.womenCount ?? 0,
        generationsCount: genCount,
        generationCount: genCount,
        totalRelations: asserted,
        assertedRelationsCount: asserted,
        inferredRelations: inferred,
        inferredRelationsCount: inferred,
        averageAge: backendStats.value.averageAge ?? 0,
        deceasedCount: backendStats.value.deceasedCount ?? 0,
        parentsCount: backendStats.value.parentsCount ?? 0,
        generations: backendStats.value.generations ?? {},
        inferredBreakdown: backendStats.value.inferredBreakdown ?? {},
        genMap: new Map((members.value || []).map(m => [m.id, Number(m.generation) || 1]))
      }
    }
    const local = reasoner.value.getDashboardStats()
    return {
      ...local,
      generationCount: local.generationsCount || 3,
      assertedRelationsCount: local.totalRelations || 0,
      inferredRelationsCount: local.inferredRelations || 0,
      genMap: local.genMap || new Map((members.value || []).map(m => [m.id, Number(m.generation) || 1]))
    }
  })

  // Selected Member Object
  const selectedMember = computed(() => {
    if (!selectedMemberId.value) return null
    return members.value.find(m => m.id === selectedMemberId.value) || reasoner.value.getMember(selectedMemberId.value)
  })

  // Relations du membre sélectionné (provenant de Fuseki SPARQL ou fallback local)
  const selectedMemberRelations = computed(() => {
    if (!selectedMemberId.value) return null
    
    // Si l'API backend a renvoyé les relations déduites en SPARQL
    if (selectedMemberRelationsApi.value && selectedMemberRelationsApi.value.person?.id === selectedMemberId.value) {
      return selectedMemberRelationsApi.value
    }

    // Fallback local
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

  // Vérifier la connexion avec Laravel + Fuseki
  const checkConnection = async () => {
    isCheckingConnection.value = true
    apiError.value = null
    try {
      const health = await api.getHealth()
      if (health.status === 'ok') {
        isConnected.value = true
        tripleCount.value = health.fuseki?.triples || 780
        await loadBackendData()
      } else {
        isConnected.value = false
      }
    } catch (err) {
      isConnected.value = false
      apiError.value = err.message || 'Impossible de joindre le backend'
    } finally {
      isCheckingConnection.value = false
    }
  }

  // Charger les données réelles depuis le backend (Fuseki)
  const loadBackendData = async () => {
    isLoadingMembers.value = true
    try {
      const [membersRes, statsRes] = await Promise.all([
        api.getMembers(),
        api.getStats().catch(() => null)
      ])
      if (membersRes && Array.isArray(membersRes.data)) {
        members.value = membersRes.data
        saveToStorage()
      }
      if (statsRes && statsRes.data) {
        backendStats.value = statsRes.data
      }
    } catch (err) {
      console.warn('Chargement des données du backend échoué, conservation des données locales:', err)
    } finally {
      isLoadingMembers.value = false
    }
  }

  // Sélectionner un membre et charger ses relations sémantiques depuis Fuseki
  const selectMember = async (id) => {
    selectedMemberId.value = id
    selectedMemberRelationsApi.value = null

    if (id && isConnected.value) {
      try {
        const res = await api.getMemberRelations(id)
        if (res && res.data) {
          selectedMemberRelationsApi.value = res.data
        }
      } catch (err) {
        console.warn('Impossible de charger les relations SPARQL pour', id, err)
      }
    }
  }

  // Actions CRUD synchronisées avec le backend SPARQL
  const addMember = async (data) => {
    if (isConnected.value) {
      try {
        const payload = {
          name: data.name.trim(),
          gender: data.gender,
          birthYear: Number(data.birthYear) || undefined,
          deathYear: data.deathYear ? Number(data.deathYear) : undefined,
          age: Number(data.age) || undefined,
          avatar: data.avatar || undefined,
          profession: data.profession || undefined,
          fatherId: data.fatherId || undefined,
          motherId: data.motherId || undefined,
          spouseId: data.spouseId || undefined
        }
        await api.createMember(payload)
        await loadBackendData()
        return
      } catch (err) {
        console.error('Erreur API addMember:', err)
        throw err
      }
    }

    // Fallback mode hors-ligne
    const newId = 'm_' + Date.now().toString(36)
    let gen = 1
    if (data.fatherId || data.motherId) {
      const f = members.value.find(m => m.id === data.fatherId)
      const m = members.value.find(m => m.id === data.motherId)
      gen = Math.max(f?.generation || 0, m?.generation || 0) + 1
    }

    const newMember = {
      id: newId,
      name: data.name.trim(),
      gender: data.gender,
      birthYear: Number(data.birthYear) || 2000,
      deathYear: data.deathYear ? Number(data.deathYear) : null,
      age: Number(data.age) || 25,
      avatar: data.avatar || (data.gender === 'M' 
        ? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160&auto=format&fit=crop&q=80'
        : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=160&auto=format&fit=crop&q=80'),
      fatherId: data.fatherId || null,
      motherId: data.motherId || null,
      spouseId: data.spouseId || null,
      generation: gen,
      profession: data.profession || 'Membre de la famille'
    }

    if (newMember.spouseId) {
      const spouse = members.value.find(m => m.id === newMember.spouseId)
      if (spouse) spouse.spouseId = newId
    }

    members.value.push(newMember)
    saveToStorage()
    return newMember
  }

  const updateMember = async (id, data) => {
    if (isConnected.value) {
      try {
        await api.updateMember(id, data)
        await loadBackendData()
        return
      } catch (err) {
        console.error('Erreur API updateMember:', err)
        throw err
      }
    }

    const index = members.value.findIndex(m => m.id === id)
    if (index === -1) return
    members.value[index] = { ...members.value[index], ...data }
    saveToStorage()
  }

  const deleteMember = async (id) => {
    if (isConnected.value) {
      try {
        await api.deleteMember(id)
        if (selectedMemberId.value === id) selectedMemberId.value = null
        await loadBackendData()
        return
      } catch (err) {
        console.error('Erreur API deleteMember:', err)
        throw err
      }
    }

    members.value = members.value.filter(m => m.id !== id)
    if (selectedMemberId.value === id) selectedMemberId.value = null
    saveToStorage()
  }

  const resetToDefault = async () => {
    if (isConnected.value) {
      try {
        await api.resetOntology()
        selectedMemberId.value = null
        await loadBackendData()
        return
      } catch (err) {
        console.error('Erreur réinitialisation ontologie:', err)
      }
    }

    members.value = JSON.parse(JSON.stringify(initialFamilyMembers))
    saveToStorage()
    selectedMemberId.value = null
  }

  const openAddModal = () => { isAddModalOpen.value = true }
  const closeAddModal = () => { isAddModalOpen.value = false }

  // Initialisation automatique à l'ouverture de l'application
  if (!hasInitialized) {
    hasInitialized = true
    checkConnection()
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
    isConnected,
    isCheckingConnection,
    tripleCount,
    apiError,
    isLoadingMembers,
    checkConnection,
    loadBackendData,
    addMember,
    updateMember,
    deleteMember,
    resetToDefault,
    selectMember,
    openAddModal,
    closeAddModal
  }
}

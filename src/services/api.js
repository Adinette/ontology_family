/**
 * Client API pour communiquer avec le Backend Laravel (et Apache Jena Fuseki).
 */

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

async function request(endpoint, options = {}) {
  const url = `${API_BASE}${endpoint}`
  const config = {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers || {})
    },
    ...options
  }

  try {
    const response = await fetch(url, config)
    if (!response.ok) {
      let errorData
      try {
        errorData = await response.json()
      } catch {
        errorData = { message: `Erreur HTTP ${response.status} (${response.statusText})` }
      }
      throw new Error(errorData.message || `Erreur serveur (${response.status})`)
    }
    return await response.json()
  } catch (err) {
    console.error(`[API Error] ${options.method || 'GET'} ${endpoint}:`, err)
    throw err
  }
}

export const api = {
  // Santé et connexion
  getHealth: () => request('/health'),

  // Membres
  getMembers: (filters = {}) => {
    const params = new URLSearchParams()
    if (filters.gender) params.append('gender', filters.gender)
    if (filters.generation) params.append('generation', filters.generation)
    if (filters.q) params.append('q', filters.q)
    const qs = params.toString() ? `?${params.toString()}` : ''
    return request(`/members${qs}`)
  },

  getMember: (id) => request(`/members/${encodeURIComponent(id)}`),

  createMember: (data) => request('/members', {
    method: 'POST',
    body: JSON.stringify(data)
  }),

  updateMember: (id, data) => request(`/members/${encodeURIComponent(id)}`, {
    method: 'PUT',
    body: JSON.stringify(data)
  }),

  deleteMember: (id) => request(`/members/${encodeURIComponent(id)}`, {
    method: 'DELETE'
  }),

  // Relations sémantiques déduites pour un membre
  getMemberRelations: (id) => request(`/members/${encodeURIComponent(id)}/relations`),

  // Recherche sémantique
  search: (relation, personId) => {
    const params = new URLSearchParams({ relation, person: personId })
    return request(`/search?${params.toString()}`)
  },

  getSearchTypes: () => request('/search/types'),

  // Lien entre 2 individus (A ↔ B)
  getRelationship: (fromId, toId) => {
    const params = new URLSearchParams({ from: fromId, to: toId })
    return request(`/relationship?${params.toString()}`)
  },

  // Tableau de bord
  getStats: () => request('/stats'),

  // SPARQL
  getSparqlPresets: () => request('/sparql/presets'),
  runSparql: (query) => request('/sparql', {
    method: 'POST',
    body: JSON.stringify({ query })
  }),

  // Ontologie et exports
  getOntology: () => request('/ontology'),
  resetOntology: () => request('/reset', { method: 'POST' }),
  getExportTurtleUrl: () => `${API_BASE}/export/turtle`
}

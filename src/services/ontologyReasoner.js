/**
 * Moteur de Raisonnement Ontologique pour la Famille
 * Adapté aux concepts du Web Sémantique (OWL, RDF, RDFS, Inférence)
 */

export class OntologyReasoner {
  constructor(members = []) {
    this.members = members
    this.memberMap = new Map(members.map(m => [m.id, m]))
  }

  setMembers(members) {
    this.members = members
    this.memberMap = new Map(members.map(m => [m.id, m]))
  }

  getMember(id) {
    return this.memberMap.get(id) || null
  }

  // --- Faits Assertés de base ---
  getFather(personId) {
    const p = this.getMember(personId)
    return p && p.fatherId ? this.getMember(p.fatherId) : null
  }

  getMother(personId) {
    const p = this.getMember(personId)
    return p && p.motherId ? this.getMember(p.motherId) : null
  }

  getParents(personId) {
    const p = this.getMember(personId)
    if (!p) return []
    const parents = []
    if (p.fatherId && this.memberMap.has(p.fatherId)) parents.push(this.memberMap.get(p.fatherId))
    if (p.motherId && this.memberMap.has(p.motherId)) parents.push(this.memberMap.get(p.motherId))
    return parents
  }

  getSpouse(personId) {
    const p = this.getMember(personId)
    if (!p) return null
    if (p.spouseId && this.memberMap.has(p.spouseId)) return this.memberMap.get(p.spouseId)
    // Symétrie de la relation conjoint (OWL SymmetricProperty)
    for (const m of this.members) {
      if (m.spouseId === personId) return m
    }
    return null
  }

  // --- Règles d'inférence Déduites (Inferred Facts) ---

  // 1. Enfants : Inversion de parentDe (OWL InverseOf)
  getChildren(personId) {
    return this.members.filter(m => m.fatherId === personId || m.motherId === personId)
  }

  getSons(personId) {
    return this.getChildren(personId).filter(m => m.gender === 'M')
  }

  getDaughters(personId) {
    return this.getChildren(personId).filter(m => m.gender === 'F')
  }

  // 2. Frères et Sœurs : Partage d'au moins un parent (x != y)
  getSiblings(personId) {
    const p = this.getMember(personId)
    if (!p) return []
    if (!p.fatherId && !p.motherId) return []

    return this.members.filter(m => {
      if (m.id === personId) return false
      const shareFather = p.fatherId && m.fatherId && p.fatherId === m.fatherId
      const shareMother = p.motherId && m.motherId && p.motherId === m.motherId
      return shareFather || shareMother
    })
  }

  getBrothers(personId) {
    return this.getSiblings(personId).filter(m => m.gender === 'M')
  }

  getSisters(personId) {
    return this.getSiblings(personId).filter(m => m.gender === 'F')
  }

  // 3. Grands-Parents : Composition parent(x, y) & parent(y, z)
  getGrandParents(personId) {
    const parents = this.getParents(personId)
    const grandParents = []
    for (const parent of parents) {
      const pParents = this.getParents(parent.id)
      for (const gp of pParents) {
        if (!grandParents.some(x => x.id === gp.id)) {
          grandParents.push(gp)
        }
      }
    }
    return grandParents
  }

  getGrandFathers(personId) {
    return this.getGrandParents(personId).filter(m => m.gender === 'M')
  }

  getGrandMothers(personId) {
    return this.getGrandParents(personId).filter(m => m.gender === 'F')
  }

  // 4. Petits-Enfants : Inversion de grandParentDe
  getGrandChildren(personId) {
    const children = this.getChildren(personId)
    const grandChildren = []
    for (const child of children) {
      const cChildren = this.getChildren(child.id)
      for (const gc of cChildren) {
        if (!grandChildren.some(x => x.id === gc.id)) {
          grandChildren.push(gc)
        }
      }
    }
    return grandChildren
  }

  // 5. Oncles et Tantes : Frères et Sœurs des parents (+ conjoints des oncles/tantes)
  getUnclesAndAunts(personId) {
    const parents = this.getParents(personId)
    const results = []

    for (const parent of parents) {
      const sibs = this.getSiblings(parent.id)
      for (const sib of sibs) {
        if (!results.some(x => x.id === sib.id)) {
          results.push(sib)
        }
        // Conjoint de l'oncle/tante (par alliance)
        const sibSpouse = this.getSpouse(sib.id)
        if (sibSpouse && !results.some(x => x.id === sibSpouse.id)) {
          results.push(sibSpouse)
        }
      }
    }
    return results
  }

  getUncles(personId) {
    return this.getUnclesAndAunts(personId).filter(m => m.gender === 'M')
  }

  getAunts(personId) {
    return this.getUnclesAndAunts(personId).filter(m => m.gender === 'F')
  }

  // 6. Neveux et Nièces : Enfants des frères et sœurs
  getNephewsAndNieces(personId) {
    const siblings = this.getSiblings(personId)
    const results = []
    for (const sib of siblings) {
      const children = this.getChildren(sib.id)
      for (const c of children) {
        if (!results.some(x => x.id === c.id)) {
          results.push(c)
        }
      }
    }
    return results
  }

  // 7. Cousins et Cousines : Enfants des oncles et tantes de sang
  getCousins(personId) {
    const parents = this.getParents(personId)
    const cousins = []

    for (const parent of parents) {
      const sibs = this.getSiblings(parent.id)
      for (const sib of sibs) {
        const sibChildren = this.getChildren(sib.id)
        for (const cousin of sibChildren) {
          if (cousin.id !== personId && !cousins.some(x => x.id === cousin.id)) {
            cousins.push(cousin)
          }
        }
      }
    }
    return cousins
  }

  // 8. Ancêtres (Fermeture transitive de parentDe)
  getAncestors(personId, visited = new Set()) {
    if (visited.has(personId)) return []
    visited.add(personId)

    const parents = this.getParents(personId)
    const ancestors = [...parents]

    for (const parent of parents) {
      const grandAncestors = this.getAncestors(parent.id, visited)
      for (const a of grandAncestors) {
        if (!ancestors.some(x => x.id === a.id)) {
          ancestors.push(a)
        }
      }
    }
    return ancestors
  }

  // 9. Descendants (Fermeture transitive de enfantDe)
  getDescendants(personId, visited = new Set()) {
    if (visited.has(personId)) return []
    visited.add(personId)

    const children = this.getChildren(personId)
    const descendants = [...children]

    for (const child of children) {
      const grandDescendants = this.getDescendants(child.id, visited)
      for (const d of grandDescendants) {
        if (!descendants.some(x => x.id === d.id)) {
          descendants.push(d)
        }
      }
    }
    return descendants
  }

  // Calcul dynamique de la génération
  calculateGenerations() {
    const genMap = new Map()

    const getGen = (personId, visited = new Set()) => {
      if (genMap.has(personId)) return genMap.get(personId)
      if (visited.has(personId)) return 1 // évite les boucles cycliques

      visited.add(personId)
      const p = this.getMember(personId)
      if (!p) return 1

      const fGen = p.fatherId ? getGen(p.fatherId, new Set(visited)) : 0
      const mGen = p.motherId ? getGen(p.motherId, new Set(visited)) : 0

      const maxParentGen = Math.max(fGen, mGen)
      const myGen = maxParentGen > 0 ? maxParentGen + 1 : 1
      genMap.set(personId, myGen)
      return myGen
    }

    for (const m of this.members) {
      getGen(m.id)
    }

    return genMap
  }

  // --- Statistiques complètes pour le Dashboard ---
  getDashboardStats() {
    const total = this.members.length
    const men = this.members.filter(m => m.gender === 'M').length
    const women = this.members.filter(m => m.gender === 'F').length

    // Calcul des générations
    const genMap = this.calculateGenerations()
    const generationsSet = new Set(genMap.values())
    const generationCount = generationsSet.size || 1

    // Calcul des relations assertées
    let assertedRelationsCount = 0
    for (const m of this.members) {
      if (m.fatherId) assertedRelationsCount++
      if (m.motherId) assertedRelationsCount++
      if (m.spouseId) assertedRelationsCount++
    }

    // Calcul de l'ensemble des relations inférées
    let inferredRelationsCount = 0
    const inferredTriples = []

    for (const m of this.members) {
      // Inversé: Enfant
      const children = this.getChildren(m.id)
      inferredRelationsCount += children.length

      // Fratrie
      const siblings = this.getSiblings(m.id)
      inferredRelationsCount += siblings.length

      // Grands-parents
      const gps = this.getGrandParents(m.id)
      inferredRelationsCount += gps.length

      // Oncles & Tantes
      const unclesAunts = this.getUnclesAndAunts(m.id)
      inferredRelationsCount += unclesAunts.length

      // Cousins
      const cousins = this.getCousins(m.id)
      inferredRelationsCount += cousins.length
    }

    // Nombre moyen d'enfants par famille
    const parentsCount = new Set(
      this.members.flatMap(m => [m.fatherId, m.motherId].filter(Boolean))
    ).size

    const avgChildren = parentsCount > 0 ? (total / parentsCount).toFixed(1) : 0

    return {
      totalMembers: total,
      menCount: men,
      womenCount: women,
      generationCount: Math.max(generationCount, 1),
      assertedRelationsCount,
      inferredRelationsCount,
      parentsCount,
      avgChildren,
      genMap
    }
  }

  // --- Moteur de Justification Ontologique ---
  // Explique pourquoi une relation existe selon la logique formelle OWL
  explainRelation(fromId, toId, relationType) {
    const p1 = this.getMember(fromId)
    const p2 = this.getMember(toId)
    if (!p1 || !p2) return 'Personnes inconnues'

    switch (relationType) {
      case 'father':
        return `${p1.name} est le père asserté de ${p2.name} (Triplets : <${p2.name}> aPourPere <${p1.name}>).`
      case 'mother':
        return `${p1.name} est la mère assertée de ${p2.name} (Triplets : <${p2.name}> aPourMere <${p1.name}>).`
      case 'spouse':
        return `${p1.name} et ${p2.name} sont mariés (Propriété symétrique owl:SymmetricProperty).`
      case 'child':
        return `${p2.name} est le parent de ${p1.name}. Par la règle d'inversion owl:inverseOf(aPourParent), ${p1.name} est l'enfant de ${p2.name}.`
      case 'sibling':
        return `${p1.name} et ${p2.name} partagent les mêmes parents. Règle : aPourParent(?x, ?p) ∧ aPourParent(?y, ?p) ∧ (?x ≠ ?y) ⇒ aPourFratrie(?x, ?y).`
      case 'grandparent':
        return `Règle de composition transitrice : ${p1.name} est le parent de l'un des parents de ${p2.name}.`
      case 'uncle_aunt':
        return `Règle d'inférence : ${p1.name} est frère/sœur du parent de ${p2.name}.`
      case 'cousin':
        return `Règle d'inférence : Les parents respectifs de ${p1.name} et ${p2.name} sont frères et sœurs.`
      default:
        return `Relation déduite par le raisonneur ontologique.`
    }
  }

  // --- Exportation Sémantique RDF / Turtle (Format Protégé) ---
  exportTurtle() {
    const lines = [
      `@prefix : <http://www.semanticweb.org/ontologies/family#> .`,
      `@prefix owl: <http://www.w3.org/2002/07/owl#> .`,
      `@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .`,
      `@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .`,
      `@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .`,
      ``,
      `<http://www.semanticweb.org/ontologies/family> rdf:type owl:Ontology ;`,
      `    rdfs:label "Ontologie de la Famille — IFRI TP" ;`,
      `    rdfs:comment "Ontologie généalogique avec règles d'inférence OWL-DL" .`,
      ``,
      `### Classes`,
      `:Personne rdf:type owl:Class .`,
      `:Homme rdf:type owl:Class ; rdfs:subClassOf :Personne .`,
      `:Femme rdf:type owl:Class ; rdfs:subClassOf :Personne ; owl:disjointWith :Homme .`,
      ``,
      `### Propriétés d'Objet`,
      `:aPourParent rdf:type owl:ObjectProperty .`,
      `:aPourPere rdf:type owl:ObjectProperty ; rdfs:subPropertyOf :aPourParent ; rdfs:range :Homme .`,
      `:aPourMere rdf:type owl:ObjectProperty ; rdfs:subPropertyOf :aPourParent ; rdfs:range :Femme .`,
      `:aPourEnfant rdf:type owl:ObjectProperty ; owl:inverseOf :aPourParent .`,
      `:aPourConjoint rdf:type owl:ObjectProperty, owl:SymmetricProperty .`,
      `:aPourGrandParent rdf:type owl:ObjectProperty ; owl:propertyChainAxiom ( :aPourParent :aPourParent ) .`,
      `:aPourOncleOuTante rdf:type owl:ObjectProperty .`,
      `:aPourCousin rdf:type owl:ObjectProperty, owl:SymmetricProperty .`,
      ``,
      `### Individus (A-Box)`,
    ]

    for (const m of this.members) {
      const safeId = m.name.replace(/[^a-zA-Z0-9]/g, '_')
      const type = m.gender === 'M' ? ':Homme' : ':Femme'
      lines.push(`:${safeId} rdf:type owl:NamedIndividual, ${type} ;`)
      lines.push(`    rdfs:label "${m.name}" ;`)
      lines.push(`    :age "${m.age}"^^xsd:integer ;`)
      lines.push(`    :anneeNaissance "${m.birthYear}"^^xsd:gYear ;`)

      if (m.fatherId) {
        const father = this.getMember(m.fatherId)
        if (father) lines.push(`    :aPourPere :${father.name.replace(/[^a-zA-Z0-9]/g, '_')} ;`)
      }
      if (m.motherId) {
        const mother = this.getMember(m.motherId)
        if (mother) lines.push(`    :aPourMere :${mother.name.replace(/[^a-zA-Z0-9]/g, '_')} ;`)
      }
      if (m.spouseId) {
        const spouse = this.getMember(m.spouseId)
        if (spouse) lines.push(`    :aPourConjoint :${spouse.name.replace(/[^a-zA-Z0-9]/g, '_')} ;`)
      }
      lines.push(`    rdf:type :Personne .`)
      lines.push(``)
    }

    return lines.join('\n')
  }
}


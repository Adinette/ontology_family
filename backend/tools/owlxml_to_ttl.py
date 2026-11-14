#!/usr/bin/env python3
"""Convertit un fichier OWL/XML (format "OWL/XML" de Protégé) en Turtle,
chargeable dans Apache Jena Fuseki.

Usage :  python3 owlxml_to_ttl.py famille.owl famille.ttl
Dépendance : pip install rdflib

Alternative sans script : dans Protégé, File > Save as... > "RDF/XML" ou "Turtle".
"""
import sys
import xml.etree.ElementTree as ET
from rdflib import BNode, Graph, Literal, Namespace, URIRef
from rdflib.collection import Collection
from rdflib.namespace import OWL, RDF, RDFS, XSD

NS = "{http://www.w3.org/2002/07/owl#}"


class Converter:
    def __init__(self, root):
        self.root = root
        self.base = root.get("ontologyIRI") or root.get("{http://www.w3.org/XML/1998/namespace}base")
        self.g = Graph()
        self.prefixes = {"owl": str(OWL), "rdf": str(RDF), "rdfs": str(RDFS), "xsd": str(XSD),
                         "xml": "http://www.w3.org/XML/1998/namespace"}

    # ---- IRIs -----------------------------------------------------------
    def iri(self, e):
        v = e.get("IRI")
        if v is not None:
            return URIRef(self.base + v) if v.startswith("#") else URIRef(v)
        ab = e.get("abbreviatedIRI")
        pre, local = ab.split(":", 1)
        return URIRef(self.prefixes[pre] + local)

    def literal(self, e):
        dt = e.get("datatypeIRI")
        return Literal(e.text or "", datatype=URIRef(dt)) if dt else Literal(e.text or "")

    # ---- Expressions de classe / de données ------------------------------
    def expr(self, e):
        t = e.tag.replace(NS, "")
        if t in ("Class", "ObjectProperty", "DataProperty", "NamedIndividual", "Datatype"):
            return self.iri(e)
        if t == "Literal":
            return self.literal(e)
        if t in ("ObjectIntersectionOf", "ObjectUnionOf", "DataIntersectionOf", "DataUnionOf"):
            n = BNode()
            self.g.add((n, RDF.type, RDFS.Datatype if t.startswith("Data") else OWL.Class))
            pred = OWL.intersectionOf if "Intersection" in t else OWL.unionOf
            lst = BNode()
            Collection(self.g, lst, [self.expr(c) for c in e])
            self.g.add((n, pred, lst))
            return n
        if t in ("ObjectSomeValuesFrom", "DataSomeValuesFrom"):
            n = BNode()
            self.g.add((n, RDF.type, OWL.Restriction))
            self.g.add((n, OWL.onProperty, self.expr(e[0])))
            self.g.add((n, OWL.someValuesFrom, self.expr(e[1])))
            return n
        if t == "DatatypeRestriction":
            n = BNode()
            self.g.add((n, RDF.type, RDFS.Datatype))
            self.g.add((n, OWL.onDatatype, self.expr(e[0])))
            facets = []
            for f in e[1:]:
                fb = BNode()
                self.g.add((fb, URIRef(f.get("facet")), self.expr(f[0])))
                facets.append(fb)
            lst = BNode()
            Collection(self.g, lst, facets)
            self.g.add((n, OWL.withRestrictions, lst))
            return n
        raise ValueError(f"Construction OWL non gérée : {t}")

    # ---- Conversion --------------------------------------------------------
    def run(self):
        g = self.g
        for p in self.root.findall(NS + "Prefix"):
            name, iri = p.get("name"), p.get("IRI")
            g.bind(name if name else "", iri + "#" if name == "" else iri)
        ont = URIRef(self.base)
        g.add((ont, RDF.type, OWL.Ontology))
        kinds = {"Class": OWL.Class, "ObjectProperty": OWL.ObjectProperty,
                 "DataProperty": OWL.DatatypeProperty, "NamedIndividual": OWL.NamedIndividual}
        for el in self.root:
            t = el.tag.replace(NS, "")
            if t == "Declaration":
                g.add((self.iri(el[0]), RDF.type, kinds[el[0].tag.replace(NS, "")]))
            elif t == "SubClassOf":
                g.add((self.expr(el[0]), RDFS.subClassOf, self.expr(el[1])))
            elif t == "EquivalentClasses":
                first = self.expr(el[0])
                for other in el[1:]:
                    g.add((first, OWL.equivalentClass, self.expr(other)))
            elif t == "DisjointClasses":
                cs = [self.expr(c) for c in el]
                for i in range(len(cs)):
                    for j in range(i + 1, len(cs)):
                        g.add((cs[i], OWL.disjointWith, cs[j]))
            elif t == "SubObjectPropertyOf":
                sub, sup = el[0], el[1]
                if sub.tag.replace(NS, "") == "ObjectPropertyChain":
                    lst = BNode()
                    Collection(g, lst, [self.iri(c) for c in sub])
                    g.add((self.iri(sup), OWL.propertyChainAxiom, lst))
                else:
                    g.add((self.iri(sub), RDFS.subPropertyOf, self.iri(sup)))
            elif t == "InverseObjectProperties":
                g.add((self.iri(el[0]), OWL.inverseOf, self.iri(el[1])))
            elif t == "TransitiveObjectProperty":
                g.add((self.iri(el[0]), RDF.type, OWL.TransitiveProperty))
            elif t == "SymmetricObjectProperty":
                g.add((self.iri(el[0]), RDF.type, OWL.SymmetricProperty))
            elif t == "FunctionalDataProperty":
                g.add((self.iri(el[0]), RDF.type, OWL.FunctionalProperty))
            elif t in ("ObjectPropertyDomain", "DataPropertyDomain"):
                g.add((self.iri(el[0]), RDFS.domain, self.expr(el[1])))
            elif t in ("ObjectPropertyRange", "DataPropertyRange"):
                g.add((self.iri(el[0]), RDFS.range, self.expr(el[1])))
            elif t == "ClassAssertion":
                g.add((self.iri(el[1]), RDF.type, self.expr(el[0])))
            elif t == "ObjectPropertyAssertion":
                g.add((self.iri(el[1]), self.iri(el[0]), self.iri(el[2])))
            elif t == "DataPropertyAssertion":
                g.add((self.iri(el[1]), self.iri(el[0]), self.literal(el[2])))
            elif t == "Prefix":
                pass
            else:
                print(f"Axiome ignoré : {t}", file=sys.stderr)
        return g


if __name__ == "__main__":
    src, dst = sys.argv[1], sys.argv[2]
    graph = Converter(ET.parse(src).getroot()).run()
    graph.serialize(destination=dst, format="turtle")
    print(f"{len(graph)} triplets écrits dans {dst}")

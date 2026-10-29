# Report — Reprendre un lien de l'historique Chat pour amorcer Radar

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/005-f-amorcage-historique/`
> **Amont** : `pitch.md` · `plan.md`

## Synthèse

- **Écarts** : 2 — 2 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 6 / 6 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 0 item.

Livraison conforme. Les écarts sont des partages de templates et d'aides de test pour éviter la duplication entre l'ajout et la reprise.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Templates `new` et `seed` non mutualisés | Champs et avertissement de doublon extraits en `_fields.html.twig` et `_duplicate.html.twig` | Éviter de dupliquer une quarantaine de lignes entre les deux écrans (implem) | `plan.md` §Périmètre, §Hors scope |
| Tests de reprise autonomes | `LinkWebTestCase::submit()` paramétré par route et nom de formulaire | Réutiliser l'aide de soumission existante (implem) | `plan.md` §Périmètre |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Aucun | — | — |

## Dette technique identifiée

_(aucune)_

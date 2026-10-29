# Report — Ajouter un lien en moins de 15 secondes

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/002-f-ajout-lien/`
> **Amont** : `pitch.md` · `plan.md`

## Synthèse

- **Écarts** : 3 — 3 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 8 / 8 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 0 item.

Livraison conforme au plan sur le modèle, le formulaire et le service. Les écarts sont des détails de présentation de la liste et un correctif de review sur le comptage des catégories.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Ligne de liste avec l'adresse du lien | Adresse cliquable, ouverte dans un nouvel onglet | Lecture naturelle d'une URL affichée (implem) | `plan.md` §Périmètre |
| Date d'origine affichée à l'heure de Paris | Format `jj/mm/aaaa` via le filtre `date` (fuseau par défaut `Europe/Paris`, `php.ini`) | `twig/intl-extra` absent du projet (implem) | `plan.md` §Périmètre |
| Limite « deux catégories » comptée sur les cases + le nom saisi | Un nom saisi identique à une catégorie cochée ne compte qu'une fois | Review — [BUG] catégorie cochée et ressaisie comptée deux fois | `plan.md` §Périmètre, §Stratégie de test |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Attribut `novalidate` sur le formulaire d'ajout | Les messages de validation viennent du serveur, en français (R11), plutôt que des bulles du navigateur (implem) | — |

## Dette technique identifiée

_(aucune)_

## Leçons apprises

- **`MockClock` est en UTC par défaut** : les dates attendues dans les tests unitaires doivent préciser `Europe/Paris`, fuseau de l'application (`php.ini`).

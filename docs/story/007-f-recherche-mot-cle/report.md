# Report — Retrouver un lien par mot

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/007-f-recherche-mot-cle/`
> **Amont** : `pitch.md` · `plan.md`

## Synthèse

- **Écarts** : 1 — réaligné dans `pitch.md` et `plan.md`.
- **Critères** : 7 / 7 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 0 item.

La recherche est livrée comme prévu ; seule la règle de casse a été précisée pour les lettres accentuées.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Saisie mise en minuscules ; « casse ignorée, accents stricts » | Saisie gardée telle quelle, `LIKE` sans `LOWER()` ; la casse des lettres accentuées compte | `LOWER()` et `LIKE` de SQLite ne traitent que l'ASCII : une majuscule accentuée saisie ne correspondait plus à rien (implem) | `pitch.md` §Règles métier (règle 3), `plan.md` §Approche retenue, §Mécanismes mobilisés, §Périmètre, §Stratégie de test |

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

## Leçons apprises

- **SQLite ne connaît la casse que pour l'ASCII** (`LIKE`, `LOWER()`, `UPPER()`) : toute comparaison de texte français insensible à la casse ou aux accents passe par PHP ou par une fonction SQLite enregistrée.

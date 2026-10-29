# Report — Compter chaque ouverture de lien avec son âge

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/009-f-mesure-clics-sortants/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 0.
- **Critères** : 6 / 6 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 1 item, dont 0 critique.

Livraison conforme au plan. Reste un mineur de review sur la fiabilité de la mesure face à une requête forgée.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Aucun | — | — | — |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Aucun | — | — |

## Dette technique identifiée

1. **[SECU] Mesure gonflable par une requête GET forgée** — `src/Controller/LinkController.php` (`app_link_open`) — un membre connecté qui charge une image ou une page pointant vers `/liens/{id}/ouvrir` compte une ouverture sans clic. À reconsidérer si les indicateurs (V2) montrent des valeurs aberrantes.

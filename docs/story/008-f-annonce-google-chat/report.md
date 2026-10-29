# Report — Annoncer chaque nouveau lien dans Google Chat

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/008-f-annonce-google-chat/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 0.
- **Critères** : 6 / 6 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 1 item, dont 1 critique.

Livraison conforme au plan, vérifiée avec le transport `null://` en test. L'envoi réel dépend d'une autorisation Workspace non vérifiable depuis le code.

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

1. **Envoi réel vers Google Chat non vérifié** — point « Hors review » de `review.md` : autorisation des webhooks entrants par l'administrateur Workspace, `GOOGLE_CHAT_DSN` à renseigner hors dépôt, rendu du message et lien « Voir dans Radar » à contrôler. **Critique** à la mise en production : sans ce réglage, aucune annonce ne part.

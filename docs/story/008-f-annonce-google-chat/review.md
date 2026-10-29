# Review — Annoncer chaque nouveau lien dans Google Chat

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/008-f-annonce-google-chat/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree, 10 fichiers modifiés + 5 nouveaux

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 0 / 0
- **Statut** : **PRÊT À COMMITER**

`/forge:sync`, puis `/forge:commit`. Fichier conservé pour le point « Hors review » ci-dessous.

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

_(aucun)_

## Points positifs

- **Inactif par défaut** : `GOOGLE_CHAT_DSN=null://null`, aucun secret dans le dépôt.
- **Idempotence** : `announcedAt` marqué dans le flush de l'aperçu, testé sur un double appel.
- **Envoi découplé** : `ChatMessage` passe par le transport `async` existant, l'ajout ne dépend jamais de Google Chat.

## Hors review (à vérifier en environnement réel)

- Envoi réel vers l'espace de l'équipe : vérifier avec l'administrateur Workspace que les webhooks entrants sont autorisés, renseigner `GOOGLE_CHAT_DSN` hors dépôt, ajouter un lien et contrôler le rendu du message (gras, italique, liens `<url|texte>`) et le lien « Voir dans Radar » (`DEFAULT_URI` de production).

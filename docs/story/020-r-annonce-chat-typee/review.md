# Review — Typer la carte d'annonce Google Chat : icône, couleur et action par type

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/020-r-annonce-chat-typee/`
> **Amont** : `plan.md`
> **Diff examiné** : `main...HEAD`, 1 commit (`60c1132`), 3 fichiers de test, +28/-7 ; working tree : 2 documents modifiés dans `docs/redesign/`, `docs/redesign/google-chat/` et cette story non suivis

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 0 / 2
- **Statut** : **PRÊT À COMMITER SOUS RÉSERVE** : seule l'étape 1 sur 5 du plan est réalisée. Cette review ne couvre aucun code de production, et la story ne peut pas être fusionnée en l'état.

Reprendre `/forge:refactor-implem` à l'étape 2, puis relancer `/forge:review` sur l'ensemble du diff avant `/forge:sync`.

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[TEST] Le test d'échec ne couvre que l'échec de mise en file, pas l'échec du webhook** — `tests/Service/LinkAnnouncerTest.php:71` — `ChatMessage` est routé sur `async` : une erreur du webhook survient dans le worker, après `markAnnounced()`, et le lien reste marqué annoncé. Test renommé en `testUnqueuedAnnouncementPropagatesAndLeavesTheLinkUnannounced` ; le message de `60c1132`, déjà publié, reste inchangé.
- [x] **[DOC] Le README du redesign annoncera des icônes déplacées** — `docs/redesign/README.md:9` — après l'étape 2, `google-chat/` ne contiendra plus de PNG. « icônes PNG » retiré de la ligne, ce fichier s'ajoute au §Périmètre livré.

## Points positifs

- **Tests non vacants** : retirer `remove()` ferait échouer `LinkRemovalTest` et `LinkBannerStoreTest`, puisque le fichier est écrit juste avant. Le test d'échec échouerait aussi si `markAnnounced()` passait avant `send()`.
- **Arrangements découplés de `store()`** : le filet de `forget()` survivra à l'étape 4 sans réécriture.
- **Paramètre nommé `failure:`** : le test ne dépend pas de `$storage`, qui disparaît à l'étape 3.
- **Périmètre tenu** : aucun fichier de `src/` ni de `config/` dans le diff, `make lint` à zéro finding et 18 tests verts sur les 3 classes touchées.

# Report — Publier automatiquement les articles des sources suivies

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/012-f-sources-automatiques/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 15 — 14 réalignés dans `plan.md`/`pitch.md`, 1 laissé tel quel.
- **Critères** : 12 / 12 critères d'acceptation atteints, cochés dans `pitch.md` ; 8 / 8 critères de sortie cochés dans `plan.md`.
- **Non livré** : aucun.
- **Dette** : 3 items, dont 1 critique.

Le livré dépasse le pitch sur deux ajouts demandés après la review : la teinte orange des cartes de source et la relève à la demande avec antériorité, qui assouplit la règle « historique ignoré ». La normalisation des adresses conserve désormais l'ancre, pour tous les liens. Le reste des écarts tient au découpage imposé par Mago et à la source de fixtures.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Normalisation R6 inchangée (ancre retirée) | Ancre conservée, seule la directive `:~:text=` est retirée, pour tous les liens | Décision user : le flux PHP.net distingue ses actualités par l'ancre (`index.php#2026-10-26-1`) | `plan.md` §Approche retenue, §Périmètre, §Stratégie de test |
| Source de fixtures « PHP.net » active | Source « Martin Fowler », relevée puis retirée | Le flux PHP.net publiait 885 entrées à la première relève en dev ; une source retirée n'est jamais relevée | `plan.md` §Périmètre, §Risques et mitigations |
| `src/Scheduler/SourceSchedule.php` | `src/Schedule.php`, avec `processOnlyLastMissedRun` | Emplacement de la recette Flex de `symfony/scheduler` (implem) | `plan.md` §Mécanismes mobilisés, §Périmètre, §Stratégie de test |
| `SourcePublisher` utilise le compte « radar » ; `LinkManager` gère le doublon | Le compte est passé par le handler (et le contrôleur) ; le doublon est écarté par `findDuplicate()` avant `publish()` | Seuils de complexité Mago (implem) | `plan.md` §Approche retenue, §Périmètre |
| `config/services.yaml` lie `RADAR_ADMIN_EMAIL` | `#[Autowire(env: 'RADAR_ADMIN_EMAIL')]` dans `AdminManager`, `services.yaml` inchangé | Option envisagée par le plan, retenue à l'implémentation | `plan.md` §Périmètre |
| Tests unitaires `SourceManagerTest`, `SourcePublisherTest`, `LinkManagerTest` | Non écrits : couverts en fonctionnel (`SourceControllerTest`, `SourceLinkTest`, `SourceFetchTest`) | Consigné par l'implem (« testés en fonctionnel ») | `plan.md` §Périmètre, §Stratégie de test |
| `tests/Scheduler/SourceScheduleTest.php` | `tests/ScheduleTest.php` | Suit le déplacement du planning | `plan.md` §Périmètre, §Stratégie de test |
| Règle 7 : historique du flux toujours ignoré | Ignoré par la relève horaire, reprenable sur 72 h au plus par une relève à la demande | Ajout demandé après review (relève avec antériorité) | `pitch.md` §Règles métier (7, 8, 9), §Hors scope |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Relève d'une source à la demande (bouton « Relever », `POST /sources/{id}/relever`), avec antériorité `FetchWindow` (1 h à 72 h) et dates `pubDate` / `published` / `updated` | Demandé après review | `pitch.md` §User Stories, §Règles métier (8) ; `plan.md` §Approche retenue, §Périmètre, §Stratégie de test |
| Cartes des liens de source en orange, prioritaire sur la teinte de la plateforme | Demandé après review | `pitch.md` §Règles métier (14) ; `plan.md` §Approche retenue, §Périmètre |
| `UserRepository::findMembersOrderedByEmail()` : le compte « radar » exclu des posteurs d'origine | Review [BUG] « Le compte « radar » est proposé comme posteur d'origine dans « Reprendre un lien de Chat » » | `plan.md` §Périmètre, §Stratégie de test |
| `reactivate()` remet l'état de relève à zéro | Review [ROBUSTESSE] « Une source réactivée affiche l'état de relève de sa vie précédente » | `plan.md` §Périmètre |
| `XmlNodes`, `FeedItem::tryFrom`, `Feed::fromItems` | Extraits pour la complexité Mago (implem) | `plan.md` §Périmètre |
| `AlreadyFollowedSourceException`, `AlreadyAdminException`, `SourceWebTestCase` | Non tracée (découpage d'implémentation) | `plan.md` §Périmètre |
| `Makefile` : `make clean` supprime aussi `public/assets` | Non tracée, sans lien avec la story | — |

## Dette technique identifiée

1. **Worker `scheduler_default` absent du déploiement de production** — point « Hors review » — l'ajouter au container FrankenPHP à sa construction (noté dans `docs/stack.md` §Cible). **Critique** au premier déploiement : sans lui, aucune relève, sans erreur visible ailleurs que la « dernière relève réussie » de `/sources`.
2. **Liens déjà en base dont l'adresse porte une ancre** — point « Hors review » — leur forme normalisée reste sans ancre (non renormalisés) : le même lien recollé avec son ancre n'est pas reconnu comme doublon. Renormaliser `link.normalized_url` par une migration de données si le cas se présente.
3. **E2E `SourceTest` dépendant du réseau** — point « Hors review » — il lit le vrai flux FeedBurner et se marque `skipped` s'il est injoignable.

## Leçons apprises

- **Une source active dans les fixtures relève un flux réel** : à la première relève en dev, PHP.net aurait publié 885 entrées. Des fixtures de source doivent être retirées, ou pointer vers un flux local.
- **Un compte système dans la table `user` apparaît dans toutes les listes d'utilisateurs** : le finding [BUG] de review l'a trouvé dans « Reprendre un lien de Chat ». Un plan qui introduit un compte système doit recenser les usages de `UserRepository` qui listent des membres.
- **Une source automatique révèle des formes d'adresse que les liens collés n'avaient pas** : la normalisation qui retirait l'ancre confondait les actualités de PHP.net. Tester la normalisation sur des flux réels avant de figer la règle de doublon.
- **Les seuils de complexité Mago dessinent les services** : trois extractions et un déplacement de responsabilité (doublon, posteur) à l'implémentation. Un plan de parseur ou de service orchestrateur gagne à prévoir ce découpage.

# CLAUDE.md

Radar — outil interne de veille technologique et produit : liens partagés en cartes enrichies, classés par besoin, annoncés dans Google Chat (voir `docs/vision.md`).

**Tradeoff :** ces directives privilégient la prudence sur la vitesse. Pour les tâches triviales, utilise ton jugement.

## 1. Réfléchir avant de coder

**Ne pas supposer. Ne pas cacher la confusion. Exposer les tradeoffs.**

Avant d'implémenter :

- Énoncer explicitement les hypothèses. En cas de doute, demander.
- Plusieurs interprétations possibles ? Les présenter — ne pas choisir en silence.
- Une approche plus simple existe ? Le dire. Pousser le débat quand c'est justifié.
- Quelque chose n'est pas clair ? S'arrêter. Nommer ce qui bloque. Demander.

Toute ambiguité → poser la question. Tout problème bloquant → remonter immédiatement.

## 2. Simplicité d'abord

**Le minimum de code qui résout le problème. Rien de spéculatif.**

- Aucune fonctionnalité au-delà de ce qui est demandé.
- Aucune abstraction pour un usage unique.
- Aucune "flexibilité" ou "configurabilité" non demandée.
- Aucune gestion d'erreur pour scénarios impossibles.
- 200 lignes alors que 50 suffisaient ? Réécrire.

Question à se poser : "Un dev sénior trouverait-il cela sur-conçu ?" Si oui, simplifier.

## 3. Modifications chirurgicales

**Ne toucher que ce qui doit l'être. Nettoyer uniquement ses propres miettes.**

- Ne pas "améliorer" le code adjacent, les commentaires, le formatage.
- Ne pas refactorer ce qui n'est pas cassé.
- Respecter le style existant, même si différent du tien.
- Code mort sans rapport repéré ? Le signaler — pas le supprimer.
- Imports/variables/fonctions devenus orphelins **par ta modif** → à supprimer.

Test : chaque ligne modifiée doit se rattacher directement à la demande utilisateur.

## 4. Exécution pilotée par l'objectif

**Définir le critère de succès. Itérer jusqu'à vérification.**

Transformer les tâches en objectifs vérifiables :

- "Ajouter une validation" → "Écrire les tests pour les entrées invalides, puis les faire passer"
- "Corriger le bug" → "Écrire un test qui le reproduit, puis le faire passer"
- "Refactor X" → "Tests verts avant et après"

Pour le multi-étapes : suivre le workflow `PLAN → ANALYZE → BUILD → TEST` (voir skill `/forge:help`). **Ne jamais passer à la phase suivante sans validation du user.**

## Commandes

Toutes les commandes PHP passent par `symfony` CLI — jamais `php` directement. `make help` liste les cibles.

## Règles critiques

- `declare(strict_types=1)` partout ; Mago à zéro finding, sans baseline
- Fixtures dans `fixtures/` (PSR-4 : `DataFixtures\`) — **PAS** dans `src/DataFixtures/`
- Ne jamais modifier une migration commitée — en créer une nouvelle
- Ne jamais modifier `vendor/`
- Pas de `dump()`, `var_dump()`, `dd()` dans le code commité
- Toute modif de schéma = migration générée par `symfony console make:migration`
- PHPUnit 13 : `createStub()` sans attentes, `createMock()` avec `expects()`
- Playwright : tests PHPUnit dans `tests/E2E/` (étendre `E2ETestCase`, testsuite `e2e` exclue de `make phpunit`), sélecteurs `data-test="..."`, base URL via `PLAYWRIGHT_BASE_URL` (défaut `https://radar.wip`)
- Enums : backed string enums dans `src/Enum/Type/`
- Mailer : classes dédiées dans `src/Mailer/` avec `TemplatedEmail`

## Identifiants de test

- `admin@example.com` / `password` (ROLE_USER)

## Architecture

**Interdit** : QueryBuilder hors repository, logique métier dans controller/entity/repository, `new Service()`, entity qui injecte un service.

## Skills disponibles

Deux plugins structurent le travail sur ce dépôt : `forge` (marketplace `gabrielmustiere/forge`) et `symfony` (marketplace `gabrielmustiere/skills`).

### Plugin `forge` — pipeline de développement

Cycle complet vision → backlog → feature/refacto/tech → release.

| Skill                    | Usage                                                |
|--------------------------|------------------------------------------------------|
| `/forge:help`            | Vue d'ensemble du pipeline                           |
| `/forge:vision`          | Phase 0 : cadrer la vision produit                   |
| `/forge:product-backlog` | Construire/maintenir le backlog                      |
| `/forge:feature-pitch`, `/forge:feature-plan`, `/forge:feature-implem` | Track feature complet |
| `/forge:refactor-plan`, `/forge:refactor-implem` | Track refacto                        |
| `/forge:tech-plan`, `/forge:tech-implem` | Track technique                              |
| `/forge:test-scenario`   | Spécifier les scénarios de test                      |
| `/forge:commit`, `/forge:release` | Sortie de livrables                         |
| `/forge:review`, `/forge:sync` | Suivi & qualité                                |
| `/forge:doc-feature`     | Cas particuliers                                     |

### Plugin `symfony` — recettes framework

À invoquer quand on touche au domaine concerné (controllers, doctrine, forms, events, messenger, validation, etc.).

Préfèrer ces skills aux conventions ad hoc — elles encodent les patterns retenus pour ce template.

---

**Ces directives fonctionnent si :** moins de modifications inutiles dans les diffs, moins de réécritures dues à la sur-conception, et les questions de clarification arrivent **avant** l'implémentation plutôt qu'après les erreurs.

<!-- BEGIN AI_MATE_AGENTS_IMPORT -->
@AGENTS.md
<!-- END AI_MATE_AGENTS_IMPORT -->

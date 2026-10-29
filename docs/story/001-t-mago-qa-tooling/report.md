# Report — Remplacer PHPStan, PHP-CS-Fixer et PHP Insights par Mago

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/001-t-mago-qa-tooling/`
> **Amont** : `plan.md` · `review.md`

## Synthèse

- **Écarts** : 9 — 9 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 8 / 8 atteints, cochés dans `plan.md` §Critères de sortie.
- **Non livré** : aucun.
- **Dette** : 1 item, dont 0 critique.
- **Métriques** : paquets QA PHP 8 → 1 (cible 1) ; fichiers de config QA 4 → 1 (cible 1) ; arbres Composer 2 → 1 (cible 1) ; findings Mago n/a → 0 sans baseline (cible 0) ; mentions résiduelles 42 → 0 (cible 0).

La bascule est conforme au plan sur la brique et les métriques. Les écarts tiennent à des fichiers générés sortis du périmètre de Mago, au formatter laissé au preset par défaut sur décision utilisateur, et à une baseline remesurée après une story intermédiaire. Reste un risque supply chain accepté.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Formatter réglé pour approcher `@Symfony` ; « style par défaut Mago sans réglage » écarté | Preset par défaut, aucun réglage | Décision utilisateur à la clôture : 6 fichiers reformatés seulement, l'objectif « limiter le diff » est tenu sans réglage | `plan.md` §Approche retenue, §Alternatives écartées, §Périmètre (`mago.toml`), §Questions ouvertes |
| `migrations/**` reformatées et corrigées | `migrations/` et `mate/extensions.php` exclus de Mago ; la migration reformatée à l'étape 3 restaurée à l'identique | Fichiers générés ; règle `CLAUDE.md` « ne jamais modifier une migration commitée » ; `mate/extensions.php` réécrit par `mate discover` | `plan.md` §Approche retenue, §Périmètre |
| `quality` = `fmt mago-lint analyze build` | `quality` = `fmt mago-fix analyze build`, `mago-fix` = `mago lint --fix --format-after-fix` | Question ouverte tranchée en (a) ; `--format-after-fix` ajouté par la review — [ROBUSTESSE] `make quality` formate avant d'appliquer les correctifs du linter | `plan.md` §Approche retenue, §Périmètre (`Makefile`), §Questions ouvertes |
| Seuil de complexité à fixer à l'étape 2 | Seuil par défaut de Mago (15 par classe), exclusions Entity/Form/Repository vérifiées effectives | Non tracée (défaut conservé) | `plan.md` §Approche retenue, §Questions ouvertes |
| Analyseur strict sans exception | Exceptions scopées : `public/index.php` hors analyse, `missing-constructor` ignoré dans `tests/E2E/`, `no-literal-password` hors `tests/`, assertions en `self::`, initialiseurs `setUp` / `setUpPlaywright` / `setContainer` | Faux positifs framework et convention du projet, chacun justifié en commentaire dans `mago.toml` (implem) | `plan.md` §Approche retenue |
| `@phpstan-ignore` de `User.php:18` remplacé si Mago lève le même finding | Supprimé sans remplacement ; `@mago-expect analysis:invalid-type-cast` posé sur le cast de `__serialize()` | Mago ne lève pas de finding sur `$id` ; le cast `(array) $this` est l'idiome Symfony qui conserve les clés privées préfixées | `plan.md` §Périmètre |
| E2E dans `e2e/` (Playwright JS) | E2E PHP dans `tests/E2E/`, donc dans le périmètre de Mago | Migration vers Playwright PHP livrée entre le cadrage et l'implémentation | `plan.md` §Stratégie de test |
| Sécurité : « version épinglée via `composer.lock` » | Le lock épingle la version, pas le contenu : aucun checksum vérifié par le wrapper | Review — [SECU] Binaire Mago téléchargé sans contrôle d'intégrité | `plan.md` §Impacts transverses |
| Baseline mesurée sur le commit initial | Remesurée sur `7c57991` (21 fichiers, 42 mentions dans 11 fichiers) | Story intermédiaire (Playwright PHP, release `v0.1.0`) ; `CHANGELOG.md` exclu du comptage car l'entrée `0.1.0` est publiée | `plan.md` §Métriques (commit de baseline `71991f8`), §Critères de sortie |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| `declare(strict_types=1)` dans 10 fichiers, contrôleur `final`, `#[\Override]`, `#[\SensitiveParameter]`, octal explicite, fonction fléchée dans `public/index.php` | Findings `mago lint` / `mago analyze` corrigés à l'étape 4 | `plan.md` §Périmètre |
| Retrait de `symfony composer install --working-dir=tools/phpinsights` dans `make install` | Conséquence directe de la suppression de `tools/` | `plan.md` §Périmètre (`Makefile`) |
| Nettoyage des entrées `/e2e` et `/playwright.config.ts` de `.gitattributes` (commit `c434dd3`) | Oubli de la migration Playwright PHP repéré à l'étape 2, hors story | — |

## Dette technique identifiée

1. **[SECU] Binaire Mago téléchargé sans contrôle d'intégrité** — `vendor/carthage-software/mago/composer/src/internal.php:354` — le wrapper récupère l'archive de la release GitHub par numéro de version, sans checksum ; `composer.lock` épingle la version, pas le contenu du binaire. Risque déjà accepté au plan (§Impacts transverses) mais formulé comme couvert par le lock : préciser le risque au `/sync`, et le recouper à la story CI (cache du binaire, `GITHUB_TOKEN`).

## Leçons apprises

- **Exclure les fichiers générés avant le premier `mago fmt`** : le formatter a réécrit une migration commitée, qu'il a fallu restaurer. Un prochain outil de style ou d'analyse sur ce dépôt doit partir avec `migrations/` et les fichiers gérés par Mate exclus.
- **L'analyseur ne suit pas un `setUp()` hérité d'une classe abstraite** : `check-property-initialization` signale chaque sous-classe d'`E2ETestCase` ; `class-initializers` ne suffit pas, il faut un `ignore` scopé à `tests/E2E/`.
- **Un plan cadré avant une autre story se périme vite** : baseline, chemins (`e2e/`) et commande de mesure étaient faux à l'implémentation. Remesurer la baseline sur le `HEAD` réel avant d'implémenter.

# Plan technique — Remplacer PHPStan, PHP-CS-Fixer et PHP Insights par Mago

> **But** : figer le comment du remplacement de l'outillage QA PHP par Mago — brique retenue, config, métriques, bascule et rollback.
> **Registre** : technique
> **Story** : `docs/story/001-t-mago-qa-tooling/`
> **Amont** : aucun

## Motivation

L'outillage QA PHP repose aujourd'hui sur trois outils distincts, chacun avec sa config et ses dépendances :

- **PHPStan 2.2** niveau 10 + 4 extensions (`phpstan-doctrine`, `phpstan-symfony`, `phpstan-phpunit`, `phpstan-strict-rules`) + `tomasvotruba/cognitive-complexity` (`composer.json` require-dev, `phpstan.dist.neon`).
- **PHP-CS-Fixer** en `@Symfony` + `concat_space: one` (`.php-cs-fixer.dist.php`).
- **PHP Insights**, isolé dans `tools/phpinsights/` avec son propre `composer.json` / `composer.lock`, à cause d'un conflit `sebastian/diff` avec PHPUnit 13 (`tools/phpinsights/composer.json`).

Soit 8 paquets QA, 4 fichiers de config, un second arbre Composer à maintenir, et 4 cibles Make spécifiques (`phpstan`, `php-cs-fix`, `php-cs-check`, `insights`). Mago fournit formatter, linter et analyseur statique dans un seul binaire, piloté par un seul `mago.toml`.

Le driver est la **simplicité de l'outillage**, pas la vitesse : sur 16 fichiers PHP, le gain de temps est négligeable en valeur absolue.

**Pourquoi maintenant** : la CI GitHub Actions est à construire (`docs/stack.md` § Cible non encore en place) et le code ne compte que 16 fichiers PHP. Basculer avant d'écrire la CI évite de câbler trois outils qu'on supprimerait ensuite, et le reformatage complet coûte quasi rien tant que le code est petit — il deviendra un diff massif et conflictuel à mesure que les features arrivent.

## Approche retenue

Mago (`carthage-software/mago`) est ajouté en `require-dev` Composer et remplace intégralement les trois outils : `mago fmt` remplace PHP-CS-Fixer, `mago lint` remplace PHP Insights et la complexité cognitive, `mago analyze` remplace PHPStan. Un `mago.toml` unique à la racine porte la config : analyseur en mode strict maximal, linter avec intégrations `symfony` et `phpunit`, `cyclomatic-complexity` à la place de la complexité cognitive (mêmes exclusions `src/Entity`, `src/Form`, `src/Repository`), formatter réglé pour approcher `@Symfony` + `concat_space: one`. Zéro baseline : tous les findings sont corrigés au moment de la bascule.

Les points d'entrée `make lint` (lecture seule) et `make quality` (corrige + build) gardent leur nom et leur rôle ; seules les cibles unitaires changent (`fmt`, `fmt-check`, `mago-lint`, `analyze`). Mago s'invoque via `symfony php vendor/bin/mago`, conformément à la règle « toutes les commandes PHP passent par `symfony` CLI » (`CLAUDE.md`).

**Impact sur les clients existants** :

- Aucun code applicatif ni comportement runtime modifié : le reformatage ne touche que le style, `make phpunit` et `make playwright` doivent rester verts sans adaptation.
- Les développeurs et agents IA qui appellent `make phpstan`, `make php-cs-fix`, `make php-cs-check` ou `make insights` doivent passer aux nouvelles cibles ; `make lint` et `make quality` restent valides.
- Perte assumée des vérifications spécifiques Doctrine et Symfony de PHPStan (validation DQL, cohérence mapping ↔ types de propriétés, typage du container) : Mago n'a pas d'intégration Doctrine.

### Mécanismes mobilisés

- **Pattern** : remplacement d'outillage de dev, config déclarative unique (`mago.toml`).
- **Lib / composant** : **nouvelle dépendance** Composer `carthage-software/mago` (`require-dev`, `^1.59`). Wrapper PHP léger qui télécharge le binaire précompilé depuis les releases GitHub au premier appel puis le met en cache. 8 paquets QA retirés.
- **Mécanisme d'extension** : aucun — outillage hors runtime, aucune modification de `vendor/`. Les recettes Flex de `friendsofphp/php-cs-fixer` et `phpstan/phpstan` sont désinstallées par `composer remove` (blocs `.gitignore` et entrées `symfony.lock`).

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Mago `fmt` + `lint` seulement, PHPStan conservé comme filet de typage | Garde deux outils et les 5 paquets PHPStan : contraire au driver de simplicité ; la perte de couverture Doctrine/Symfony est assumée. |
| Cohabitation Mago + PHPStan puis suppression sur critère d'écart chiffré | Allonge la bascule pour un code de 16 fichiers ; aucun corpus de défauts n'est retenu comme métrique (simplicité seule). |
| Installation Homebrew / binaire global | Version non épinglée dans le dépôt, installation séparée par poste et en CI. |
| Image Docker `ghcr.io/carthage-software/mago` | `docker run` à chaque commande en local, plus lourd que le wrapper Composer. |
| Style par défaut Mago (PER-CS) sans réglage | Choix utilisateur : approcher le style `@Symfony` actuel pour limiter le diff de reformatage. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `mago.toml` | Config unique Mago : sources et exclusions, formatter proche `@Symfony`, linter strict (intégrations `symfony` + `phpunit`, `cyclomatic-complexity` avec exclusions Entity/Form/Repository), analyseur strict. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `composer.json` | Ajouter `carthage-software/mago` en `require-dev` ; retirer `friendsofphp/php-cs-fixer`, `phpstan/phpstan`, `phpstan/phpstan-doctrine`, `phpstan/phpstan-phpunit`, `phpstan/phpstan-strict-rules`, `phpstan/phpstan-symfony`, `tomasvotruba/cognitive-complexity`. **Conserver** `phpstan/phpdoc-parser` (dépendance runtime de `require`). |
| `composer.lock` | Régénéré par `composer require` / `composer remove`. |
| `symfony.lock` | Entrées `friendsofphp/php-cs-fixer` et `phpstan/phpstan` retirées par Flex. |
| `Makefile` | Remplacer les cibles `phpstan`, `php-cs-fix`, `php-cs-check`, `insights` par `fmt`, `fmt-check`, `mago-lint`, `analyze` ; `lint` = `fmt-check mago-lint analyze` ; `quality` = `fmt mago-lint analyze build`. |
| `.gitignore` | Retirer les blocs `friendsofphp/php-cs-fixer`, `phpstan/phpstan` et la ligne `/tools/phpinsights/vendor/` (avec son commentaire). |
| `.gitattributes` | Retirer `/.php-cs-fixer.dist.php` et `/phpstan.dist.neon` ; ajouter `/mago.toml export-ignore`. |
| `src/Entity/User.php` | Supprimer `@phpstan-ignore property.unusedType` (l.18), remplacé par une suppression Mago équivalente seulement si Mago lève le même finding. |
| `src/**`, `tests/**`, `config/**`, `bin/**`, `public/**`, `fixtures/**`, `migrations/**`, `mate/**` (`*.php`) | Reformatage `mago fmt` (style uniquement) puis corrections des findings `mago lint` / `mago analyze`. `config/bundles.php` et `config/reference.php` restent exclus, comme aujourd'hui. |
| `README.md` | Ligne « Qualité » (l.19) et table des commandes (l.55-58) alignées sur Mago et les nouvelles cibles. |
| `CLAUDE.md` | Stack « Qualité : PHPStan level 10 + PHP-CS-Fixer » (l.61) → Mago ; commentaire de `make quality` (l.74). |
| `docs/stack.md` | Section DevOps « Analyse statique / style » → Mago ; entrée de changelog. |
| `templates/page/index.html.twig` | Description de l'action `make quality` (l.101) : « CS-Fixer + PHPStan » → « Mago ». |
| `phpstan.dist.neon` | Supprimé. |
| `.php-cs-fixer.dist.php` | Supprimé. |
| `phpinsights.php` | Supprimé. |
| `tools/phpinsights/composer.json`, `tools/phpinsights/composer.lock` | Supprimés, avec le dossier `tools/` devenu vide. |

## Hors scope

- **CI GitHub Actions** : story dédiée (`docs/stack.md` § Cible non encore en place) ; elle câblera `make lint` et devra fournir `GITHUB_TOKEN` au premier téléchargement du binaire Mago.
- **Hooks Git (pre-commit)** : non demandés.
- **Rector ou tout autre outil de refactoring automatique** : absent aujourd'hui, pas introduit.
- **Outillage front (Tailwind, Playwright, JS)** : non concerné par Mago.
- **`AGENTS.md`** : généré par `mate discover`, ne mentionne pas l'outillage QA — non modifié.
- **Mise à jour des plugins forge / symfony** (références qui citent PHPStan / ECS) : hors dépôt ; la ref stack demande déjà de suivre le `Makefile` / `CLAUDE.md` du projet.

## Impacts transverses

- **Modules clients impactés** : aucun module applicatif ; consommateurs = développeurs et agents IA via `Makefile` et `CLAUDE.md`.
- **Migration de données** : aucune.
- **Impacts prod / infra** : aucun — outillage `require-dev`, absent de l'image de prod. Le premier appel à `vendor/bin/mago` nécessite un accès réseau à GitHub (téléchargement du binaire).
- **Droits d'accès** : inchangé.
- **Sécurité** : nouveau vecteur supply chain — binaire téléchargé depuis les releases GitHub de `carthage-software/mago` par le wrapper Composer. Version épinglée via `composer.lock`. Surface réduite par ailleurs : 8 paquets QA et un arbre Composer isolé retirés.

## Rollback et kill switch

- **Kill switch** : sans objet — aucun chemin de prod touché. Retour arrière par `git revert` des commits de la story (étapes 2 à 6), suivi de `composer install`. Jusqu'à l'étape 5 incluse, l'ancien outillage est encore installé et utilisable.
- **Comportement en panne de dépendance** : si le binaire Mago ne peut être téléchargé (GitHub indisponible, rate limit), `make lint` échoue ; contournement par `GITHUB_TOKEN`. Une fois en cache, plus aucun appel réseau.
- **Cohabitation ancienne/nouvelle version** : oui, transitoire — Mago est ajouté (étape 2) avant le retrait de l'ancien outillage (étape 5), ce qui permet de comparer et de revenir en arrière sans réinstaller.
- **Sessions / cookies** : aucun impact.

## Métriques (baseline → cible)

Driver = simplicité, pas de métrique de couverture ni de durée retenue. Baseline mesurée le 2026-10-29 à l'étape 1, sur l'état du commit initial (dépôt initialisé à cette étape) : `make lint` vert (CS-Fixer 0/16 fichier, PHPStan 0 erreur, 6,1 s), `make phpunit` vert (8 tests, 25 assertions), `make playwright` vert (4 tests).

Baseline remesurée le 2026-10-29 sur `7c57991` (après la migration des E2E vers Playwright PHP et la release `v0.1.0`) : `make lint` vert (CS-Fixer 0/21 fichier, PHPStan 0 erreur, 1,9 s), `make phpunit` vert (8 tests, 25 assertions), `make playwright` vert (4 tests PHPUnit dans `tests/E2E/`), mentions résiduelles 42 lignes dans 11 fichiers (dont 1 dans `CHANGELOG.md`, entrée `0.1.0` publiée, exclue de la cible).

| Métrique | Baseline | Cible | Méthode de mesure |
|---|---|---|---|
| Paquets QA PHP installés | 8 (7 en `require-dev` + `nunomaduro/phpinsights` dans `tools/`) | **1** (`carthage-software/mago`) | Lecture de `composer.json` ; `test ! -d tools` |
| Fichiers de config QA PHP | 4 (`phpstan.dist.neon`, `.php-cs-fixer.dist.php`, `phpinsights.php`, `tools/phpinsights/composer.json`) | **1** (`mago.toml`) | `ls` à la racine |
| Arbres Composer | 2 (racine + `tools/phpinsights`) | **1** | `find . -name composer.json -not -path './vendor/*'` |
| Findings Mago (fmt + lint + analyze) | n/a (non installé) | **0, sans baseline** | `make lint` (code retour 0, aucun fichier baseline) |
| Mentions résiduelles de l'ancien outillage | 41 lignes dans 10 fichiers (42 / 11 sur `7c57991`) | **0 ligne** hors `phpstan/phpdoc-parser` et `CHANGELOG.md` | `grep -rniE "phpstan\|cs-fixer\|phpinsights" . --exclude-dir={vendor,node_modules,var,docs,.git} --exclude={composer.lock,CHANGELOG.md} \| grep -v phpdoc-parser` |

## Stratégie de test

Aucun nouveau test : la story ne change aucun comportement. Les suites existantes servent de filet contre une régression introduite par le reformatage ou les corrections de findings.

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `tests/` (suite complète, `make phpunit`) | unit / functional | Le reformatage `mago fmt` et les corrections `lint` / `analyze` ne changent aucun comportement. |
| `e2e/` (`make playwright`) | E2E | Parcours existants intacts après reformatage (templates non touchés sauf `index.html.twig:101`). |
| `make lint` | statique | `mago fmt --check`, `mago lint` et `mago analyze` à 0 finding, sans baseline. |

**Hors scope tests** :

- L'équivalence de détection Mago vs PHPStan (Doctrine, Symfony, strict-rules) n'est pas mesurée : choix explicite « simplicité seule ». La perte de couverture est assumée (cf. §Risques).
- Le téléchargement du binaire en CI (`GITHUB_TOKEN`) ne se vérifie qu'à la story CI.

## Ordre d'exécution

1. [x] **Baseline sur l'outillage actuel**
   - Objectif : dépôt versionné, `composer install` fait, `make lint` et `make phpunit` verts avec PHPStan + CS-Fixer ; compteurs de §Métriques confirmés et cités avec le commit.
   - Fichiers : aucun (corrections éventuelles si l'existant n'est pas vert — à signaler, pas à masquer).
   - Vérification : `git rev-parse HEAD` OK ; `make lint` et `make phpunit` code retour 0.
   - Commitable seule : oui (rien à commiter si déjà vert).

2. [ ] **Ajouter Mago en cohabitation**
   - Objectif : `composer require --dev carthage-software/mago:^1.59`, `mago.toml` strict (via `mago init` puis durci), nouvelles cibles `fmt`, `fmt-check`, `mago-lint`, `analyze`. Ancien outillage encore présent, `lint` / `quality` inchangés à ce stade.
   - Fichiers : `composer.json`, `composer.lock`, `mago.toml`, `Makefile`, `.gitattributes`.
   - Vérification : `symfony php vendor/bin/mago --version` répond ; `make analyze` et `make mago-lint` s'exécutent (findings attendus).
   - Commitable seule : oui.

3. [ ] **Reformater le code avec `mago fmt`**
   - Objectif : commit dédié au style seul, pour un diff lisible et isolable dans `git blame`.
   - Fichiers : `*.php` des dossiers listés en §Périmètre.
   - Vérification : `make fmt-check` vert ; `make phpunit` et `make playwright` verts.
   - Commitable seule : oui.

4. [ ] **Corriger les findings `lint` et `analyze` jusqu'à zéro**
   - Objectif : 0 finding sans baseline ; migrer ou supprimer l'annotation `src/Entity/User.php:18`.
   - Fichiers : fichiers PHP concernés par les findings, `mago.toml` si une règle doit être exclue sur un chemin (justifiée en commentaire).
   - Vérification : `make mago-lint` et `make analyze` verts ; `make phpunit` vert.
   - Commitable seule : oui.

5. [ ] **Retirer l'ancien outillage et basculer `lint` / `quality`**
   - Objectif : `composer remove` des 7 paquets (pas `phpstan/phpdoc-parser`), suppression des configs et de `tools/`, `lint` et `quality` recâblés sur Mago, anciennes cibles supprimées.
   - Fichiers : `composer.json`, `composer.lock`, `symfony.lock`, `Makefile`, `.gitignore`, `.gitattributes`, suppression de `phpstan.dist.neon`, `.php-cs-fixer.dist.php`, `phpinsights.php`, `tools/phpinsights/`.
   - Vérification : `make lint` et `make quality` verts ; `make phpunit` vert ; `composer validate` OK.
   - Commitable seule : oui.

6. [ ] **Aligner la documentation**
   - Objectif : plus aucune mention de l'ancien outillage dans la doc projet et l'UI.
   - Fichiers : `README.md`, `CLAUDE.md`, `docs/stack.md`, `templates/page/index.html.twig`.
   - Vérification : commande de §Métriques « mentions résiduelles » à 0 ; `make playwright` vert.
   - Commitable seule : oui.

## Critères de sortie

- [ ] `make phpunit` et `make playwright` verts après la bascule.
- [ ] `make lint` vert : `mago fmt --check`, `mago lint`, `mago analyze` à 0 finding, sans fichier de baseline.
- [ ] `make quality` vert.
- [ ] `composer.json` ne contient plus qu'un paquet QA PHP (`carthage-software/mago`) et conserve `phpstan/phpdoc-parser` en `require`.
- [ ] `mago.toml` est le seul fichier de config QA PHP ; `phpstan.dist.neon`, `.php-cs-fixer.dist.php`, `phpinsights.php` et `tools/` n'existent plus.
- [ ] Le `mago.toml` active les intégrations `symfony` et `phpunit` et `cyclomatic-complexity` avec exclusions `src/Entity`, `src/Form`, `src/Repository`.
- [ ] Aucune mention résiduelle de PHPStan / PHP-CS-Fixer / PHP Insights hors `phpstan/phpdoc-parser` et `docs/story/`.
- [ ] Documentation projet alignée (`README.md`, `CLAUDE.md`, `docs/stack.md`, `templates/page/index.html.twig`).

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Régression de typage non détectée faute des extensions `phpstan-doctrine` / `phpstan-symfony` (DQL, mapping ↔ types, container) | moyenne | Risque assumé (simplicité seule) ; analyseur Mago en strict maximal ; tests fonctionnels comme filet ; à réévaluer si un bug de ce type passe en review. |
| `mago fmt` ne sait pas reproduire certaines règles `@Symfony` | élevée | Accepter le défaut Mago pour ces règles et les lister dans le report d'implémentation (cf. §Questions ouvertes). |
| Pas d'équivalent de la complexité cognitive | moyenne | `cyclomatic-complexity` avec les mêmes exclusions ; à confirmer à l'étape 2. |
| Téléchargement du binaire bloqué (rate limit GitHub, réseau) | faible en local, moyenne en CI | `GITHUB_TOKEN` / `GH_TOKEN` ; binaire en cache après le premier appel. |
| Faux positifs Mago sur les entités Doctrine (propriétés assignées par l'ORM) | moyenne | Suppression ciblée (`@mago-expect`) ou exclusion par chemin dans `mago.toml`, justifiée. |
| Dépôt non versionné localement au cadrage : rollback par `git revert` impossible | élevée si non traité | Étape 1 exige un dépôt Git avec commit de référence avant toute modification. |
| Agents IA ou habitudes qui appellent encore `make phpstan` / `make php-cs-fix` | faible | `CLAUDE.md` et `README.md` mis à jour ; `make lint` / `make quality` inchangés en tant que points d'entrée. |

## Questions ouvertes

- **Options de formatage `@Symfony` sans équivalent Mago** : selon ce que `mago fmt` expose. Options : (a) accepter le défaut Mago pour la règle concernée, (b) garder un outil de style secondaire. Recommandé : (a), le driver est la simplicité ; à trancher à l'étape 2 par `/tech-implem`.
- **Complexité cognitive** : Mago ne semble proposer que la complexité cyclomatique. Options : (a) `cyclomatic-complexity` avec les mêmes exclusions, (b) renoncer à la métrique. Recommandé : (a), garde une limite de complexité ; seuil à fixer à l'étape 2.
- **`make quality` lance `mago lint --fix` ou `mago lint`** : options : (a) `--fix`, (b) lecture seule. Recommandé : (a), cohérent avec le rôle « corrige » de `quality` (aujourd'hui `php-cs-fixer fix`).
- **Dépôt Git local** : le dossier n'est pas un dépôt Git au moment du cadrage alors que `docs/stack.md` déclare `gabrielmustiere/radar` sur GitHub. Options : (a) cloner / initialiser avant l'étape 1, (b) travailler sans Git. Recommandé : (a), le rollback repose sur `git revert`.

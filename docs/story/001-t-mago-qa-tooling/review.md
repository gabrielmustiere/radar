# Review — Remplacer PHPStan, PHP-CS-Fixer et PHP Insights par Mago

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/001-t-mago-qa-tooling/`
> **Amont** : `plan.md`
> **Diff examiné** : `origin/main..HEAD` (8 commits locaux), 30 fichiers, +663 / −6184 lignes (dont ~6450 de lockfiles et `tools/phpinsights` supprimé)

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 1 / 2
- **Statut** : **PRÊT À COMMITER**

`/forge:sync` pour réaligner le plan sur les écarts et écrire le `report.md`, puis `/forge:commit`.

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[ROBUSTESSE] `make quality` formate avant d'appliquer les correctifs du linter** — `Makefile:190` — `quality: fmt mago-fix analyze build` : un correctif `mago lint --fix` peut laisser du code non formaté, et `make lint` (`fmt-check`) échoue alors juste après un `make quality` vert. Corrigé : `mago-fix` lance `mago lint --fix --format-after-fix` ; `make quality` puis `make lint` verts.
- [ ] **[SECU] Binaire Mago téléchargé sans contrôle d'intégrité** — `vendor/carthage-software/mago/composer/src/internal.php:354` — le wrapper récupère l'archive de la release GitHub par numéro de version, sans checksum ; `composer.lock` épingle la version, pas le contenu du binaire. Risque déjà accepté au plan (§Impacts transverses) mais formulé comme couvert par le lock : préciser le risque au `/sync`, et le recouper à la story CI (cache du binaire, `GITHUB_TOKEN`).

## Points positifs

- **Bascule en cohabitation** : Mago ajouté, code reformaté et findings corrigés avant tout retrait — l'ancien outillage est resté installé jusqu'à l'étape 5, un commit par étape, chacun revertable seul.
- **Commit de style isolé** (`a275b07`) : le reformatage est séparé des corrections, lisible et ignorable dans `git blame`.
- **Chaque exception de `mago.toml` est justifiée en commentaire** et scopée au plus près (`ignore` limité à `tests/E2E/`, `no-literal-password` limité à `tests/`), plutôt qu'une règle désactivée globalement ; exclusions de complexité vérifiées effectives (seuil forcé à 1 : seules les classes hors Entity/Form/Repository remontent).
- **Règle projet respectée sur les migrations** : la migration reformatée à l'étape 3 a été restaurée à l'identique et `migrations/` sorti du périmètre, au lieu d'ajouter `#[\Override]` à un fichier commité.
- **Durcissement réel du code** : `declare(strict_types=1)` dans les 10 fichiers qui l'ignoraient (règle `CLAUDE.md`), `#[\SensitiveParameter]` sur les mots de passe, contrôleur `final` — sans régression (PHPUnit 8/8, E2E 4/4 sur le front controller modifié).

# Plan technique — Être averti qu'un lien existe déjà dans Radar

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/004-f-doublon-lien/`
> **Amont** : `pitch.md`

## Approche retenue

`LinkUrlAnalyzer` (qui absorbe l'ancien `SourceTypeResolver` : les deux lisent une adresse de lien) calcule la forme canonique d'une adresse (règles 2 et 3 du pitch) et son type de source. `Link` stocke cette forme dans `normalizedUrl`, protégée par un index unique. `LinkManager::add()` normalise l'URL **avant** toute création (la résolution de catégorie passe par `CategoryResolver`, la persistance par `LinkRepository::save()` — découpage imposé par le seuil de 5 paramètres de Mago) : si `LinkRepository::findOneByNormalizedUrl()` trouve un lien, il lève `DuplicateLinkException` (porteuse du lien existant) sans rien persister — la catégorie saisie n'est donc jamais créée. Le contrôleur attrape l'exception, ajoute une `FormError` sur le champ URL et rend le formulaire en 422 avec la carte existante et un lien vers sa page.

Une route `GET /liens/{id}` (`app_link_show`) affiche la carte seule, via l'`EntityValueResolver` (404 natif si l'id n'existe pas).

### Mécanismes mobilisés

- **Index unique Doctrine** (`UNIQ_LINK_NORMALIZED_URL`) : R6 garantie en base, même en cas de course.
- **Exception métier** : le service décide du doublon, le contrôleur ne fait que le présenter.
- **`FormError`** : message sur le champ URL, rendu par le thème de formulaire existant.
- **`EntityValueResolver`** (`#[MapEntity]` implicite sur `{id}`) : 404 sans code.
- **`Uri\WhatWg\Url`** (PHP 8.5) : décomposition fiable de l'adresse.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Contrainte de validation `UniqueEntity` sur le DTO | Le DTO n'est pas une entité et la carte existante doit remonter au contrôleur ; l'exception porte le lien. |
| Remplacer l'URL saisie par sa forme normalisée | L'adresse d'origine doit rester ouvrable telle quelle (certains sites exigent leurs paramètres). |
| Retirer tous les paramètres d'URL | Casserait `youtube.com/watch?v=…` et les pages paginées (règle 3). |

## Modèle de données

### Modification de `Link`

`src/Entity/Link.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `normalizedUrl` | string(2048) (`normalized_url`) | non | index unique `UNIQ_LINK_NORMALIZED_URL` ; défaut technique `''` |

Le défaut `''` n'existe que parce que SQLite refuse `ADD COLUMN … NOT NULL` sans défaut. La migration générée reconstruit la table `link` : sa clé étrangère vers `user` passe de `ON DELETE RESTRICT` à `NO ACTION` (même effet sous SQLite, `doctrine:schema:validate` vert) et elle échoue sur une table qui contient déjà deux liens ou plus. La table `link` est vide en production (application non déployée) ; les bases locales se réinitialisent (`make db-reset`, base de test recréée).

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Service/LinkUrlAnalyzer.php` | Forme canonique d'une adresse et type de source (remplace `src/Service/Preview/SourceTypeResolver.php`, supprimé). |
| `src/Service/CategoryResolver.php` | Catégorie existante de même nom ou nouvelle catégorie (extrait de `LinkManager`). |
| `src/Exception/DuplicateLinkException.php` | Doublon détecté, porte le lien existant. |
| `templates/link/show.html.twig` | Page d'une carte. |
| `migrations/VersionXXXX.php` | Colonne `normalized_url` + index unique (générée). |
| `tests/Service/LinkUrlAnalyzerTest.php` | Variantes équivalentes et distinctes ; table URL → type (remplace `SourceTypeResolverTest`). |
| `tests/Controller/LinkDuplicateTest.php` | Doublon refusé avec carte, catégorie non créée, paramètre significatif accepté, page de carte, 404. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Entity/Link.php` | Champ `normalizedUrl` posé au constructeur. |
| `src/Repository/LinkRepository.php` | `findOneByNormalizedUrl()`, `save()`. |
| `src/Service/LinkManager.php` | Normalisation, détection du doublon avant création ; dépend de `LinkRepository`, `LinkUrlAnalyzer`, `CategoryResolver`, horloge, bus. |
| `src/Controller/LinkController.php` | Gestion de `DuplicateLinkException` ; action `show`. |
| `templates/link/new.html.twig` | Bloc d'avertissement avec la carte existante. |
| `fixtures/AppFixtures.php` | URL normalisée des liens de démonstration. |
| `tests/Service/LinkManagerTest.php`, `tests/MessageHandler/FetchLinkPreviewHandlerTest.php` | Nouveau paramètre du constructeur / dépendances. |

## Hors scope

- **Normalisations supplémentaires** (ordre des paramètres, `m.` mobile, AMP) : liste fermée du pitch.

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : variantes `dark:`.
- **Traduction / i18n** : messages en français en dur.
- **API / exposition externe** : non.
- **Droits d'accès** : `access_control` `^/` → `ROLE_USER` couvre `/liens/{id}`.
- **Emails / notifications** : non.
- **Migration de données** : ajout de colonne NOT NULL avec défaut technique ; aucune reprise (table vide en production).
- **Comportement par défaut** : inchangé hors doublon.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `LinkUrlAnalyzer` | unit | https/http, casse du domaine, `www.`, barre finale, ancre, `utm_*`/`fbclid`/`si`, `youtu.be` → `youtube.com/watch?v=`, paramètre conservé, racine. |
| `LinkManager` | unit | doublon → exception, rien persisté. |
| Ajout en doublon + page de carte | functional | 422 + message + carte + lien vers `/liens/{id}` ; catégorie saisie absente ; `?page=2` accepté ; `/liens/{id}` 200 ; id inconnu 404. |

**Hors scope tests** :

- Pas d'E2E dédié : parcours purement serveur, couvert en fonctionnel.

## Ordre d'exécution

1. [x] **Normalisation et modèle**
   - Objectif : `LinkUrlAnalyzer`, champ, repository, migration.
   - Vérification : `doctrine:schema:validate`, `make lint`.
   - Commitable seule : oui.

2. [x] **Détection et écrans**
   - Objectif : exception, `LinkManager`, contrôleur, avertissement, page de carte.
   - Vérification : `make lint`, parcours manuel.
   - Commitable seule : oui.

3. [x] **Tests et QA finale**
   - Vérification : `make lint`, `make phpunit`, `make playwright`.
   - Commitable seule : oui.

## Critères de sortie

- [x] Index unique en base sur `normalized_url` ; migration générée, `down()` réversible.
- [x] Un doublon ne persiste ni lien ni catégorie.
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Course entre deux ajouts simultanés du même lien | faible | L'index unique rejette le second (erreur 500 ponctuelle, acceptée à 50 utilisateurs). |
| Normalisation trop agressive qui fusionne deux pages distinctes | faible | Liste fermée de paramètres retirés ; tests sur paramètre significatif. |
| Migration sur une base locale déjà peuplée | élevée | `make db-reset` ; base de test recréée avant `make phpunit`. |

## Questions ouvertes

_(aucune)_

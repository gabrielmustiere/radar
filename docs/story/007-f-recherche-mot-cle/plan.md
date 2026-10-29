# Plan technique — Retrouver un lien par mot

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/007-f-recherche-mot-cle/`
> **Amont** : `pitch.md`

## Approche retenue

`SearchController::index` (`GET /recherche?q=`, `app_search`) lit le paramètre par `#[MapQueryParameter]`, le découpe en mots via `SearchQuery::fromString()` (value object : mots de 2 caractères ou plus, 5 au plus, dédoublonnés sans tenir compte de la casse, casse d'origine conservée) et appelle `LinkRepository::search(SearchQuery)`. Le repository construit une condition par mot : `LIKE` échappé (`ESCAPE '!'`) sur `title`, `description`, `context`, `url`, ou `EXISTS` d'une catégorie du lien dont le nom correspond ; les conditions des mots sont combinées en ET. Les résultats sont chargés avec posteur et catégories (JOIN fetch) et triés par date d'origine.

Le formulaire de la topbar pointe vers `app_search` (`name="q"`) et réaffiche la saisie ; la page de résultats porte son propre champ (visible sur mobile).

### Mécanismes mobilisés

- **`#[MapQueryParameter]`** : lecture typée de `q`.
- **QueryBuilder paramétré + `ESCAPE`** : aucune concaténation de saisie utilisateur dans le DQL, jokers neutralisés.
- **Sous-requête `EXISTS`** sur les catégories : la condition de catégorie n'altère pas le JOIN fetch qui charge toutes les catégories de la carte.
- **`LIKE` SQLite** : insensible à la casse pour l'ASCII (règle 3 du pitch) ; ni `LOWER()` ni mise en minuscules de la saisie, qui empêcheraient une majuscule accentuée saisie de correspondre.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| SQLite FTS5 | Table virtuelle et synchronisation à maintenir ; volume (≤ 1 000 liens/an) trop faible pour en avoir besoin. |
| Moteur externe (Meilisearch…) | Infrastructure en plus pour un outil interne de 50 personnes. |
| Fonction SQLite d'insensibilité aux accents | Écartée au pitch ; ajoute une couche au driver. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Search/SearchQuery.php` | Mots exploitables d'une saisie (≥ 2 caractères, ≤ 5 mots, dédoublonnés, casse conservée). |
| `src/Controller/SearchController.php` | `GET /recherche`. |
| `templates/search/index.html.twig` | Champ de recherche, compteur, cartes, état « aucun résultat ». |
| `tests/Search/SearchQueryTest.php` | Découpage de la saisie. |
| `tests/Controller/SearchControllerTest.php` | Chaque champ cherché, ET entre mots, casse, aucun résultat, `%`, saisie réaffichée. |
| `tests/E2E/SearchTest.php` | Recherche depuis la topbar. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Repository/LinkRepository.php` | `search(SearchQuery)`. |
| `templates/base.html.twig` | Formulaire de la topbar vers `app_search`, saisie réaffichée. |

## Hors scope

- **Mise en évidence des mots dans les cartes** : non demandé.

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : variantes `dark:`.
- **Traduction / i18n** : français.
- **API / exposition externe** : non.
- **Droits d'accès** : `ROLE_USER` (access_control existant).
- **Emails / notifications** : non.
- **Migration de données** : aucune.
- **Comportement par défaut** : inchangé.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `SearchQuery` | unit | espaces multiples, mots courts ignorés, limite de 5, doublons sans tenir compte de la casse, saisie vide. |
| `SearchController` + `search()` | functional | trouvé par titre (aperçu simulé), description, contexte, adresse, catégorie ; deux mots en ET ; casse ; aucun résultat ; `%` ; saisie réaffichée. |
| Topbar | E2E | saisie + Entrée → résultats contenant le lien. |

**Hors scope tests** :

- Pas de test de performance : `LIKE` sur ≤ 1 000 lignes.

## Ordre d'exécution

1. [x] **Requête** — `SearchQuery`, `LinkRepository::search()`. Vérification : `make lint`. Commitable seule : oui.
2. [x] **Écran** — contrôleur, template, topbar. Vérification : `make quality`. Commitable seule : oui.
3. [x] **Tests et QA finale** — `make lint`, `make phpunit`, `make playwright`. Commitable seule : oui.

## Critères de sortie

- [x] Saisie utilisateur toujours passée en paramètre, jokers échappés.
- [x] Une requête pour les résultats (JOIN fetch, pas de N+1).
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Recherche accentuée infructueuse | moyenne | Limite assumée au pitch, à reconsidérer à l'usage. |
| `LIKE '%…%'` sans index | faible | Volume faible ; FTS5 possible plus tard. |

## Questions ouvertes

_(aucune)_

# Plan technique — Consulter le fil des derniers liens ajoutés

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/010-f-fil-derniers-ajouts/`
> **Amont** : `pitch.md`

## Approche retenue

Les trois listes (`findLatest`, `findByCategory`, `search`) de `LinkRepository` prennent un numéro de page et renvoient un `WindowPage<Link>` de Doctrine (liens de la page, numéro, total, pages précédente/suivante). Une méthode privée `paginate()` passe par `OffsetPaginator` (le `Paginator` historique est déprécié dans Doctrine ORM 3.7), indispensable ici : les requêtes chargent la collection `categories` en JOIN fetch, qu'un simple `LIMIT` tronquerait. Le tri des catégories d'une carte passe dans le mapping (`#[ORM\OrderBy(['name' => 'ASC'])]` sur `Link::$categories`) au lieu de chaque requête : un tri sur la collection jointe perturberait la sous-requête de pagination. Les contrôleurs lisent `page` par `#[MapQueryParameter]` (entier ≥ 1, sinon 404) et renvoient 404 au-delà de la dernière page.

Sur l'accueil, le filtre Twig `by_day` (`LinkExtension`) regroupe les liens de la page par jour d'origine à l'heure de Paris (Twig n'a pas de `group_by`) ; l'intitulé vient du filtre `day_label`, qui compare à l'horloge (`Aujourd'hui`, `Hier`) et formate sinon avec `IntlDateFormatter` en français (`EEEE d MMMM`, année ajoutée si différente). Un partiel `_pager.html.twig` affiche « Plus récents » / « Plus anciens » sur les trois pages.

### Mécanismes mobilisés

- **`Doctrine\ORM\Tools\Pagination\OffsetPaginator` + `WindowPage`** : pagination correcte avec JOIN fetch de collection, total et navigation inclus.
- **`#[MapQueryParameter]` + `FILTER_VALIDATE_INT` (`min_range` 1)** : page invalide → 404 sans code.
- **`IntlDateFormatter` (extension `intl` chargée)** : noms de jours et de mois en français sans dépendance Twig supplémentaire.
- **`ClockInterface`** dans l'extension Twig : intitulés testables.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| `twig/intl-extra` (`format_date`) | Dépendance ajoutée pour un seul format ; `IntlDateFormatter` est déjà disponible. |
| `LIMIT` + `perPage + 1` | Casse avec le JOIN fetch des catégories (lignes multipliées). |
| Bundle de pagination (KnpPaginator, Pagerfanta) | Trois listes simples ; le `Paginator` Doctrine suffit. |
| Regrouper par jour dans le contrôleur | Logique de présentation ; un filtre Twig la porte. |
| Value object maison `LinkPage` | `WindowPage` de Doctrine fournit déjà liens, total et navigation. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `templates/link/_pager.html.twig` | Navigation « Plus récents » / « Plus anciens ». |
| `tests/Twig/LinkExtensionTest.php` | `day_label` : aujourd'hui, hier, jour de l'année, autre année ; `by_day` : regroupement à l'heure de Paris. |
| `tests/Controller/FeedTest.php` | Regroupement par jour, total, pagination de catégorie (25 liens), page de recherche, 404. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Repository/LinkRepository.php` | `findLatest`, `findByCategory`, `search` paginés via `paginate()` (`OffsetPaginator`), `PER_PAGE = 24`. |
| `src/Entity/Link.php` | `#[ORM\OrderBy(['name' => 'ASC'])]` sur `categories`. |
| `src/Twig/LinkExtension.php` | Filtres `day_label` et `by_day` ; horloge injectée. |
| `src/Controller/PageController.php`, `CategoryController.php`, `SearchController.php` | Paramètre `page`, 404 au-delà de la dernière page. |
| `templates/page/index.html.twig` | Groupes par jour, total, pager. |
| `templates/category/show.html.twig`, `templates/search/index.html.twig` | Total, pager. |
| `tests/Controller/LinkWebTestCase.php`, `LinkControllerTest.php`, `LinkCardTest.php` | Sélecteur `FIRST_CARD` (première carte du premier jour) : `:first-child` visait la première carte de chaque jour. |

## Hors scope

- **Mise en cache des compteurs** : volume faible.

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : variantes `dark:`.
- **Traduction / i18n** : jours et mois via `IntlDateFormatter` en `fr_FR`, fuseau `Europe/Paris`.
- **API / exposition externe** : non.
- **Droits d'accès** : inchangé.
- **Emails / notifications** : non.
- **Migration de données** : aucune.
- **Comportement par défaut** : page 1 sans paramètre.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `LinkExtension` | unit | `dayLabel()` : Aujourd'hui, Hier, « Lundi 26 octobre », année affichée si différente ; `byDay()`. |
| Accueil | functional | lien ajouté sous « Aujourd'hui » en tête ; total affiché. |
| Catégorie de 25 liens | functional | page 1 : 24 cartes + « Plus anciens » ; page 2 : 1 carte + « Plus récents » ; page 3 : 404. |
| Recherche | functional | 25 résultats → pagination. |
| Paramètre invalide | functional | `?page=0` → 404. |

**Hors scope tests** :

- Pas de nouveau test E2E : les parcours existants traversent l'accueil et la catégorie.

## Ordre d'exécution

1. [x] **Pagination des requêtes** — `paginate()`, trois méthodes. Vérification : `make lint`. Commitable seule : non (contrôleurs à adapter).
2. [x] **Contrôleurs et pager** — paramètre `page`, 404, partiel, trois templates. Vérification : `make quality`. Commitable seule : oui.
3. [x] **Fil par jour** — filtres `day_label` et `by_day`, groupes sur l'accueil. Vérification : `make quality`. Commitable seule : oui.
4. [x] **Tests et QA finale** — `make lint`, `make phpunit`, `make playwright`. Commitable seule : oui.

## Critères de sortie

- [x] Pagination correcte malgré le JOIN fetch (cartes complètes sur chaque page).
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Groupe de jour coupé entre deux pages | moyenne | Acceptable : l'intitulé est répété en tête de la page suivante. |
| Deux requêtes par page (Paginator) | faible | Volume faible. |

## Questions ouvertes

_(aucune)_

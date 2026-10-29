# Plan technique — Parcourir les liens d'une catégorie-besoin

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/006-f-exploration-categories/`
> **Amont** : `pitch.md`

## Approche retenue

Un Twig Component `CategoryNav` (classe `App\Twig\Components\CategoryNav`) rend la section « Catégories » de la barre latérale sur toutes les pages : il reçoit `LinkRepository::countByCategory()` (une requête groupée : id, nom, nombre de liens) et l'id de la catégorie courante pour la mise en évidence. Un `CategoryController::show` (`GET /categories/{id}`, `app_category_show`) résout la catégorie par l'`EntityValueResolver` (404 natif) et affiche `LinkRepository::findByCategory()` : un JOIN de filtrage et un second JOIN qui charge toutes les catégories de chaque lien, pour garder des cartes complètes sans N+1.

La carte devient actionnable : titre et visuel pointent vers l'URL d'origine (`target="_blank" rel="noopener noreferrer"`), un bouton « Ouvrir » s'ajoute au pied, et chaque pastille de catégorie mène à sa page.

### Mécanismes mobilisés

- **Twig Component (`#[AsTwigComponent]`)** : données de la barre latérale chargées par le composant, sans les passer depuis chaque contrôleur.
- **DQL groupé dans le repository** : un seul `COUNT … GROUP BY` pour les compteurs.
- **Double JOIN (filtre + fetch)** : liens d'une catégorie avec toutes leurs catégories en une requête.
- **`EntityValueResolver`** : 404 sur catégorie inconnue.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Variable globale Twig / `render(controller())` | Un sous-requête par page pour `render()` ; le composant est le mécanisme UX déjà installé. |
| Association inverse `Category::$links` | Inutile pour compter (requête côté `Link`) ; garde `Link` propriétaire unique. |
| Slug dans l'URL de catégorie | Le renommage (V2) casserait les URLs ; l'id est stable. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Twig/Components/CategoryNav.php` | Composant : catégories + compteurs, catégorie courante. |
| `templates/components/CategoryNav.html.twig` | Section « Catégories » de la barre latérale. |
| `src/Controller/CategoryController.php` | `GET /categories/{id}`. |
| `templates/category/show.html.twig` | Page d'une catégorie (grille de cartes). |
| `tests/Controller/CategoryControllerTest.php` | Navigation, filtrage, ordre, mise en évidence, 404. |
| `tests/E2E/ExploreCategoryTest.php` | Parcours barre latérale → catégorie → ouverture d'un lien (nouvel onglet). |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Repository/LinkRepository.php` | `countByCategory()`, `findByCategory()`. |
| `templates/base.html.twig` | Inclusion de `<twig:CategoryNav>` dans la barre latérale. |
| `templates/link/_card.html.twig` | Titre, visuel et bouton « Ouvrir » vers l'URL d'origine ; pastilles vers la catégorie. |

## Hors scope

- **Pagination de la page de catégorie** : traitée avec le fil (`fil-derniers-ajouts`).

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : variantes `dark:`.
- **Traduction / i18n** : français.
- **API / exposition externe** : `rel="noopener noreferrer"` sur les liens sortants.
- **Droits d'accès** : `ROLE_USER` (access_control existant).
- **Emails / notifications** : non.
- **Migration de données** : aucune.
- **Comportement par défaut** : inchangé.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `CategoryController` + `findByCategory()` + `CategoryNav` | functional | compteurs dans la barre latérale ; page n'affiche que les liens de la catégorie, plus récent d'abord ; lien bi-catégorie présent dans les deux avec ses deux pastilles ; catégorie courante marquée ; 404. |
| Carte actionnable | functional | titre et bouton « Ouvrir » vers l'URL, `target=_blank`, pastille vers `/categories/{id}`. |
| Parcours complet | E2E | clic catégorie dans la barre latérale → page → « Ouvrir » ouvre un nouvel onglet sur l'URL d'origine. |

**Hors scope tests** :

- Pas de test unitaire du composant : sa seule logique est l'appel au repository, couvert en fonctionnel.

## Ordre d'exécution

1. [x] **Requêtes** — `countByCategory()`, `findByCategory()`. Vérification : `make lint`. Commitable seule : oui.
2. [x] **Barre latérale et page** — composant, contrôleur, templates. Vérification : `make quality`. Commitable seule : oui.
3. [x] **Carte actionnable** — liens sortants et pastilles. Vérification : `make quality`. Commitable seule : oui.
4. [x] **Tests et QA finale** — `make lint`, `make phpunit`, `make playwright`. Commitable seule : oui.

## Critères de sortie

- [x] Une requête pour la barre latérale, une requête pour la liste d'une catégorie (pas de N+1).
- [x] Tous les liens sortants portent `rel="noopener noreferrer"`.
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Requête de la barre latérale sur chaque page | faible | Une requête groupée sur quelques dizaines de catégories. |
| Pages de catégorie longues sans pagination | moyenne | Pagination prévue avec le fil (`fil-derniers-ajouts`). |

## Questions ouvertes

_(aucune)_

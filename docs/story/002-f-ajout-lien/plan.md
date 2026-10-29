# Plan technique — Ajouter un lien en moins de 15 secondes

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/002-f-ajout-lien/`
> **Amont** : `pitch.md`

## Approche retenue

Deux entités Doctrine, `Link` et `Category`, reliées en ManyToMany (côté propriétaire `Link`). Le formulaire d'ajout est un FormType lié à un DTO `LinkSubmission` (pas à l'entité) : il porte l'URL, les catégories existantes cochées, le nom d'une catégorie à créer et le contexte, et c'est lui qui valide la règle « une ou deux catégories au total » par une contrainte `Callback`. Le contrôleur `LinkController` délègue la création à `LinkManager::add()`, qui résout la catégorie saisie (réutilisée si son nom existe déjà, comparaison insensible à la casse faite en PHP), crée le `Link` avec le membre connecté comme posteur et l'heure de l'horloge comme date d'origine, puis persiste.

« Tous les liens » (`PageController::index`) affiche les liens du plus récent au plus ancien via `LinkRepository::findLatest()`, qui charge posteur et catégories en une requête (JOIN fetch). La locale par défaut passe à `fr` pour que les messages de validation Symfony sortent en français (R11).

### Mécanismes mobilisés

- **Doctrine ORM (attributs) + `make:migration`** : deux tables et une table de jointure, migration générée.
- **Form + DTO + contraintes Validator** : le DTO isole le formulaire du modèle (pas de mass-assignment) ; la règle de cardinalité, transverse à deux champs, tient dans un `Callback`.
- **`EntityType` (multiple, expanded)** : cases à cocher des catégories existantes, triées par nom via le repository.
- **`ClockInterface`** : date d'origine testable sans `new \DateTimeImmutable()` dispersé.
- **`#[CurrentUser]`** : posteur résolu par le contrôleur, passé au service.
- **Thème de formulaire Flowbite** (déjà configuré dans `config/packages/twig.yaml`) : rendu par `form_row`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Formulaire lié directement à l'entité `Link` | La catégorie à créer et la règle de cardinalité ne sont pas des propriétés du lien ; un DTO évite de polluer l'entité. |
| Modale Live Component en deux temps | Plus de JS et d'états pour un gain nul sur les 15 secondes ; écartée au pitch. |
| Comparaison des noms de catégorie en SQL (`LOWER()`) | `LOWER()` de SQLite ignore les caractères accentués (« Écrire » ≠ « écrire ») ; le volume (dizaines de catégories) permet la comparaison en PHP. |
| Colonne `display_name` sur `User` | Aucun écran de profil au MVP ; la partie locale de l'email suffit (tranché au pitch). |

## Modèle de données

### Nouvelle entité `Category`

`src/Entity/Category.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | integer, auto | non | |
| `name` | string(60) | non | unique (`UNIQ_CATEGORY_NAME`), `NotBlank`, `Length(2..60)` |
| `createdAt` | datetime_immutable (`created_at`) | non | |

### Nouvelle entité `Link`

`src/Entity/Link.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | integer, auto | non | |
| `url` | string(2048) | non | |
| `context` | string(500) | oui | |
| `postedBy` | ManyToOne (`User`), `posted_by_id` | non | `onDelete: RESTRICT` (un posteur ne disparaît pas sans décision) |
| `postedAt` | datetime_immutable (`posted_at`) | non | index `IDX_LINK_POSTED_AT` (tri du fil) |
| `categories` | ManyToMany (`Category`), table `link_category` | non | 1 ou 2 à la création (validé dans le DTO) |

Pas de cloisonnement : tout est partagé (R8). Les contraintes de validation de l'ajout vivent dans le DTO `LinkSubmission`, pas sur l'entité.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Entity/Category.php` | Catégorie-besoin (nom unique). |
| `src/Entity/Link.php` | Lien partagé : URL, contexte, posteur, date d'origine, catégories. |
| `src/Repository/CategoryRepository.php` | `findAllOrderedByName()`. |
| `src/Repository/LinkRepository.php` | `findLatest()` avec JOIN fetch posteur + catégories. |
| `src/Form/Model/LinkSubmission.php` | DTO du formulaire + contraintes, dont le `Callback` « 1 ou 2 catégories » (une catégorie saisie identique à une cochée ne compte qu'une fois). |
| `src/Form/LinkType.php` | FormType : `url`, `categories`, `newCategory`, `context`. |
| `src/Service/LinkManager.php` | `add(LinkSubmission, User): Link` — résout/crée la catégorie, crée et persiste le lien. |
| `src/Controller/LinkController.php` | `GET|POST /liens/ajouter` (`app_link_new`). |
| `templates/link/new.html.twig` | Écran d'ajout. |
| `templates/link/_item.html.twig` | Ligne d'un lien dans une liste (adresse cliquable dans un nouvel onglet, posteur, date `jj/mm/aaaa`, catégories, contexte). |
| `migrations/VersionXXXX.php` | Tables `category`, `link`, `link_category` (générée). |
| `tests/Service/LinkManagerTest.php` | Résolution de catégorie (existante insensible à la casse / nouvelle), posteur et date. |
| `tests/Controller/LinkControllerTest.php` | Parcours d'ajout et cas d'erreur. |
| `tests/E2E/AddLinkTest.php` | Ajout d'un lien de bout en bout dans le navigateur. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Entity/User.php` | Ajouter `getDisplayName()` (partie locale de l'email). |
| `src/Controller/PageController.php` | `index` passe `LinkRepository::findLatest()` au template. |
| `templates/page/index.html.twig` | Liste des liens (compteur réel) ; état vide conservé quand il n'y en a aucun. |
| `templates/base.html.twig` | Bouton « Ajouter un lien » dans la topbar (`data-test="add-link"`). |
| `config/packages/translation.yaml` | `default_locale: fr`. |
| `fixtures/AppFixtures.php` | Quelques catégories-besoins de démonstration. |

## Hors scope

- **Aperçu, doublons, annonce, amorçage** : stories suivantes (voir pitch).
- **Pagination de « Tous les liens »** : story `fil-derniers-ajouts`.
- **Externalisation des libellés en fichiers de traduction** : les templates existants écrivent le français en dur ; on garde ce style.

## Impacts transverses

- **Cloisonnement des données** : aucun — pas de filtrage par organisation.
- **Déclinaisons / thèmes** : non ; variantes `dark:` sur les nouveaux écrans.
- **Traduction / i18n** : `default_locale: fr` ; libellés en dur en français comme le reste des templates.
- **API / exposition externe** : non.
- **Droits d'accès** : `access_control` existant (`^/` → `ROLE_USER`) suffit ; aucun voter (R2).
- **Emails / notifications** : non.
- **Migration de données** : création de structures uniquement, aucune donnée existante.
- **Comportement par défaut** : état vide inchangé tant qu'il n'y a aucun lien.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/LinkManager.php` | unit | catégorie existante réutilisée malgré la casse/les espaces ; catégorie créée sinon ; posteur et date de l'horloge posés ; contexte vide → `null`. |
| `src/Controller/LinkController.php` | functional | anonyme redirigé ; ajout nominal (catégorie existante) visible en tête de `/` ; création de catégorie ; catégorie cochée + catégorie créée conservées ensemble ; réutilisation insensible à la casse ; nom d'une catégorie cochée ressaisi non compté deux fois ; 0 catégorie, 3 catégories, URL invalide refusés en 422 avec message français. |
| `templates/link/new.html.twig` + `templates/base.html.twig` | E2E | depuis `/`, clic « Ajouter un lien », saisie, validation, lien visible en tête. |

**Hors scope tests** :

- Pas de test unitaire du `Callback` du DTO : couvert par les tests fonctionnels 0 / 3 catégories.
- Pas de test du repository isolé : `findLatest()` est traversé par le test fonctionnel nominal.

## Ordre d'exécution

1. [x] **Modèle et migration**
   - Objectif : entités `Category`, `Link`, repositories, `User::getDisplayName()`.
   - Fichiers : entités, repositories, migration générée.
   - Vérification : `symfony console doctrine:schema:validate`, `make lint`.
   - Commitable seule : oui.

2. [x] **Formulaire et service**
   - Objectif : DTO + contraintes, `LinkType`, `LinkManager`, locale `fr`.
   - Fichiers : `src/Form/*`, `src/Service/LinkManager.php`, `config/packages/translation.yaml`.
   - Vérification : `make lint`.
   - Commitable seule : oui.

3. [x] **Contrôleur et écrans**
   - Objectif : `/liens/ajouter`, bouton topbar, liste sur `/`, fixtures de catégories.
   - Fichiers : `LinkController`, `PageController`, templates, fixtures.
   - Vérification : `make quality`, parcours manuel.
   - Commitable seule : oui.

4. [x] **Tests et QA finale**
   - Objectif : tests unit, functional, E2E ; suite complète verte.
   - Fichiers : `tests/Service/LinkManagerTest.php`, `tests/Controller/LinkControllerTest.php`, `tests/E2E/AddLinkTest.php`.
   - Vérification : `make lint`, `make phpunit`, `make playwright`.
   - Commitable seule : oui.

## Critères de sortie

- [x] `doctrine:schema:validate` passe ; migration générée par `make:migration`, `down()` réversible.
- [x] `make lint` : zéro finding Mago.
- [x] `make phpunit` et `make playwright` verts, sans régression.
- [x] Aucune requête Doctrine hors `src/Repository/` ; aucune logique métier dans le contrôleur.
- [x] « Tous les liens » charge posteur et catégories sans requête par ligne (JOIN fetch).

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Course entre deux créations simultanées d'une même catégorie → violation d'unicité | faible | Index unique en base ; à 50 utilisateurs, l'erreur 500 ponctuelle est acceptable au MVP. |
| Passage de la locale à `fr` change des libellés existants (erreurs de connexion) | faible | Les tests existants vérifient la présence de l'alerte, pas son texte ; message français souhaité (R11). |
| Les E2E écrivent dans la base de dev | moyenne | URLs et noms de catégorie uniques par exécution. |

## Questions ouvertes

- **Suppression d'un membre posteur** : `RESTRICT` empêche de supprimer un compte qui a posté. Recommandé : garder, aucune suppression de compte n'existe au MVP.

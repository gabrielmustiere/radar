# Plan technique — Reprendre un lien de l'historique Chat pour amorcer Radar

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/005-f-amorcage-historique/`
> **Amont** : `pitch.md`

## Approche retenue

Le formulaire de reprise étend celui de l'ajout : `HistoricalLinkType` a `LinkType` pour parent (`getParent()`) et ajoute `postedAt` (date, obligatoire, pas dans le futur) et `poster` (membre, obligatoire). Son DTO `HistoricalLinkSubmission` hérite de `LinkSubmission` (qui cesse d'être `final`). `LinkManager` factorise la création dans une méthode privée ; `add()` l'appelle avec le membre connecté et l'heure courante, `seed()` avec le posteur et la date d'origine et le marqueur `seeded`. Le contrôle de doublon et la résolution de catégorie sont donc communs.

`Link` gagne un booléen `seeded` (défaut `false`), lu par la future story d'annonce (R5) et par la carte pour la mention « repris de Chat ». Une route `GET|POST /liens/reprendre` (`app_link_seed`) réutilise la gestion du doublon de l'ajout.

### Mécanismes mobilisés

- **Héritage de FormType (`getParent()`)** : les champs de l'ajout ne sont pas dupliqués.
- **Contraintes `NotNull` et `LessThanOrEqual('today')`** sur le DTO.
- **`EntityType` sur `User`**, choix triés par `UserRepository::findAllOrderedByEmail()`.
- **Méthode privée de création dans `LinkManager`** : un seul chemin pour doublon, catégories, persistance et aperçu.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Champs date/posteur facultatifs sur l'ajout normal | Ajoute du bruit au formulaire des 15 secondes (principe 1). |
| Déduire « repris » d'une date d'origine passée | Fragile (un ajout normal à minuit) ; l'annonce a besoin d'un marqueur explicite. |
| Nom libre pour le posteur d'origine | Écarté au pitch (R3, R12). |

## Modèle de données

### Modification de `Link`

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `seeded` | boolean | non | défaut `false` |

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Form/Model/HistoricalLinkSubmission.php` | DTO : date et posteur d'origine en plus de l'ajout. |
| `src/Form/HistoricalLinkType.php` | Formulaire de reprise (parent `LinkType`). |
| `templates/link/seed.html.twig` | Écran « Reprendre un lien de Chat ». |
| `templates/link/_fields.html.twig` | Champs communs à l'ajout et à la reprise (URL, catégories, nouvelle catégorie, contexte). |
| `templates/link/_duplicate.html.twig` | Avertissement de doublon avec la carte existante, commun aux deux écrans. |
| `migrations/VersionXXXX.php` | Colonne `seeded` (générée). |
| `tests/Controller/LinkSeedTest.php` | Reprise nominale, date future, date absente, doublon, accès depuis l'ajout. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Entity/Link.php` | Champ `seeded`. |
| `src/Form/Model/LinkSubmission.php` | Plus `final`. |
| `src/Repository/UserRepository.php` | `findAllOrderedByEmail()`. |
| `src/Service/LinkManager.php` | `seed()` + création factorisée. |
| `src/Controller/LinkController.php` | Action `seed`. |
| `templates/link/new.html.twig` | Lien vers la reprise ; champs et avertissement de doublon extraits en partiels. |
| `templates/link/_card.html.twig` | Mention « repris de Chat ». |
| `fixtures/AppFixtures.php` | Deux membres supplémentaires (posteurs d'origine possibles). |
| `tests/Service/LinkManagerTest.php` | `seed()` pose posteur, date et marqueur. |
| `tests/Controller/LinkWebTestCase.php` | `submit()` paramétré par route et nom de formulaire. |

## Hors scope

- **Mutualiser les écrans `new` et `seed` en un seul template** : seuls les champs communs et l'avertissement de doublon sont partagés en partiels ; chaque écran garde son en-tête et ses actions.

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : variantes `dark:`.
- **Traduction / i18n** : français ; messages de contraintes explicites.
- **API / exposition externe** : non.
- **Droits d'accès** : `ROLE_USER` (access_control existant).
- **Emails / notifications** : aucune.
- **Migration de données** : colonne booléenne avec défaut, aucune reprise.
- **Comportement par défaut** : ajout normal inchangé (`seeded = false`).

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `LinkManager::seed()` | unit | posteur et date d'origine posés, `seeded` vrai, aperçu dispatché. |
| `/liens/reprendre` | functional | reprise nominale (posteur, date, mention) ; date future 422 ; date absente 422 ; doublon 422 avec carte ; lien depuis l'ajout. |

**Hors scope tests** :

- Pas d'E2E dédié : formulaire serveur, couvert en fonctionnel.

## Ordre d'exécution

1. [x] **Modèle** — champ `seeded`, migration. Vérification : `doctrine:schema:validate`, `make lint`. Commitable seule : oui.
2. [x] **Formulaire et service** — DTO, type, `seed()`, `findAllOrderedByEmail()`. Vérification : `make lint`. Commitable seule : oui.
3. [x] **Écran** — route, template, lien depuis l'ajout, mention sur la carte, fixtures. Vérification : `make quality`. Commitable seule : oui.
4. [x] **Tests et QA finale** — `make lint`, `make phpunit`, `make playwright`. Commitable seule : oui.

## Critères de sortie

- [x] Migration générée, `doctrine:schema:validate` vert.
- [x] Un seul chemin de création dans `LinkManager` (doublon et catégories communs).
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Date d'origine saisie au jour près, affichée à minuit | faible | Seule la date est affichée sur la carte. |
| Posteur d'origine sans compte | moyenne | Créer les comptes avant l'amorçage (question ouverte du pitch). |

## Questions ouvertes

_(aucune)_

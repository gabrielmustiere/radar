# Plan technique — Compter chaque ouverture de lien avec son âge

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/009-f-mesure-clics-sortants/`
> **Amont** : `pitch.md`

## Approche retenue

Les liens sortants de la carte pointent vers une route interne `GET /liens/{id}/ouvrir` (`app_link_open`). Le contrôleur résout le lien (`EntityValueResolver`), délègue à `LinkOpeningRecorder::record(Link, User)` — qui calcule l'âge en jours révolus entre `postedAt` et l'horloge, crée une `LinkOpening` et la persiste par son repository — puis répond par une redirection 302 vers `Link::getUrl()`. La destination ne vient que de la base (URL validée http/https à l'ajout) : aucune redirection ouverte.

L'âge est stocké (`link_age_days`) plutôt que recalculé : la North Star devient un simple `COUNT` sur les ouvertures d'un mois avec `link_age_days > 30`, insensible aux futures modifications de date. Le bouton « Ouvrir » porte l'URL d'origine en `title`.

### Mécanismes mobilisés

- **Route de redirection + `RedirectResponse`** : mesure côté serveur, aucun JavaScript.
- **Entité `LinkOpening` + repository `save()`** : accès aux données dans le repository.
- **`ClockInterface`** : âge testable.
- **`access_control` existant** : la route exige `ROLE_USER`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| `navigator.sendBeacon` en JavaScript | Perdu si le JS échoue ou est bloqué ; une redirection serveur ne rate aucun clic. |
| Compteur agrégé sur `Link` | Perd le moment et le membre, nécessaires aux indicateurs mensuels (C6.2). |
| Recalculer l'âge à la lecture | Requête plus lourde, et l'âge dépendrait d'une date éditable (V2). |

## Modèle de données

### Nouvelle entité `LinkOpening`

`src/Entity/LinkOpening.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | integer, auto | non | |
| `link` | ManyToOne (`Link`), `link_id` | non | `onDelete: CASCADE` |
| `openedBy` | ManyToOne (`User`), `opened_by_id` | non | `onDelete: CASCADE` |
| `openedAt` | datetime_immutable (`opened_at`) | non | index `IDX_LINK_OPENING_OPENED_AT` |
| `linkAgeDays` | integer (`link_age_days`) | non | ≥ 0 |

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Entity/LinkOpening.php` | Ouverture d'un lien d'origine. |
| `src/Repository/LinkOpeningRepository.php` | `save()`. |
| `src/Service/LinkOpeningRecorder.php` | Âge en jours révolus, création de l'ouverture. |
| `migrations/VersionXXXX.php` | Table `link_opening` (générée). |
| `tests/Service/LinkOpeningRecorderTest.php` | Âge 0, âge 45, date d'origine future bornée à 0. |
| `tests/Controller/LinkOpenTest.php` | Redirection, enregistrement, lien repris ancien, anonyme, 404. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Controller/LinkController.php` | Action `open`. |
| `templates/link/_card.html.twig` | Liens sortants vers `app_link_open`, `title` avec l'URL d'origine. |
| `tests/Controller/CategoryControllerTest.php` | Attendus de `href` mis à jour. |
| `tests/E2E/ExploreCategoryTest.php` | La fenêtre ouverte arrive sur la source après la redirection. |

## Hors scope

- **Requête North Star** : livrée avec les indicateurs mensuels (V2).

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : non.
- **Traduction / i18n** : infobulle en français.
- **API / exposition externe** : la redirection garde `rel="noopener noreferrer"` sur le lien de la carte.
- **Droits d'accès** : `ROLE_USER` via `access_control`.
- **Emails / notifications** : non.
- **Migration de données** : création de table.
- **Comportement par défaut** : mesure active.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `LinkOpeningRecorder` | unit | âge 0 le jour même, 45 jours, jamais négatif ; membre et moment posés. |
| `/liens/{id}/ouvrir` | functional | 302 vers l'URL d'origine ; une ouverture par appel ; lien repris de plus de 30 jours ; anonyme → `/login` sans enregistrement ; 404. |
| Carte | functional | les liens sortants pointent vers `app_link_open`, `title` = URL d'origine. |
| Parcours | E2E | clic « Ouvrir » → nouvel onglet sur la source. |

**Hors scope tests** :

- Pas de test de charge : une insertion par clic.

## Ordre d'exécution

1. [x] **Modèle** — entité, repository, migration. Vérification : `doctrine:schema:validate`, `make lint`. Commitable seule : oui.
2. [x] **Enregistrement et redirection** — service, action, carte. Vérification : `make quality`. Commitable seule : oui.
3. [x] **Tests et QA finale** — `make lint`, `make phpunit`, `make playwright`. Commitable seule : oui.

## Critères de sortie

- [x] La redirection ne vise que l'URL enregistrée du lien (pas de paramètre de destination).
- [x] Migration générée, `doctrine:schema:validate` vert.
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Préchargement de liens par le navigateur qui fausse la mesure | faible | `turbo-prefetch=false` déjà posé dans `base.html.twig`. |
| Croissance de la table | faible | Quelques centaines de lignes par mois. |

## Questions ouvertes

_(aucune)_

# Plan technique — Juger un lien sans cliquer grâce à sa carte

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/003-f-carte-apercu/`
> **Amont** : `pitch.md`

## Approche retenue

À la création, `LinkManager` déduit le type de source de l'URL (`SourceTypeResolver`, enum `SourceType`) et pose un statut d'aperçu `pending` (enum `PreviewStatus`), puis dispatche un message Messenger `FetchLinkPreview(linkId)` routé sur le transport `async` existant. Son handler appelle `LinkPreviewFetcher`, qui télécharge la page (client HTTP qui refuse les réseaux privés, délais courts, 1 Mo maximum) et délègue la lecture des balises OpenGraph / Twitter / `<title>` / `meta description` à `LinkPreviewParser` (`Dom\HTMLDocument`). Le handler enregistre l'aperçu sur le lien (`ready`) ou le marque `unavailable`. Les liens X ne sont jamais interrogés : `unavailable` d'emblée. Une adresse servie en `image/*` a pour aperçu l'image elle-même.

Côté écran, `templates/link/_card.html.twig` remplace la ligne de liste : visuel (image d'aperçu ou visuel de repli par type de source), type de source + domaine, titre (ou adresse en repli), description, contexte, posteur, date, catégories. Un petit contrôleur Stimulus `image-fallback` remplace une image qui ne charge pas par le visuel de repli. Le domaine s'obtient par un filtre Twig `link_host` (`LinkExtension`) ; libellé et icône du type de source sont des méthodes de l'enum.

### Mécanismes mobilisés

- **Messenger (transport Doctrine `async`, `sync://` en test)** : l'ajout ne bloque pas sur une source lente ; le worker tourne déjà en dev.
- **HttpClient + `NoPrivateNetworkHttpClient`** (service dédié dans `config/services.yaml`) : règle « jamais le réseau interne » sans code maison.
- **`MockHttpClient` substitué au client d'aperçu en test** (`when@test` de `config/services.yaml`) : les tests fonctionnels n'appellent jamais les sources, sans toucher au client global dont UX Icons a besoin.
- **`Dom\HTMLDocument` (PHP 8.4+)** : parseur HTML5 natif, aucune dépendance ajoutée.
- **Backed enums `src/Enum/Type/`** (convention projet) avec `label()` / `icon()`.
- **Extension Twig** : filtre `link_host` pour le domaine sans `www.`.
- **Stimulus** : repli d'image côté navigateur.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Récupérer l'aperçu pendant l'ajout | Une source lente ferait dépasser les 15 secondes (pitch). |
| Bibliothèque d'embed (`embed/embed`) | Dépendance lourde pour lire une dizaine de balises ; le parseur natif suffit. |
| Héberger les images d'aperçu | Stockage et purge à gérer ; hors scope du pitch. |
| Calculer le type de source à l'affichage | Le stocker permet le filtre par type (V2 `filtre-type-source`) sans recalcul. |

## Modèle de données

### Modification de `Link`

`src/Entity/Link.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `sourceType` | string enum `SourceType` (`source_type`, 20) | non | défaut `article` |
| `previewStatus` | string enum `PreviewStatus` (`preview_status`, 20) | non | défaut `pending` |
| `title` | string(255) | oui | tronqué à 255 |
| `description` | string(500) | oui | tronquée à 500 |
| `imageUrl` | string(2048) (`image_url`) | oui | URL absolue http(s) |
| `siteName` | string(255) (`site_name`) | oui | |

Les défauts en base rendent la migration sûre sur une table non vide (les liens existants passent en `pending` / `article`).

### Nouveaux enums

- `src/Enum/Type/SourceType.php` : `github`, `youtube`, `x`, `image`, `article`.
- `src/Enum/Type/PreviewStatus.php` : `pending`, `ready`, `unavailable`.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Enum/Type/SourceType.php` | Types de source, libellé et icône. |
| `src/Enum/Type/PreviewStatus.php` | Statut de l'aperçu. |
| `src/Service/Preview/SourceTypeResolver.php` | URL → `SourceType`. |
| `src/Service/Preview/LinkPreview.php` | Value object de l'aperçu (titre, description, image, site). |
| `src/Service/Preview/HtmlMetadata.php` | Lecture des balises `<meta>` et `<title>` d'une page (`Dom\HTMLDocument`), valeurs nettoyées. |
| `src/Service/Preview/LinkPreviewParser.php` | `HtmlMetadata` + URL de base → `LinkPreview` (OpenGraph, Twitter, `<title>`, `meta description`, URL d'image résolue, troncatures). |
| `src/Service/Preview/LinkPreviewFetcher.php` | Téléchargement borné, image directe, `null` en cas d'échec. |
| `src/Message/FetchLinkPreview.php` | Message `linkId`. |
| `src/MessageHandler/FetchLinkPreviewHandler.php` | Charge le lien, récupère et enregistre l'aperçu. |
| `src/Twig/LinkExtension.php` | Filtre `link_host`. |
| `assets/controllers/image_fallback_controller.js` | Remplace une image en erreur par le visuel de repli. |
| `templates/link/_card.html.twig` | Carte de lien. |
| `migrations/VersionXXXX.php` | Colonnes d'aperçu sur `link` (générée). |
| `tests/Http/PreviewMockResponseFactory.php` | Réponses HTTP simulées en test. |
| `tests/Service/Preview/SourceTypeResolverTest.php` | Table URL → type. |
| `tests/Service/Preview/LinkPreviewParserTest.php` | OpenGraph, repli `<title>`/description, image relative, page vide. |
| `tests/Service/Preview/LinkPreviewFetcherTest.php` | HTML, image directe, 404, erreur réseau, non-HTML. |
| `tests/MessageHandler/FetchLinkPreviewHandlerTest.php` | `ready` avec aperçu, `unavailable` sans aperçu, X jamais interrogé, lien supprimé ignoré. |
| `tests/Controller/LinkWebTestCase.php` | Base commune des tests d'ajout (membre connecté, soumission, catégories des fixtures). |
| `tests/Controller/LinkCardTest.php` | Rendu de la carte : aperçu simulé, carte de repli. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Entity/Link.php` | Champs d'aperçu et de type de source, `applyPreview()` / `markPreviewUnavailable()`. |
| `src/Service/LinkManager.php` | Pose le type de source, dispatche `FetchLinkPreview` après le flush. |
| `config/packages/messenger.yaml` | Route `FetchLinkPreview` vers `async`. |
| `config/services.yaml` | Client HTTP sans réseau privé (`app.preview_http_client`) pour `LinkPreviewFetcher` ; en test, ce même service devient un `MockHttpClient` alimenté par `PreviewMockResponseFactory`. |
| `mago.toml` | `too-many-properties` et `excessive-parameter-list` exclus de `src/Entity/` (entités déclaratives, même raison que la complexité). |
| `templates/page/index.html.twig` | Grille de cartes à la place de la liste. |
| `templates/link/_item.html.twig` | Supprimé (remplacé par `_card`). |
| `fixtures/AppFixtures.php` | Quelques liens de démonstration (aperçu prêt et repli). |
| `tests/Controller/LinkControllerTest.php` | Sélecteurs de carte ; helpers déplacés dans `LinkWebTestCase` (seuil `too-many-methods`). |
| `tests/E2E/AddLinkTest.php` | Sélecteurs de carte. |
| `tests/Service/LinkManagerTest.php` | Dispatch du message, type de source. |

## Hors scope

- **Retry de l'aperçu** : un échec passe en `unavailable` sans nouvelle tentative (relance manuelle en V2).
- **Cache HTTP des pages sources** : un lien n'est interrogé qu'une fois.

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : variantes `dark:` sur la carte.
- **Traduction / i18n** : libellés français dans l'enum.
- **API / exposition externe** : requêtes sortantes vers les sources ; `User-Agent` explicite `RadarBot`.
- **Droits d'accès** : inchangé.
- **Emails / notifications** : non.
- **Migration de données** : colonnes ajoutées avec défauts ; pas de reprise des liens existants (aucun en production).
- **Comportement par défaut** : carte de repli tant que l'aperçu n'est pas prêt.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `SourceTypeResolver` | unit | github.com/owner/repo, youtube.com/watch, youtu.be, x.com, twitter.com, `.png`, article. |
| `LinkPreviewParser` | unit | OpenGraph complet ; repli `<title>` + `meta description` ; image relative résolue ; page sans métadonnées → aperçu vide. |
| `LinkPreviewFetcher` | unit (`MockHttpClient`) | HTML → aperçu ; `image/png` → image directe ; 404 et erreur réseau → `null` ; PDF → `null`. |
| `FetchLinkPreviewHandler` | unit | aperçu → `ready` ; `null` → `unavailable` ; lien X → `unavailable` sans appel ; lien supprimé → rien. |
| `LinkCardTest` | functional | après ajout, la carte montre le titre simulé, le type de source et la catégorie ; source en échec → carte de repli. |
| Carte dans le navigateur | E2E | la carte du lien ajouté s'affiche avec son contexte. |

**Hors scope tests** :

- Pas de test du refus réseau privé : comportement de `NoPrivateNetworkHttpClient` (Symfony), seul le câblage est de notre ressort (vérifié par `debug:container`).
- Pas de test JS du contrôleur `image-fallback` : comportement visuel, vérifié manuellement.

## Ordre d'exécution

1. [x] **Modèle et enums**
   - Objectif : enums, champs sur `Link`, migration générée.
   - Vérification : `doctrine:schema:validate`, `make lint`.
   - Commitable seule : oui.

2. [x] **Construction de l'aperçu**
   - Objectif : resolver, parser, fetcher, message + handler, câblage HTTP et Messenger.
   - Vérification : `make lint`, tests unitaires.
   - Commitable seule : oui.

3. [x] **Carte**
   - Objectif : `_card`, grille, extension Twig, Stimulus, fixtures.
   - Vérification : `make quality`, rendu clair/sombre.
   - Commitable seule : oui.

4. [x] **Tests et QA finale**
   - Vérification : `make lint`, `make phpunit`, `make playwright`.
   - Commitable seule : oui.

## Critères de sortie

- [x] Migration générée, `down()` réversible, `doctrine:schema:validate` vert.
- [x] Aucun appel réseau réel dans `make phpunit`.
- [x] `LinkPreviewFetcher` reçoit un client `NoPrivateNetworkHttpClient` hors test.
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Page source énorme ou lente | moyenne | `timeout` 5 s, `max_duration` 10 s, téléchargement interrompu au-delà de 1 Mo. |
| Encodage exotique d'une page | faible | `Dom\HTMLDocument` lit la déclaration de charset ; textes nettoyés et tronqués. |
| Image d'aperçu hotlinkée bloquée par la source | moyenne | `referrerpolicy="no-referrer"` + repli Stimulus. |
| Worker Messenger arrêté en prod | moyenne | Les cartes restent en repli lisible ; le déploiement (story tech future) devra lancer le worker. |

## Questions ouvertes

- **User-Agent** : (a) `RadarBot` explicite, (b) imiter un navigateur. Recommandé : (a), transparence envers les sources.

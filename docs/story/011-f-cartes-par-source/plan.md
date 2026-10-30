# Plan technique — Juger un lien d'un coup d'œil grâce à une carte adaptée à sa source

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/011-f-cartes-par-source/`
> **Amont** : `pitch.md`

## Approche retenue

On garde le pipeline de la story 003 et on remplace son parseur unique par une **cascade d'extracteurs**. `LinkManager` dispatche toujours `FetchLinkPreview` sur le transport `async`. Le handler délègue l'enrichissement à `LinkEnricher`, qui appelle un `MetadataResolver` fonctionnant ainsi :
- il télécharge la page une seule fois, via un `PageFetcher` sécurisé et borné, puis la confie à chaque `MetadataExtractor` dans l'ordre de priorité de leur tag ;
- l'ordre est le suivant : adaptateurs YouTube, GitHub et X, puis oEmbed sur liste blanche (Vimeo, Speaker Deck), OpenGraph, Twitter Cards, JSON-LD et HTML brut ;
- les résultats (value object `LinkPreview`, enrichi) sont fusionnés **champ par champ**, en prenant la première valeur non vide, puis tronqués sur une limite de mot (titre 100, description 250, auteur et site 255).

La page n'est pas téléchargée pour X : son HTML anonyme est inexploitable et polluerait la fusion. La cascade est ouverte : ajouter une source revient à ajouter une classe taguée, sans toucher aux autres.

`LinkEnricher` fusionne ensuite le nouvel aperçu avec le snapshot déjà enregistré : un rafraîchissement partiel n'efface rien. Il confie l'image d'aperçu et la favicon à un `PreviewImageStore`, qui télécharge par le même client sécurisé, vérifie le type MIME réel et la surface (16 Mpx au plus), recadre en 1.91:1 sur 600 px de large (en GD, en WebP) et écrit le fichier via **Flysystem**. Si le téléchargement échoue alors que l'image source n'a pas changé, le visuel déjà conservé est gardé. Le lien reçoit le snapshot (nouvelles colonnes plates et `preview_fetched_at`), puis le handler annonce comme aujourd'hui, une seule fois.

Le navigateur obtient les images par une route authentifiée, `GET /media/{path}`, qui lit Flysystem et répond avec un cache privé immuable. La carte Twig se décline selon le type de source :
- vidéo : façade Stimulus qui compte la lecture en `POST` puis injecte l'iframe `youtube-nocookie` ;
- dépôt : étoiles et langage ;
- article : auteur et date de publication ;
- post X : auteur et texte.

Le repli dégradé reste, avec la favicon en plus. Une commande `app:links:enrich` envoie un message `RefreshLinkPreview` pour chaque lien dont le snapshot est échu (7 j en `ready`, 1 h en `unavailable`, jamais horodaté hors liens en attente), avec `--dry-run` et `--force`. Son handler enrichit sans jamais annoncer. Un décorateur du client HTTP limite le débit par hôte (`symfony/rate-limiter`, `reserve()->wait()`), placé sous `NoPrivateNetworkHttpClient` pour voir chaque saut de redirection ; la chaîne est composée par `PreviewHttpClientFactory`.

### Mécanismes mobilisés

- **Tagged iterator (`#[AutoconfigureTag]` sur l'interface + `#[AsTaggedItem(priority:)]` + `#[AutowireIterator]`)** : la priorité du tag porte l'ordre de la cascade. Une source de plus ne touche ni le resolver ni les autres extracteurs.
- **`NoPrivateNetworkHttpClient` (`app.preview_http_client`)** : il contrôle l'IP à chaque redirection. Toutes les requêtes sortantes (page, oEmbed, API GitHub, images) passent par ce client, construit par `PreviewHttpClientFactory` (fabrique statique appelée depuis `config/services.yaml`) qui pose les options (User-Agent, 5 s, 3 redirections) sur lui.
- **Décorateur `HttpClientInterface` maison par hôte** (sous le contrôle d'IP, donc appelé à chaque saut) : le `ThrottlingHttpClient` natif n'a qu'un seul limiteur, alors que la règle porte sur chaque domaine cible.
- **`symfony/rate-limiter`** (token bucket nommé `preview_host`, stockage `cache.app`) : brique officielle, pas de compteur maison.
- **`HttpClientInterface::stream()`** : lecture morceau par morceau, arrêtée à 512 Ko. Elle ne s'arrête pas à `</head>` : le JSON-LD (auteur, date) vit souvent en fin de `<body>`.
- **`Dom\HTMLDocument` (PHP 8.4+)** : déjà utilisé, avec l'argument `overrideEncoding` alimenté par le charset de l'en-tête HTTP. Sinon, `<meta charset>` est détecté par le parseur HTML5.
- **`Uri\WhatWg\Url`** : déjà utilisé pour résoudre les URL relatives, désormais aussi contre `<base href>`.
- **Flysystem (`league/flysystem-bundle`)** : storage `media.storage`, adapter local `var/storage/media` en dev et en prod, adapter mémoire en test. La bascule vers S3 ne sera qu'un changement de config.
- **GD + `finfo`** : décodage, recadrage et encodage WebP natifs, type MIME vérifié sur les octets. Aucune dépendance image.
- **Messenger (transport `async`, `sync://` en test)** : un message distinct, `RefreshLinkPreview`, pour le rafraîchissement par la CLI ; son handler n'annonce jamais (R5).
- **Commande console (`#[AsCommand]`)** + méthode de repository nommée pour la sélection des liens échus (règle projet : pas de QueryBuilder hors repository).
- **`#[IsCsrfTokenValid('link-play')]`** sur la route de lecture : jeton lié à la session (l'identifiant n'est pas dans `stateless_token_ids`). Un jeton invalide lève une erreur d'authentification, que le firewall traduit en redirection vers `/login`.
- **Stimulus** : un nouveau contrôleur `video-facade`. `image-fallback` est conservé, au cas où un fichier de média manquerait.
- **Extension Twig existante (`LinkExtension`)** : un filtre `youtube_id` qui délègue à `YouTubeVideoId`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| `embed/embed` branché en PSR-18 sur le client sécurisé | Elle impose son ordre de fusion et sa propre liste de providers, et les images, le plafond de lecture et le rate-limit resteraient à notre charge. Déjà écartée en 003. |
| Pool `cache.app` par URL normalisée (7 j / 1 h) | Un lien est unique par URL (R6) et son snapshot est déjà persisté. Un second cache empêcherait la CLI de rafraîchir les étoiles. La règle 7 j / 1 h est donc appliquée à la sélection de la CLI. |
| Images servies via `publicUrl()` (statique local, bucket public en prod) | Il faudrait un bucket en lecture publique, et R1 ne tiendrait plus que par des noms impossibles à deviner. La route authentifiée marche à l'identique quel que soit l'adapter. |
| Adapter S3 + SeaweedFS dès cette story | La cible de prod n'est pas choisie : ce serait une dépendance et un service à maintenir sans usage. Une story technique s'en chargera, sans toucher au code métier. |
| Colonne JSON `preview_details` | La donnée ne serait pas typée et devrait être décodée dans des getters. Les colonnes plates suivent l'existant (`title`, `site_name`). |
| Message différé (`DelayStamp`) quand la limite est atteinte | Plus complexe et réordonne les messages. Avec un seul worker et ce volume, `wait()` suffit. |
| Drapeau « annoncer » sur `FetchLinkPreview` pour la CLI | Un message distinct (`RefreshLinkPreview`) dit l'intention par son type et garde le handler d'ajout inchangé. |
| Réécrire `normalizedUrl` avec `canonical` / `og:url` | Risque de violer `UNIQ_LINK_NORMALIZED_URL` après coup, pour un bénéfice que le pitch ne demande pas (seul `ref` s'ajoute). |
| Favicon ICO/SVG | GD ne décode pas l'ICO, et un SVG servi depuis notre origine présente un risque XSS. Seuls les formats raster sont acceptés. |

## Modèle de données

### Modification de `Link`

`src/Entity/Link.php`. Toutes les colonnes ajoutées sont nullable, donc la migration est sûre sur une table non vide :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `author` | string(255) | oui | auteur (article, post X, chaîne YouTube) |
| `publishedAt` (`published_at`) | datetime_immutable | oui | date de publication de la source |
| `imagePath` (`image_path`) | string(100) | oui | chemin Flysystem `previews/{sha256}.webp` |
| `faviconPath` (`favicon_path`) | string(100) | oui | chemin Flysystem `favicons/{sha256}.png` |
| `githubStars` (`github_stars`) | integer | oui | figé à l'enrichissement |
| `githubLanguage` (`github_language`) | string(50) | oui | langage principal |
| `previewFetchedAt` (`preview_fetched_at`) | datetime_immutable | oui | horodatage du dernier enrichissement, `null` = jamais |

- `image_url` est conservée : elle contient l'URL source de l'image, sert à la retélécharger et n'est plus jamais rendue.
- `title(255)` et `description(500)` sont inchangées. La troncature à 100 / 250 se fait en amont.
- `applyPreview()` prend désormais `LinkPreview $preview, ?string $imagePath, ?string $faviconPath, \DateTimeImmutable $fetchedAt`. `markPreviewUnavailable(\DateTimeImmutable $fetchedAt)` horodate aussi, et laisse un aperçu `ready` en `ready` (seule la date de tentative change). `previewSnapshot(): LinkPreview` rend l'aperçu enregistré, base de la fusion d'un rafraîchissement.
- `PreviewStatus` est inchangé (`pending/ready/unavailable`).
- Index `IDX_LINK_PREVIEW_FETCHED_AT` sur `preview_fetched_at`, pour la sélection de la CLI. Migration `migrations/Version20261030085113.php`.

### Value object `LinkPreview` (enrichi)

`src/Service/Preview/LinkPreview.php` : ajout de `author`, `publishedAt`, `faviconUrl`, `githubStars`, `githubLanguage`, plus `withFallback(self $other): self`, qui fusionne champ par champ en gardant la première valeur non vide, et `withText()` pour les textes tronqués. `isEmpty()` est conservé.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Service/Preview/Extractor/MetadataExtractor.php` | Interface taguée : `extract(string $url, ?HtmlPage $page): LinkPreview`. |
| `src/Service/Preview/Extractor/YouTubeExtractor.php` | oEmbed YouTube (aucun aperçu sans réponse), miniature `maxresdefault` vérifiée en HEAD, `hqdefault` sinon. |
| `src/Service/Preview/Extractor/GitHubExtractor.php` | API REST `/repos/…` si `GITHUB_TOKEN` : étoiles et langage, titre et description à la racine du dépôt seulement ; rien sans jeton (l'OG prend le relais). |
| `src/Service/Preview/Extractor/XExtractor.php` | oEmbed `publish.twitter.com`, auteur et texte brut de la `blockquote`, sans jamais garder le `html`. |
| `src/Service/Preview/Extractor/OEmbedExtractor.php` | Découverte `<link rel="alternate" type="application/json+oembed">`, endpoint limité à la liste blanche (`vimeo.com`, `speakerdeck.com`) ; YouTube et X ont leur adaptateur. |
| `src/Service/Preview/Extractor/OpenGraphExtractor.php` | `og:title/description/image/site_name`, `article:published_time`. |
| `src/Service/Preview/Extractor/TwitterCardExtractor.php` | `twitter:title/description/image`. |
| `src/Service/Preview/Extractor/JsonLdExtractor.php` | Nœud de contenu (`Article`, `BlogPosting`, `VideoObject`…) : `headline`/`name`, description, image, auteur, `datePublished`. |
| `src/Service/Preview/Extractor/HtmlExtractor.php` | `<title>`, `meta[name=description]`, `link rel=icon` (raster), dernier recours. |
| `src/Service/Preview/HtmlPage.php` | Page téléchargée : URL finale, base effective (`<base href>`), document DOM, accès aux métas, aux `link` et aux blocs JSON-LD. Remplace `HtmlMetadata`. |
| `src/Service/Preview/HtmlParser.php` | `Dom\HTMLDocument` avec le charset annoncé par le serveur, détection sinon. |
| `src/Service/Preview/MetadataValue.php` | Lecture prudente des valeurs de source : texte nettoyé, entier, date ISO 8601 stricte ramenée au fuseau de l'application, URL absolue http(s). |
| `src/Service/Preview/JsonLdGraph.php` | Nœuds JSON-LD de tous les blocs, `@graph` déplié, recherche par type et par `@id`. |
| `src/Service/Preview/JsonLdAuthor.php` | Auteur en chaîne, objet, référence `@id` ou liste. |
| `src/Service/Preview/JsonFetcher.php` | Lecture JSON (oEmbed, API GitHub) par le client des aperçus, `null` au moindre échec. |
| `src/Service/Preview/YouTubeVideoId.php` | ID depuis `watch?v=`, `youtu.be/`, `shorts/`, `embed/`, `live/` (11 caractères). |
| `src/Service/Preview/GitHubRepository.php` | `propriétaire/dépôt` d'une adresse github.com, racine ou page interne ; segments `.`/`..` refusés. |
| `src/Service/Preview/PageFetcher.php` | Remplace `LinkPreviewFetcher` : `stream()` plafonné à 512 Ko, `text/html` uniquement (`image/*` signalé à part), page de consentement YouTube ignorée, charset de l'en-tête, `null` en cas d'échec. |
| `src/Service/Preview/MetadataResolver.php` | Orchestre le fetch, les extracteurs (tagged iterator), la fusion et la troncature. Rend `?LinkPreview`. |
| `src/Service/Preview/TextTruncator.php` | Coupe sur une limite de mot et ajoute `…`, sans couper un caractère multi-octet. |
| `src/Service/Preview/LinkEnricher.php` | Résout l'aperçu, le fusionne avec le snapshot existant, conserve les visuels et applique le snapshot horodaté. |
| `src/Service/Preview/PreviewImageStore.php` | Télécharge (5 Mo, 16 Mpx max), vérifie le MIME avec `finfo` (jpeg/png/gif/webp/avif), recadre en 600×314 WebP, ou favicon 64 px en PNG. Écrit via Flysystem et rend le chemin ou `null`. |
| `src/Service/Preview/ImageCropper.php` | Recadrage centré GD, transparence conservée. |
| `src/HttpClient/PerHostThrottlingHttpClient.php` | Décorateur : `reserve()->wait()` sur le limiteur `preview_host`, clé = hôte de la requête. |
| `src/HttpClient/PreviewHttpClientFactory.php` | Compose `NoPrivateNetworkHttpClient(PerHostThrottlingHttpClient(http_client))` avec ses options. |
| `src/Message/RefreshLinkPreview.php` | Rafraîchissement de l'aperçu d'un lien existant. |
| `src/MessageHandler/RefreshLinkPreviewHandler.php` | Enrichit et enregistre, sans annonce. |
| `src/Command/EnrichLinksCommand.php` | `app:links:enrich [--dry-run] [--force]` : liste ou envoie `RefreshLinkPreview` pour les liens échus. |
| `src/Controller/MediaController.php` | `GET /media/{path}` (`previews/` ou `favicons/` + sha256 + extension), stream Flysystem, `Cache-Control: private, max-age=31536000, immutable`, `nosniff`, 404 si absent. |
| `templates/link/_card_visual.html.twig` | Visuel de carte : image conservée, ou repli par type de source avec favicon. |
| `assets/controllers/video_facade_controller.js` | Au clic : `POST` de lecture (jeton CSRF), puis remplacement de la façade par l'iframe `youtube-nocookie.com/embed/{id}?autoplay=1`. |
| `config/packages/flysystem.yaml` | Storage `media.storage` : local `%kernel.project_dir%/var/storage/media`, `memory` en test. |
| `config/packages/rate_limiter.yaml` | Limiteur `preview_host` : token bucket, rafale de 3, 1 jeton toutes les 2 s. |
| `fixtures/images/*.webp` | 13 visuels des fixtures, déjà recadrés en 600×314 par `PreviewImageStore` en dev. |
| `migrations/Version20261030085113.php` | Colonnes et index ci-dessus (`make:migration`). |
| `tests/Fixtures/preview/*.html` / `*.json` | Pages statiques (GitHub, article JSON-LD `@graph`, `<title>` seul, OG relatif + `<base href>`, charset latin-1, Vimeo) et réponses oEmbed / API GitHub. |
| `tests/Service/Preview/Extractor/*Test.php` | Un test par extracteur, à partir des fixtures. |
| `tests/Service/Preview/MetadataResolverTest.php` | Priorité de fusion (stubs), troncature, X sans fetch de page. |
| `tests/Service/Preview/PageFetcherTest.php` | Plafond de 512 Ko, JSON-LD en fin de `<body>`, non-HTML, image, page de consentement. |
| `tests/Service/Preview/PageFetcherSsrfTest.php` | **SSRF** sur le client composé par la fabrique : IP privées, redirection publique → privée, 3 redirections au plus. |
| `tests/Service/Preview/PreviewImageStoreTest.php` | Recadrage, faux MIME refusé, plafonds de taille et de surface (Flysystem mémoire). |
| `tests/Service/Preview/LinkEnricherTest.php` | Snapshot horodaté, visuel conservé, échec, rafraîchissement partiel qui n'efface rien. |
| `tests/Service/Preview/MetadataValueTest.php`, `TextTruncatorTest.php`, `YouTubeVideoIdTest.php` | Dates ISO et fuseau, troncature, formes d'ID YouTube. |
| `tests/Service/Preview/PreviewFixture.php`, `PreviewTestKit.php`, `TestImage.php` | Fixtures statiques, enrichissement et client réels sur réponses simulées, images GD. |
| `tests/HttpClient/PerHostThrottlingHttpClientTest.php` | Une clé de limiteur par hôte. |
| `tests/HttpClient/PreviewHttpClientFactoryTest.php` | User-Agent et délais posés, limiteur consommé à chaque saut. |
| `tests/MessageHandler/RefreshLinkPreviewHandlerTest.php` | Rafraîchissement sans annonce. |
| `tests/Command/EnrichLinksCommandTest.php` | Sélection 7 j / 1 h / jamais, liens en attente exclus, `--dry-run` sans envoi, `--force`, aucune annonce. |
| `tests/Controller/MediaControllerTest.php` | Authentification exigée, 404, en-têtes de cache, chemin hors motif refusé. |
| `tests/Controller/EnrichedLinkWebTestCase.php` | Liens créés avec un aperçu déjà construit, retirés après chaque test. |
| `tests/Controller/LinkCardVariantsTest.php` | Façade vidéo, étoiles et langage, « Par … · publié le … », post X, favicon dans le repli. |
| `tests/Controller/LinkPlayTest.php` | `POST` de lecture → ouverture enregistrée ; jeton invalide → aucune ouverture. |
| `tests/E2E/RichCardTest.php` | Ajout réel YouTube (façade, puis iframe au clic), article (image servie par `/media/`), X (auteur ou repli), mobile. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `composer.json` / `composer.lock` / `symfony.lock` | Ajout de `league/flysystem-bundle`, `symfony/rate-limiter` ; `league/flysystem-memory` en dev. |
| `config/bundles.php` / `config/reference.php` | `FlysystemBundle` (enregistré par Flex), référence régénérée. |
| `config/services.yaml` | `app.preview_http_client` construit par `PreviewHttpClientFactory` avec User-Agent `RadarBot/1.0 (+%env(DEFAULT_URI)%)` ; remplacé par `MockHttpClient` en test. |
| `config/packages/messenger.yaml` | `RefreshLinkPreview` routé sur `async`. |
| `.env` | `GITHUB_TOKEN=` (vide). |
| `mago.toml` | `excessive-parameter-list` exclut `LinkPreview` (snapshot de l'entité) ; `no-boolean-flag-parameter` exclut `src/Command/` (options de console). |
| `src/Entity/Link.php` | Colonnes, getters, index, nouvelles signatures de `applyPreview()` / `markPreviewUnavailable()`, `previewSnapshot()`. |
| `src/Service/Preview/LinkPreview.php` | Nouveaux champs, `withFallback()`, `withText()`. |
| `src/Service/LinkUrlAnalyzer.php` | `ref` dans `TRACKING_PARAMETERS`. |
| `src/MessageHandler/FetchLinkPreviewHandler.php` | Plus de court-circuit X ; délègue à `LinkEnricher`, puis annonce. |
| `src/Repository/LinkRepository.php` | `findDueForEnrichment(\DateTimeImmutable $readyBefore, \DateTimeImmutable $unavailableBefore): list<Link>`, liens en attente exclus. |
| `src/Controller/LinkController.php` | Action `play` : `POST /liens/{id}/lecture`, `#[IsCsrfTokenValid('link-play')]`, `LinkOpeningRecorder`, 204. |
| `src/Twig/LinkExtension.php` | Filtre `youtube_id`. |
| `templates/link/_card.html.twig` | Zone média : façade vidéo ou lien image (`_card_visual.html.twig`) ; lignes « étoiles · langage » et « Par … · publié le … ». Libellés en français. |
| `fixtures/AppFixtures.php` | Écrit `fixtures/images/*.webp` dans `media.storage`, troncatures de l'enrichissement, nouvelle `applyPreview()`. |
| `tests/Http/PreviewMockResponseFactory.php` | Vraie image PNG (octets GD) pour les tests fonctionnels. |
| `tests/MessageHandler/FetchLinkPreviewHandlerTest.php` | Enrichit puis annonce avec le vrai titre ; X résolu ; aucune ré-annonce. |
| `tests/Controller/LinkCardTest.php` | `src` de l'image en `/media/`, `loading="lazy"`, `alt`, aucune image de la source. |
| `tests/Service/LinkUrlAnalyzerTest.php` | `ref` retiré. |
| `tests/Service/LinkAnnouncerTest.php`, `tests/Twig/Components/PendingLinkCardTest.php` | Nouvelle signature de `applyPreview()`. |
| `tests/E2E/E2ETestCase.php` | `freeRealLink()` filtrable par début d'adresse. |
| `src/Service/Preview/LinkPreviewFetcher.php`, `LinkPreviewParser.php`, `HtmlMetadata.php` et leurs tests | Supprimés : remplacés par `PageFetcher`, les extracteurs et `HtmlPage`. |

## Hors scope

- **Adapter S3 et service SeaweedFS** : story technique dédiée quand la prod sera choisie (MinIO communautaire est archivé, SeaweedFS est pressenti).
- **Purge des médias d'un lien retiré** : question ouverte du pitch, à traiter dans `correction-retrait-lien`.
- **Durée des vidéos** : ni extraite, ni persistée, ni affichée (le pitch ne l'affiche pas).
- **Recalcul de `normalizedUrl` pour `ref`** : aucune donnée de prod n'existe (`docs/stack.md`, « Cible non encore en place »).
- **CSP / en-têtes de sécurité globaux** : hors story.

## Impacts transverses

- **Cloisonnement des données** : non concerné (outil mono-organisation).
- **Déclinaisons / thèmes** : variantes de carte en Tailwind, en clair et en sombre, tokens Markit existants.
- **Traduction / i18n** : libellés en dur en français, comme le reste des templates ; dates formatées à l'heure de Paris (R11, `IntlDateFormatter` comme dans `LinkExtension::dayLabel`).
- **API / exposition externe** : appels sortants vers les oEmbed YouTube, X, Vimeo et Speaker Deck et l'API GitHub. Aucun point d'entrée exposé.
- **Droits d'accès** : `/media/*` et `POST /liens/{id}/lecture` sont couverts par `access_control ^/ ROLE_USER` (`config/packages/security.yaml:41`). La CLI est réservée à l'exploitant.
- **Emails / notifications** : aucun changement à l'ajout. Le rafraîchissement par la CLI passe par `RefreshLinkPreview`, dont le handler n'annonce jamais (R5).
- **Migration de données** : colonnes nullable ajoutées. Rattrapage par `app:links:enrich` au premier déploiement (tout lien déjà traité a `preview_fetched_at` à `null` ; les liens en attente ont déjà leur message en file).
- **Comportement par défaut** : toutes les cartes passent aux variantes ; sans métadonnées spécifiques, on garde la carte d'aperçu actuelle.

## Stratégie de test

Aucun appel réseau des aperçus dans `make phpunit`. `MockHttpClient` est utilisé partout ; les tests SSRF passent par le client composé par `PreviewHttpClientFactory` sur un `MockHttpClient`, avec des **IP littérales** pour qu'aucune résolution DNS n'ait lieu.

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `Extractor/YouTubeExtractor` + `YouTubeVideoId` | unit | 5 formes d'URL → ID ; oEmbed → titre et auteur ; `maxres` 404 → `hqdefault` ; oEmbed en échec → aucun aperçu. |
| `Extractor/GitHubExtractor` | unit | Avec token : étoiles, langage, description ; issue → titre laissé à la page ; sans token : aucun appel ; URL hors dépôt ou avec `..` ignorée. |
| `Extractor/XExtractor` | unit | oEmbed → auteur + texte brut (balises retirées) ; échec → aperçu vide. |
| `Extractor/OEmbedExtractor` | unit | Découverte sur la liste blanche acceptée ; endpoint hors liste ignoré. |
| `Extractor/OpenGraph`, `TwitterCard`, `Html` | unit | Fixtures : OG relatif + `<base href>` résolu, page `<title>` seule, entités décodées, charset latin-1. |
| `Extractor/JsonLdExtractor` | unit | Article `@graph`, plusieurs blocs, `author` chaîne / objet / tableau, `datePublished`, JSON invalide ignoré. |
| `MetadataResolver` | unit | Priorité de fusion champ par champ (stubs), troncature 100 / 250 / 255 sur mot, X sans fetch de page, page indisponible → `null`. |
| `MetadataValue`, `TextTruncator` | unit | Dates ISO seules (`2024`, `now` refusés), fuseau de l'application ; troncature multi-octet. |
| `PageFetcher` | unit | `ftp://` refusé ; plafond de 512 Ko ; JSON-LD en fin de `<body>` lu ; non-HTML → `null` ; `image/*` signalé ; page de consentement ignorée. |
| `PageFetcher` + `PreviewHttpClientFactory` (SSRF) | unit | Refus de `127.0.0.1`, `10.0.0.12`, `169.254.169.254`, `[::1]` et d'une redirection publique → privée ; redirection publique suivie ; 3 redirections au plus. |
| `PreviewImageStore` | unit | Image recadrée en 600×314 WebP ; faux MIME refusé ; plafonds de 5 Mo et de 16 Mpx (contre-épreuve sans plafond) ; favicon raster en PNG 64 px, SVG refusé. |
| `PerHostThrottlingHttpClient`, `PreviewHttpClientFactory` | unit | Une clé de limiteur par hôte ; User-Agent et délais posés ; limiteur consommé à chaque saut de redirection. |
| `LinkEnricher` | unit | Snapshot horodaté ; visuel conservé ; échec → indisponible ; rafraîchissement partiel ou en échec qui n'efface rien. |
| `EnrichLinksCommand` | functional | Sélection (jamais, `ready` > 7 j, `unavailable` > 1 h), liens en attente exclus ; `--dry-run` sans envoi ; `--force` sélectionne tout ; aucune annonce. |
| `FetchLinkPreviewHandler`, `RefreshLinkPreviewHandler` | unit | Enrichit puis annonce avec le vrai titre ; X résolu ; aucune ré-annonce ; le rafraîchissement n'annonce jamais. |
| `MediaController` | functional | 302 vers login si anonyme ; 200 + `Cache-Control` immuable ; 404 si fichier absent ; chemin hors motif → 404. |
| `LinkController::play` | functional | `POST` valide → une `LinkOpening` ; jeton invalide → redirection vers `/login`, aucune ouverture. |
| `templates/link/_card.html.twig` | functional | Façade vidéo, étoiles et langage, « Par … · publié le … », auteur X, `src` en `/media/`, `loading="lazy"` + `alt`, favicon dans le repli. |
| `tests/E2E/RichCardTest.php` | E2E | Sur le serveur de dev, avec réseau et worker : ajout YouTube → façade puis iframe après le clic ; article → image servie par `/media/` ; X → auteur ou repli ; viewport mobile sans débordement. |

**Hors scope tests** :

- Étoiles GitHub en E2E : `GITHUB_TOKEN` est vide en dev. Le cas est couvert en fonctionnel avec mock.
- Rendu réel de l'iframe YouTube (lecture effective) : on vérifie sa présence et son `src`, pas la vidéo.
- Adapters Flysystem autres que local et mémoire : pas encore en place.

## Ordre d'exécution

1. [x] **Extracteurs et resolver**
   - Objectif : cascade complète et testée, sans persistance nouvelle (le handler continue d'appliquer titre, description, image et site).
   - Fichiers : `Extractor/*`, `HtmlPage`, `PageFetcher`, `MetadataResolver`, `TextTruncator`, `LinkPreview`, `PerHostThrottlingHttpClient`, `config/services.yaml`, `config/packages/rate_limiter.yaml`, `LinkUrlAnalyzer` (`ref`), `YouTubeVideoId`, suppression de l'ancien parseur, fixtures de test, tests unitaires et SSRF.
   - Vérification : `make phpunit` et `make lint` verts.
   - Commitable seule : oui.

2. [x] **Asynchrone et persistance**
   - Objectif : snapshot complet horodaté, X enrichi, CLI de rattrapage.
   - Fichiers : `Link` + migration (`make:migration`, relue), `FetchLinkPreviewHandler`, `LinkRepository::findDueForEnrichment`, `EnrichLinksCommand`, `AppFixtures` (nouvelle signature, sans images à ce stade), tests du handler et de la commande.
   - Vérification : `make db-reset`, `make phpunit`, `make lint` ; `symfony console app:links:enrich --dry-run` liste les liens.
   - Commitable seule : oui.

3. [x] **Images**
   - Objectif : visuels et favicons conservés et servis par Radar.
   - Fichiers : `composer.json` (Flysystem), `config/packages/flysystem.yaml`, `PreviewImageStore`, `MediaController`, branchement dans le handler, `fixtures/images/*.webp` + `AppFixtures`, `PreviewMockResponseFactory` (vraie image), tests.
   - Vérification : `make phpunit`, `make lint` ; en dev, `app:links:enrich --force` remplit `var/storage/media/` et `/media/…` répond 200 une fois connecté.
   - Commitable seule : oui (la carte affiche encore `imageUrl` jusqu'à l'étape 4).

4. [x] **Rendu des cartes**
   - Objectif : variantes vidéo, dépôt, article, X et repli + favicon, lecture comptée.
   - Fichiers : `_card.html.twig`, `video_facade_controller.js`, `LinkController::play`, `LinkExtension` (`youtube_id`), `LinkCardTest`, `LinkPlayTest`, `tests/E2E/RichCardTest.php`.
   - Vérification : `make phpunit`, `make lint`, `make build`, puis `make serve` + `make db-reset` + `make playwright` verts.
   - Commitable seule : oui.

## Critères de sortie

- [x] Aucun template ne rend `imageUrl` ; toute image de carte a un `src` en `/media/`.
- [x] Ajouter une source = une classe `MetadataExtractor` taguée, sans modifier le resolver ni les autres extracteurs.
- [x] Les tests SSRF (`127.0.0.1`, `10.x`, `169.254.169.254`, `[::1]`, redirection publique → privée) sont verts.
- [ ] `make phpunit` vert sans aucun appel réseau réel (aucune résolution DNS).
- [x] `make lint` : zéro finding Mago, sans baseline.
- [x] `make playwright` vert sur le serveur de dev (après `make db-reset`).
- [x] `symfony console doctrine:schema:validate` OK après migration.
- [x] `app:links:enrich --dry-run` ne modifie rien et ne dispatche rien.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| `league/flysystem-bundle` pas encore compatible Symfony 8.1 | levé | La 3.7.1 accepte Symfony `^8.0`. |
| `NoPrivateNetworkHttpClient` résout le DNS, y compris en test | élevée | Tests SSRF avec IP littérales ; le client mocké de l'env de test reste sans `NoPrivateNetwork` (comme aujourd'hui). |
| YouTube redirige un serveur européen vers `consent.youtube.com` | élevée | L'adaptateur YouTube passe par l'oEmbed (indépendant de la page). Une page dont l'URL finale change d'hôte vers `consent.*` est ignorée. |
| oEmbed X changé ou fermé | moyenne | Dégradation vers la carte de repli (règle 6 du pitch) ; l'E2E accepte auteur **ou** repli. |
| E2E dépendants du réseau réel (flaky) | moyenne | Convention existante (`RealLinks`) ; on attend que la carte quitte `pending` ; les variantes sont garanties en fonctionnel. |
| Mémoire GD sur une image géante (bombe de pixels) | moyenne | Taille max de 5 Mo, puis dimensions et surface (16 Mpx) lues par `getimagesizefromstring()` avant `imagecreatefromstring()`. |
| `reserve()->wait()` bloque le worker pendant le rattrapage | faible | Volume d'environ 100 liens ; acceptable avec un seul worker. |
| Seuils de complexité Mago sur `HtmlPage`, `JsonLdExtractor`, `GitHubExtractor`, `LinkUrlAnalyzer` | élevée | Découpage en classes (`HtmlParser`, `MetadataValue`, `JsonLdGraph`, `JsonLdAuthor`, `YouTubeVideoId`, `GitHubRepository`, `LinkEnricher`) ; deux exclusions justifiées dans `mago.toml`. |

## Questions ouvertes

- **Domaine du User-Agent** : `RadarBot/1.0 (+<url>)` demande une URL publique, or Radar est interne et sans domaine de prod connu. Options : (a) `DEFAULT_URI`, (b) l'URL du dépôt GitHub. Recommandé : (a), à paramétrer par l'environnement. → tranché : (a), `RadarBot/1.0 (+%env(DEFAULT_URI)%)`.
- **Lien vers une image directe** : afficher le visuel conservé recadré en 1.91:1 (comme les autres), ou en entier (`object-contain`) ? Recommandé : recadré, par cohérence de la grille ; à revoir à l'usage. → tranché : recadré.

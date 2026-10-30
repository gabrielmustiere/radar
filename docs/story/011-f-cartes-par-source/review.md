# Review — Juger un lien d'un coup d'œil grâce à une carte adaptée à sa source

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/011-f-cartes-par-source/`
> **Amont** : `plan.md` + `pitch.md`
> **Diff examiné** : working tree, 29 fichiers modifiés (+870 / −405) et 43 entrées nouvelles (sources, tests, fixtures, migration)

## Synthèse

- **Bloquants restants** : 0 / 1
- **Importants restants** : 0 / 5
- **Mineurs restants** : 0 / 6
- **Statut** : **PRÊT À COMMITER**

Tout est corrigé (`make lint` à zéro finding, `make phpunit` 210 tests, `make playwright` 19 tests). Prochaine étape : `/forge:sync`, puis `/forge:commit`. Le fichier est conservé pour la section « Hors review ».

## Bloquants

- [x] **[PLAN] Le ré-enrichissement par la CLI peut annoncer des liens dans Google Chat** — `src/MessageHandler/FetchLinkPreviewHandler.php:31` — Corrigé : nouveau message `RefreshLinkPreview` et son handler (enrichit, n'annonce jamais), envoyé par la CLI ; liens `pending` exclus de la sélection par défaut. Tests : `RefreshLinkPreviewHandlerTest`, `EnrichLinksCommandTest` (aucune annonce, `pending` exclu).

## Importants

- [x] **[ROBUSTESSE] Une image géante fait tomber le worker en boucle** — `src/Service/Preview/PreviewImageStore.php:100` — Corrigé : plafond de surface `MAX_PIXELS` (4096², environ 64 Mo décodés), lu dans l'en-tête avant décodage. Test : image de 4100×4100 refusée, avec contre-épreuve sans le plafond.
- [x] **[BUG] Un rafraîchissement partiel efface l'aperçu et le visuel déjà connus** — `src/Service/Preview/LinkEnricher.php:30` — Corrigé : `LinkEnricher` fusionne le nouvel aperçu avec `Link::previewSnapshot()`, garde le visuel conservé si l'image source n'a pas changé et la favicon si son téléchargement échoue. Test : `LinkEnricherTest::testPartialRefreshKeepsWhatWasAlreadyKnown`.
- [x] **[BUG] Une vidéo supprimée ou privée passe en « prêt » sans titre** — `src/Service/Preview/Extractor/YouTubeExtractor.php:40` — Corrigé : aucun aperçu sans réponse oEmbed. Test : `YouTubeExtractorTest::testGivesNoPreviewWhenVideoIsUnavailable`.
- [x] **[BUG] Avec un jeton GitHub, une issue ou une PR prend le titre et la description du dépôt** — `src/Service/Preview/Extractor/GitHubExtractor.php:58` — Corrigé : `GitHubRepository::fromUrl()` distingue la racine du dépôt ; titre et description seulement à la racine, étoiles et langage partout. Test : issue `#42`.
- [x] **[BUG] La date de publication peut être fausse** — `src/Service/Preview/MetadataValue.php:32` — Corrigé : `MetadataValue::date()` n'accepte que l'ISO 8601 et convertit au fuseau de l'application. Test : `MetadataValueTest`.

## Mineurs

- [x] **[TEST] Le cas « image trop volumineuse » passe pour une mauvaise raison** — `tests/Service/Preview/PreviewImageStoreTest.php:56` — Corrigé : vraie image suivie de 6 Mo d'octets, et image de 4100×4100 ; les deux cas échouent si l'on retire les plafonds.
- [x] **[ROBUSTESSE] Auteur et nom du site ne sont pas tronqués** — `src/Service/Preview/MetadataResolver.php:43` — Corrigé : troncature à 255 dans `MetadataResolver`, testée.
- [x] **[SECU] Des segments `..` passent dans le chemin de l'API GitHub** — `src/Service/Preview/Extractor/GitHubExtractor.php:76` — Corrigé : segments `.` et `..` refusés par `GitHubRepository`. Test : `github.com/../user`.
- [x] **[TEST] La chaîne de clients de prod n'est couverte par aucun test** — `config/services.yaml:31` — Corrigé : chaîne construite par `PreviewHttpClientFactory` (appelée par `services.yaml`). Les tests SSRF passent par elle, et `PreviewHttpClientFactoryTest` vérifie User-Agent, délais et limiteur.
- [x] **[ROBUSTESSE] Le throttle ne s'applique qu'à l'hôte initial** — `src/HttpClient/PerHostThrottlingHttpClient.php:30` — Corrigé : le limiteur est placé sous `NoPrivateNetworkHttpClient` et voit chaque saut. Test : deux hôtes consommés sur une redirection.
- [x] **[ROBUSTESSE] Le JSON-LD placé dans le `<body>` est ignoré** — `src/Service/Preview/PageFetcher.php:71` — Corrigé : la lecture va jusqu'au plafond de 512 Ko au lieu de s'arrêter à `</head>`. Test : JSON-LD en fin de `<body>` lu.

## Points positifs

- **SSRF couvert pour de vrai** : toutes les requêtes sortantes (page, oEmbed, API, miniatures, images, favicons) passent par le client protégé, et les tests exercent une redirection réellement suivie.
- **Rien d'interprétable venant d'une source** : auto-escape Twig partout, ID vidéo validé par `^[\w-]{11}$`, HTML oEmbed lu pour son texte seul, SVG refusé et images toujours ré-encodées.
- **`/media` fermé** : regex stricte (pas de traversée de chemin), `ROLE_USER`, `nosniff`, cache privé.
- **Cascade ouverte** : ajouter une source revient à écrire une classe taguée, sans toucher au resolver.
- **Tests sur fixtures statiques** : chaque extracteur est testé sans réseau, et l'E2E vérifie la vraie chaîne avec le worker.

## Hors review (à vérifier en environnement réel)

- `memory_limit` du worker en production : doit couvrir une image de 16 Mpx décodée (environ 64 Mo) en plus de l'application (le dev tourne à 1024M).
- Volume persistant pour `var/storage/media`, au même titre que la base SQLite.
- `GITHUB_TOKEN` en lecture seule, sans scope, pour afficher étoiles et langage.
- Lancer `symfony console app:links:enrich` après le premier déploiement pour le rattrapage.
- Préexistant, hors diff mais élargi par les nouvelles sources : `LinkAnnouncer` injecte le titre brut dans le message Google Chat. Un titre contenant `<users/all>` ou `<https://…|texte>` serait interprété par Chat (mention de tout l'espace, faux lien). À traiter dans une story dédiée.
- Préexistant : la contrainte `ON DELETE RESTRICT` de `link.posted_by_id` est perdue en base depuis `Version20261029195719`.

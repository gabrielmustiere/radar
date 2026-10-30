# Report — Juger un lien d'un coup d'œil grâce à une carte adaptée à sa source

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/011-f-cartes-par-source/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 25 — 18 écarts volontaires et 7 ajouts non prévus, tous réalignés dans `plan.md`, dont 4 aussi dans `pitch.md`.
- **Critères** : 11 / 11 critères d'acceptation cochés dans `pitch.md` ; 7 / 8 critères de sortie cochés dans `plan.md`.
- **Non livré** : 1 critère de sortie (tests sans aucun appel réseau, à cause d'UX Icons, préexistant).
- **Dette** : 8 items, dont 2 critiques au premier déploiement.

Le livré suit l'intention du pitch. Les écarts viennent de deux sources. L'implémentation a découpé le code en classes plus fines pour tenir les seuils de Mago. La review a corrigé le comportement du rafraîchissement (sans annonce, sans effacer ce qui était connu) et durci la sécurité (bombe de pixels, limiteur à chaque saut, dates). Il reste des vérifications de déploiement et deux défauts antérieurs à la story.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| `HtmlPage` porte parsing, métas, liens et JSON-LD | Découpé en `HtmlPage`, `HtmlParser`, `MetadataValue`, `JsonLdGraph`, `JsonLdAuthor` | Seuils de complexité Mago (implémentation) | `plan.md` §Périmètre, §Risques |
| `LinkUrlAnalyzer::youTubeVideoId()` | `YouTubeVideoId::fromUrl()` | Seuil de complexité Mago sur `LinkUrlAnalyzer` (implémentation) | `plan.md` §Mécanismes, §Périmètre, §Ordre d'exécution |
| Le handler orchestre résolution, visuels et snapshot | `LinkEnricher` porte l'enrichissement, le handler annonce | Liste de paramètres trop longue, Mago (implémentation) | `plan.md` §Approche, §Périmètre |
| Liste blanche oEmbed de 4 hôtes | `vimeo.com` et `speakerdeck.com` seulement | YouTube et X ont leur adaptateur : chaque appel aurait été doublé (implémentation) | `plan.md` §Approche, §Périmètre |
| Lecture arrêtée à `</head>` ou à 512 Ko | Lecture jusqu'à 512 Ko | Review [ROBUSTESSE] Le JSON-LD placé dans le `<body>` est ignoré | `plan.md` §Mécanismes, §Périmètre, §Stratégie de test |
| Chaîne `throttle(NoPrivateNetwork)` déclarée en YAML | `NoPrivateNetwork(throttle)` composée par `PreviewHttpClientFactory` | Review [ROBUSTESSE] Le throttle ne s'applique qu'à l'hôte initial ; [TEST] La chaîne de clients de prod n'est couverte par aucun test | `plan.md` §Approche, §Mécanismes, §Périmètre |
| La CLI redispatche `FetchLinkPreview` | Message `RefreshLinkPreview` sans annonce ; liens en attente exclus | Review [PLAN] Le ré-enrichissement par la CLI peut annoncer des liens dans Google Chat | `plan.md` §Approche, §Mécanismes, §Impacts transverses, §Alternatives écartées |
| Un échec marque le lien indisponible ; `applyPreview()` remplace tout | Un échec garde un aperçu `ready` ; le rafraîchissement fusionne avec le snapshot et garde le visuel conservé | Décision d'implémentation (« un échec conserve le dernier aperçu connu »), puis review [BUG] Un rafraîchissement partiel efface l'aperçu et le visuel déjà connus | `plan.md` §Approche, §Modèle de données ; `pitch.md` §Règles métier (règle 12) |
| L'adaptateur YouTube rend toujours une miniature | Aucun aperçu sans réponse oEmbed | Review [BUG] Une vidéo supprimée ou privée passe en « prêt » sans titre | `plan.md` §Périmètre, §Stratégie de test ; `pitch.md` §Règles métier (règle 2) |
| GitHub : titre et description du dépôt sur toute page github.com | Titre et description à la racine du dépôt seulement | Review [BUG] Avec un jeton GitHub, une issue ou une PR prend le titre et la description du dépôt | `plan.md` §Périmètre ; `pitch.md` §Règles métier (règle 4) |
| Date lue par `DateTimeImmutable` | ISO 8601 stricte, ramenée au fuseau de l'application | Review [BUG] La date de publication peut être fausse | `plan.md` §Périmètre, §Stratégie de test ; `pitch.md` §Règles métier (règle 5) |
| Images limitées à 5 Mo et 8000 px de côté | Plus une surface de 16 Mpx | Review [ROBUSTESSE] Une image géante fait tomber le worker en boucle | `plan.md` §Approche, §Périmètre, §Risques |
| Lecture : jeton CSRF stateless | Jeton lié à la session (`link-play` hors `stateless_token_ids`) | non tracée | `plan.md` §Mécanismes |
| Jeton de lecture invalide → refus | Redirection vers `/login`, aucune ouverture | `InvalidCsrfTokenException` est une erreur d'authentification, traduite par le firewall (implémentation) | `plan.md` §Mécanismes, §Stratégie de test |
| `findDueForEnrichment($now, $force)` | `findDueForEnrichment($readyBefore, $unavailableBefore)` ; `--force` via `findBy()` | non tracée | `plan.md` §Périmètre |
| Limiteur avec une limite large en test | Pas de configuration de test | Le client entier est remplacé par le mock en test (implémentation) | `plan.md` §Périmètre |
| Visuels de toutes les fixtures | 13 visuels sur 16 | `opengraph.githubassets.com` a répondu 429 pour 3 images (implémentation) | `plan.md` §Périmètre |
| Pas d'exclusion de règle Mago | `excessive-parameter-list` exclut `LinkPreview`, `no-boolean-flag-parameter` exclut `src/Command/` | Snapshot de l'entité ; option de console booléenne par nature (implémentation) | `plan.md` §Périmètre, §Risques |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Critère de sortie « `make phpunit` vert sans aucun appel réseau réel » | Préexistant : les icônes Tabler ne sont pas verrouillées dans `assets/icons/`, UX Icons interroge Iconify au rendu (`config/reference.php`, `iconify` actif par défaut). Les aperçus, eux, ne font aucun appel. | Dette n°3 |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| `JsonFetcher` | Lecture JSON et gestion d'échec partagées par les 4 sources oEmbed/API (implémentation) | `plan.md` §Périmètre |
| `GitHubRepository` | Seuil de complexité Mago après le correctif de review sur la racine du dépôt | `plan.md` §Périmètre |
| `ImageCropper` | Recadrage GD isolé du stockage | `plan.md` §Périmètre |
| `templates/link/_card_visual.html.twig` | Visuel partagé par la façade vidéo et le lien image (implémentation) | `plan.md` §Périmètre |
| Troncature de l'auteur et du nom du site à 255 | Review [ROBUSTESSE] Auteur et nom du site ne sont pas tronqués | `plan.md` §Approche, §Stratégie de test |
| `LinkCardVariantsTest` + `EnrichedLinkWebTestCase`, `MetadataValueTest`, `TextTruncatorTest`, `YouTubeVideoIdTest`, `LinkEnricherTest`, `RefreshLinkPreviewHandlerTest`, `PreviewHttpClientFactoryTest` et les kits `PreviewFixture`, `PreviewTestKit`, `TestImage` | Classes nouvelles et correctifs de review, chacun testé | `plan.md` §Périmètre, §Stratégie de test |
| `E2ETestCase::freeRealLink()` filtrable par préfixe | Choisir une vidéo, un article ou un post X dans `RealLinks` (implémentation) | `plan.md` §Périmètre |

## Dette technique identifiée

1. **Volume persistant pour `var/storage/media`** — sans lui, chaque redéploiement du container perd les visuels conservés (le snapshot pointe alors vers des fichiers absents ; la carte retombe sur son repli). **Critique** au premier déploiement. (Review, Hors review.)
2. **`memory_limit` du worker en production** — doit couvrir une image de 16 Mpx décodée (environ 64 Mo) en plus de l'application ; le dev tourne à 1024M. **Critique** au premier déploiement. (Review, Hors review.)
3. **Icônes Tabler non verrouillées** — UX Icons interroge Iconify pendant les tests et à chaque premier rendu d'une icône. Action : `symfony console ux:icons:lock` pour les rapatrier dans `assets/icons/`. (Critère de sortie non atteint, préexistant.)
4. **Rattrapage au premier déploiement** — lancer `symfony console app:links:enrich` après la migration ; configurer `GITHUB_TOKEN` (lecture seule, sans scope) pour afficher étoiles et langage. (Review, Hors review.)
5. **Titres de source injectés tels quels dans l'annonce Google Chat** — `src/Service/LinkAnnouncer.php` : un titre contenant `<users/all>` ou `<https://…|texte>` serait interprété par Chat. Préexistant, élargi par les nouvelles sources (oEmbed, X). Action : échapper ces séquences, dans une story dédiée. (Review, Hors review.)
6. **Contrainte `ON DELETE RESTRICT` perdue sur `link.posted_by_id`** — préexistant depuis `Version20261029195719` ; la nouvelle migration reproduit l'état réel de la base. Action : migration dédiée. (Review, Hors review.)
7. **3 visuels de fixtures manquants** — `fixtures/images/` : les images OG de `symfony/ux`, `anthropics/claude-code` et `anthropics/skills` ont répondu 429. Action : les régénérer quand GitHub les sert.
8. **`make db-reset` ne vide pas `var/storage/media`** — les visuels des liens supprimés restent sur le disque de dev. Action : vider le stockage dans la cible, ou l'accepter en dev.

## Leçons apprises

- **Les seuils de Mago dessinent le découpage** : sur un parseur ou un orchestrateur, prévoir dès le plan des classes fines (lecture de valeurs, graphe JSON-LD, analyse d'adresse). Sur cette story, sept classes sont nées de la QA et non du plan.
- **Un `MockResponse` ne suit une redirection que si `redirect_url` est renseigné** : sans lui, un test « redirection vers le réseau interne refusée » passe sans que le contrôle d'IP joue. Il faut toujours un test positif (redirection publique suivie) à côté du test négatif.
- **`NoPrivateNetworkHttpClient` suit lui-même les redirections, avec ses propres options** : les options se posent sur lui, et un décorateur qui doit voir chaque saut se place dessous. `withOptions()` clone aussi le `MockHttpClient` interne : compter les requêtes dans la fabrique de réponses, pas sur l'instance d'origine.
- **Un rafraîchissement n'est pas un ajout** : il a besoin de son propre message (aucune annonce) et d'une fusion avec l'existant. Le plan les traitait comme une seule opération ; la review l'a rattrapé.
- **`runCommand()` de Symfony 8.1 écrit dans une sortie qui refuse `SymfonyStyle::table()`** : tester les commandes à tableau avec `CommandTester::execute()`.

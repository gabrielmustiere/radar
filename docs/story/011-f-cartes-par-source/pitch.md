# Juger un lien d'un coup d'œil grâce à une carte adaptée à sa source

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/011-f-cartes-par-source/`
> **Amont** : aucun

La carte d'un lien montre ce que sa source a de propre : une vidéo se lance depuis la carte, un dépôt affiche ses étoiles et son langage, un article son auteur et sa date de publication, un post X son auteur et son texte. Les visuels sont conservés par Radar pour que la carte reste intacte des mois plus tard.

## Contexte

Depuis la story `003-f-carte-apercu`, chaque lien a une carte générique : titre, description, image et nom du site, quand la page les expose. Toutes les sources se ressemblent donc. On ne sait pas si un dépôt est populaire, qui a écrit un article ni de quand il date, et une vidéo ne se regarde qu'en quittant Radar. Les liens X n'affichent que la carte de repli.

Deux défauts s'aggravent avec le temps. L'image d'aperçu est chargée depuis la source : quand elle disparaît, la carte perd son visuel, précisément pour les liens anciens que la vision veut faire rouvrir. Et le navigateur de chaque membre appelle des sites tiers à chaque affichage.

## Alignement vision

- **Problème adressé** : retrouver un lien des mois après son ajout et juger tout de suite s'il sert le besoin du moment (l'âge d'un article, la popularité d'un dépôt).
- **Audience servie** : le développeur face à un choix technique (parcours P2), et tous les membres qui parcourent le fil (P4).
- **Principes respectés** : principe 2 (une carte se lit sans clic), renforcé par source. Principe 1 : le posteur ne saisit rien de plus, l'enrichissement se fait après l'ajout. Le risque « sources fermées aux aperçus » est atténué pour X, et la carte de repli reste le dernier recours.
- **Hypothèse testée** : aucune directement.
- **Impact North Star** : indirect, puisqu'un lien ancien mieux décrit est plus souvent rouvert. Il devient direct pour les vidéos : lancer une vidéo dans la carte compte comme une ouverture.

## Utilisateurs concernés

- **Membre connecté** : lit une carte adaptée à la source et peut regarder une vidéo sans quitter Radar.
- **Posteur** : aucun changement. L'ajout reste instantané et rien de plus n'est demandé.
- **Exploitant de Radar** (celui qui déploie) : dispose d'une commande pour enrichir à nouveau les liens existants.

## User Stories

- En tant que **membre**, je veux voir les étoiles et le langage d'un dépôt GitHub afin de juger sa maturité sans l'ouvrir.
- En tant que **membre**, je veux lire l'auteur et la date de publication d'un article afin de savoir s'il est encore d'actualité.
- En tant que **membre**, je veux lancer une vidéo YouTube depuis sa carte afin de la regarder sans quitter Radar.
- En tant que **membre**, je veux lire l'auteur et le texte d'un post X afin de savoir de quoi il parle sans l'ouvrir.
- En tant que **membre**, je veux que le visuel d'une carte ancienne soit toujours là afin de reconnaître le lien des mois plus tard.
- En tant que **membre**, je ne veux PAS que mon navigateur appelle les sites des liens au simple affichage d'une liste.
- En tant que **posteur**, je ne veux PAS attendre l'enrichissement pour terminer mon ajout.
- En tant qu'**exploitant**, je veux enrichir à nouveau les liens existants, avec un mode simulation, afin que toute la base passe aux nouvelles cartes.

## Règles métier

1. Pour chaque information de la carte, Radar retient la source la plus fiable qui la fournit : d'abord ce que la plateforme expose spécifiquement (YouTube, GitHub, X), puis l'aperçu standard de la page, puis le contenu brut de la page.
2. **Vidéo YouTube** : la carte montre la miniature et un bouton de lecture. Le lecteur ne se charge qu'au clic, jamais à l'affichage de la liste. Une vidéo dont l'aperçu officiel ne répond pas (privée, supprimée) n'a pas d'aperçu : elle garde la carte de repli.
3. **Lancer une vidéo dans la carte compte comme une ouverture du lien**, avec l'âge du lien, exactement comme un clic sur « Ouvrir » (North Star, C6.1).
4. **Dépôt GitHub** : la carte montre les étoiles et le langage principal quand Radar peut les obtenir. Ces chiffres sont figés au moment de l'enrichissement et ne bougent qu'à un nouvel enrichissement. Sans ces chiffres, la carte reste une carte d'aperçu ordinaire. Pour une page interne d'un dépôt (issue, PR, fichier), le titre et la description restent ceux de la page.
5. **Article** : la carte montre l'auteur et la date de publication quand la page les fournit, avec un libellé (« Par … · publié le … ») qui la distingue de la date d'ajout dans Radar, laquelle reste affichée. Seule une date complète est retenue (jour, mois, année), lue à l'heure de Paris.
6. **Post X** : Radar tente l'aperçu officiel de X. Le texte affiché est du texte brut. S'il n'aboutit pas, le lien garde la carte de repli.
7. **Visuels conservés** : l'image d'aperçu est récupérée, recadrée au format des cartes et conservée par Radar. La carte n'affiche jamais une image chargée depuis la source. Un lien qui pointe directement vers une image suit la même règle.
8. **Repli visuel** : sans image, la carte garde le dégradé et l'icône de son type de source et affiche la favicon du site quand elle existe.
9. Tout ce qui vient d'une source est affiché comme du texte, jamais interprété comme du contenu actif.
10. Radar n'interroge que des adresses publiques d'internet, jamais le réseau interne, y compris quand une adresse publique redirige vers le réseau interne. Cela vaut pour les pages comme pour les images.
11. Radar ne sollicite pas une même source en rafale. Il ne refait pas l'enrichissement d'une même adresse à chaque demande : un résultat récent est réutilisé.
12. Un nouvel enrichissement qui échoue, ou qui n'obtient qu'une partie des informations, n'efface rien de ce qui était déjà connu : la carte garde son titre, sa description et son visuel.
13. Un nouvel enrichissement ne déclenche jamais de nouvelle annonce dans Google Chat (R5).
14. L'adresse de référence d'un lien ignore les paramètres de suivi, dont `ref` en plus de ceux déjà retirés.

## Critères d'acceptation

- [x] Une vidéo YouTube affiche sa miniature et un bouton de lecture. Le lecteur ne se charge qu'après le clic.
- [x] Lancer une vidéo depuis sa carte enregistre une ouverture du lien.
- [x] Un dépôt GitHub affiche ses étoiles et son langage quand ils sont disponibles, et une carte d'aperçu ordinaire sinon.
- [x] Un article qui fournit son auteur et sa date de publication les affiche, distincts de la date d'ajout.
- [x] Un post X affiche son auteur et son texte quand l'aperçu officiel répond, et la carte de repli sinon.
- [x] Une carte avec image affiche une image servie par Radar, au format des cartes, et jamais l'image d'origine.
- [x] Une carte sans image affiche le repli par type de source, avec la favicon du site quand elle existe.
- [x] Les images de carte se chargent au fil du défilement, ont un texte alternatif, et la carte reste lisible sur mobile.
- [x] L'ajout d'un lien n'attend pas l'enrichissement. Pendant l'enrichissement, ou s'il échoue, la carte de repli s'affiche.
- [x] Une adresse du réseau interne, ou une adresse publique qui redirige vers lui, n'est jamais interrogée.
- [x] La commande d'enrichissement repasse les liens existants sur les nouvelles cartes. En mode simulation, elle ne modifie rien. Elle ne déclenche aucune annonce.

## Hors scope

- **Bouton « relancer l'aperçu » pour les membres** : ligne V2 `relance-apercu` (C2.5). Seule la commande d'exploitation est livrée ici.
- **Recherche sur l'auteur ou le nom du site** : la recherche (C4.2) reste sur les titres, descriptions, contextes et catégories.
- **Filtre par type de source** : ligne V2 `filtre-type-source`, qui dépend de cette story.
- **Mise à jour automatique des étoiles GitHub** : les chiffres ne changent qu'à un nouvel enrichissement.
- **Lecture intégrée des posts X ou d'autres plateformes vidéo** : seul YouTube a un lecteur dans la carte.
- **Annonce Google Chat enrichie** (auteur, étoiles…) : l'annonce reste inchangée.

## Impacts transverses

- **Traduction / langues** : oui. Libellés « Par … », « publié le … », étoiles, langage, bouton de lecture. Dates à l'heure de Paris (R11).
- **Droits d'accès** : inchangés. La commande d'enrichissement est réservée à l'exploitant.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : de nouvelles variantes de carte (vidéo, dépôt, article, post X), dans le langage visuel Markit, en clair et en sombre.
- **Exposition à des tiers** : Radar consulte les pages publiques et les services d'aperçu officiels de YouTube, GitHub et X. Le navigateur des membres n'appelle plus les sources pour les images. Il n'appelle YouTube que lorsqu'un membre lance une vidéo.
- **Emails / notifications** : non. Aucune annonce supplémentaire (R5).
- **Données existantes** : oui. Tous les liens existants sont enrichis à nouveau par la commande lors du déploiement. D'ici là, ils gardent leur carte actuelle.
- **Comportement par défaut** : toutes les cartes passent aux variantes par source. Les sources sans variante gardent la carte d'aperçu ordinaire.

## Questions ouvertes

- **Visuels d'un lien retiré** : aucune fonctionnalité ne retire un lien aujourd'hui. Que deviennent ses visuels conservés quand `correction-retrait-lien` existera ? Options : (a) supprimés avec le lien, (b) conservés. Recommandé : (a), puisque rien d'autre ne les référence. À trancher dans `correction-retrait-lien`.

---

## Annexe — Pistes pour le plan

Pistes à confirmer, non contractuelles. Elles reprennent le cahier technique du demandeur et ce que le code fait déjà.

**Existant à faire évoluer**
- L'entité est `Link` (pas de `Bookmark`). Aperçu actuel : `FetchLinkPreview` / `FetchLinkPreviewHandler` (Messenger `async`, `sync://` en test), `LinkPreviewFetcher` + `LinkPreviewParser` + `HtmlMetadata` (`Dom\HTMLDocument`), `PreviewStatus` `pending/ready/unavailable`, carte `templates/link/_card.html.twig`, rafraîchissement `PendingLinkCard`, repli `image_fallback_controller.js`.
- `FetchLinkPreviewHandler` contourne X (`SourceType::X` → `unavailable`) : à retirer.
- Le statut demandé (`pending/done/failed` + horodatage) correspond aux trois états existants. Garder les noms actuels et ajouter un horodatage.
- `LinkAnnouncer` ignore déjà un lien dont `announcedAt` est renseigné : le ré-enrichissement n'annonce rien (règle 12).
- `LinkUrlAnalyzer::normalize()` retire déjà `utm_*`, `fbclid`, `gclid`, `mc_*`, `si`. Ajouter `ref`. Préférer `canonical` / `og:url` comme URL de référence, sans casser l'unicité R6 (la normalisation sert la détection de doublon).
- Pas de lib d'unfurl installée. `embed/embed` a déjà été écartée en 003. À réévaluer : elle couvre la cascade, mais apporte son propre client et sa propre liste de providers, et la brancher sur notre client sécurisé reste à vérifier.

**Cascade et architecture**
- Une interface `MetadataExtractor` par source et un `MetadataResolver` qui fusionne champ par champ (première valeur non vide). Ordre : adaptateurs dédiés (YouTube, GitHub, X), oEmbed sur liste blanche (découverte par `<link rel="alternate" type="application/json+oembed">` ou endpoint connu), OpenGraph, Twitter Cards, JSON-LD (plusieurs blocs, `@graph`, `author` sous forme de chaîne, d'objet ou de tableau, `datePublished`, durée), HTML brut (`<title>`, `meta description`, `link rel=icon`, `link rel=canonical`). Tagged iterator avec priorité : ajouter une source ne touche pas aux autres.
- YouTube : ID depuis `watch?v=`, `youtu.be/`, `shorts/`, `embed/`, `live/`. oEmbed `https://www.youtube.com/oembed?url=…&format=json`. Miniature `i.ytimg.com/vi/{id}/maxresdefault.jpg`, repli sur `hqdefault.jpg` si 404. Lecteur au clic (pattern façade), à envisager sur `youtube-nocookie.com`. L'ouverture comptée (règle 3) passe par `LinkOpeningRecorder`.
- GitHub : OG pour la base, plus l'API REST `/repos/{owner}/{repo}` (étoiles, langage, description) si un token est présent en variable d'environnement. Sans token, OG seul.
- X : oEmbed `publish.twitter.com/oembed`. Extraire le texte de la `blockquote` en texte brut, ne jamais injecter le `html`. Dégradation vers la carte minimale. Aucun proxy non officiel.

**Sécurité**
- `NoPrivateNetworkHttpClient` est déjà en place (`app.preview_http_client`). Ajuster : `http`/`https` seulement, 3 redirections maximum avec contrôle à chaque saut, timeout 5 s, lecture plafonnée à environ 512 Ko ou arrêt après `</head>` (aujourd'hui 1 Mo et 5 redirections), parsing seulement si `Content-Type: text/html`, User-Agent `RadarBot/1.0 (+<url>)`.
- Tests SSRF : `127.0.0.1`, `10.x`, `169.254.169.254`, `[::1]`, redirection publique → privée. Aucun appel réseau en test : `MockHttpClient`, en étendant `PreviewMockResponseFactory`.

**Parsing**
- URLs relatives résolues sur l'URL finale et sur `<base href>`. Charset : en-tête HTTP, puis `<meta charset>`, puis détection. Entités décodées.
- Troncature à environ 100 caractères (titre) et 250 (description), sans couper un mot (aujourd'hui 255 et 500 à la coupe brute).

**Images**
- Téléchargement avec les mêmes protections, une taille maximale et une vérification du type MIME réel. Redimensionnement 1.91:1 à environ 600 px de large. GD est disponible (WebP et AVIF supportés). Stockage local sur le volume du VPS (`var/` ou `public/`), dans le respect de la contrainte SQLite à instance unique.

**Asynchrone, cache, limites**
- Cache par URL normalisée : 7 jours en cas de succès, 1 h en cas d'échec (pool `cache.app`). Rate-limit par domaine cible : `symfony/rate-limiter` n'est pas installé.
- Commande CLI de (ré)enrichissement avec `--dry-run`, qui redispatche le message.
- Nouveaux champs à persister (auteur, date de publication, favicon, étoiles, langage, durée, identifiant vidéo, chemin de l'image conservée) via `make:migration`. Les colonnes existantes restent.

**Livraison par étapes reviewables (demandée)**
1. Extracteurs et resolver (tests unitaires sur fixtures HTML statiques : YouTube, GitHub, article avec JSON-LD `@graph`, page avec seulement `<title>`, page avec OG relatif ; tests de fusion par priorité).
2. Asynchrone et persistance (migration, statut horodaté, cache, rate-limit, CLI).
3. Images (téléchargement, recadrage, stockage).
4. Rendu des cartes par variante, vérifié par des E2E Playwright (`tests/E2E/`, `RealLinks`).

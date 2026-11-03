# Plan technique — Redesign « modèle 5 clair » : cartes typées, filtres en entonnoir, layout sans sidebar, écrans restylés

> **But** : figer le comment du restylage des cartes de lien (redesign « modèle 5 clair ») — forme cible, verrou de non-régression, exécution incrémentale.
> **Registre** : technique
> **Story** : `docs/story/016-r-cartes-typees/`
> **Amont** : aucun — spécification de design : `docs/redesign/SPEC.md` (§3.1, §3.2, §4.1 cartes, §4.2 cartes compactes), maquettes `docs/redesign/maquettes/liens.html` et `mobile.html`

## Motivation

La carte de lien (`templates/link/_card.html.twig`, 139 lignes, plus `_card_visual` 23, `_card_repository` 29, `_card_post` 24) est le cœur visuel de Radar, mais elle ne remplit qu'à moitié son rôle « la couleur dit tout de suite ce qu'est le lien » :

- sur 6 types (YouTube, GitHub, X, source suivie, article, image), **3 partagent le même rendu noir et blanc** (X ne se distingue que par un liseré `gray-800`, article et image sont neutres) ;
- **5 liens vers `app_link_open`** sont écrits dans les templates de carte (visuel, `_card_repository.html.twig:27`, `_card_post.html.twig:22`, titre, « Ouvrir »), soit 3 par carte rendue, alors qu'une grande partie de la carte (description, contexte, catégories) n'ouvre rien ;
- aucune animation (apparition, survol), aucun repère de nouveauté ;
- sur téléphone, chaque carte occupe un écran entier (aperçu 1.91:1 en pleine largeur).

Si on ne fait rien : la barre de filtres par type de la story 017 n'aura pas de couleurs auxquelles se raccrocher (pastille « Articles » grise, « Posts X » noire), et chaque story suivante du redesign empilera ses retouches sur une carte qui n'a pas de système de teintes complet.

Ce refacto embarque **trois changements de comportement visibles, assumés et validés au découpage du redesign** (2026-11-03) : toute la carte ouvre le lien, les pastilles de catégorie ne sont plus des liens, un badge « Nouveau » apparaît. Ils ne touchent que la vue (aucune requête, aucune donnée) ; les tests qui les verrouillaient sont adaptés explicitement (§Stratégie de test).

## Approche retenue

On garde **une seule carte** (`_card.html.twig`) et son anatomie (visuel, ligne source, titre, signature, description, contexte, catégories, pied), et on remplace sa table de thèmes à 4 entrées (youtube, github, x≈neutre, rss) par une table complète à 6 entrées où chaque type porte : liseré de carte, couleur pleine (badge, liseré du contexte, « Ouvrir », point « Nouveau »), fond de l'aperçu de repli et vignette mobile, avec leurs variantes `dark:`. Les visuels typés (`_card_visual`, `_card_repository`, `_card_post`) passent sur les fonds pâles de la maquette.

L'interaction se simplifie : le lien du titre devient l'unique lien de la carte et s'étend sur toute sa surface (stretched link) ; les couches de lien des visuels disparaissent ; seuls la façade YouTube et « Ouvrir » restent au-dessus. Le mouvement (`docs/redesign/motion.css`) entre dans `assets/styles/app.css` et s'accroche par classes (`link-card`, `link-card-media`, `link-card-open`). Sous `sm`, la même carte se réorganise en rendu compact par classes responsive, sans dupliquer de DOM.

### Forme cible

```
templates/link/
├── _card.html.twig            # table de thèmes à 6 entrées ; un seul <a> étendu ; badges type / Nouveau ; rendu compact < sm
├── _card_visual.html.twig     # fonds de repli par type (teal-50, sky-50, orange-50, stone-900…), plus de lien
├── _card_repository.html.twig # fond violet-50, texte violet, plus de lien
└── _card_post.html.twig       # fond sky-50, plus de lien
templates/components/LinkList.html.twig   # style="--i: N" sur chaque carte (apparition décalée)
assets/styles/app.css                      # tokens d'easing, keyframes, .link-card / .filter-chip, prefers-reduced-motion
```

Type d'affichage d'une carte (clé de la table de thèmes) :

```twig
{% set kind = link.source ? 'rss' : source.value %}   {# rss | youtube | github | x | article | image #}
```

| Clé | Couleur pleine | Liseré (`ring`) | Fond aperçu / vignette | Libellé du badge |
|---|---|---|---|---|
| `youtube` | `rose-600` | `rose-200` | `stone-900` | `Vidéo YouTube` |
| `github` | `violet-600` | `violet-200` | `violet-50` | `Dépôt GitHub` |
| `x` | `sky-700` | `sky-200` | `sky-50` | `Post X` |
| `rss` | `orange-700` | `orange-200` | `orange-50` | `Source suivie` |
| `article` | `teal-700` | `teal-200` | `teal-50` | `Article` |
| `image` | `gray-900` | `gray-200` | `gray-100` | `Image` |

Libellés : `SourceType::label()` existant, plus « Source suivie » pour `rss` (une carte de source suivie prend l'orange quelle que soit sa plateforme, règle actuelle de `_card.html.twig`). Variantes sombres : même logique qu'aujourd'hui (liseré `<couleur>-500/30`, texte `<couleur>-400`, fonds `<couleur>-500/10`).

Structure d'interaction :

```html
<article class="link-card relative …" data-test="link-card" style="--i: N">
  <div class="link-card-media …">            <!-- visuel ; caché < sm -->
    <div class="relative z-10" data-test="link-video"><button data-test="link-video-play">…</button></div>   <!-- façade YouTube, au-dessus : z-10 sur le bloc, l'aperçu agrandi au survol (transform) enfermerait celui du bouton -->
    <span class="pointer-events-none z-10" data-test="link-type">…</span>  <span class="pointer-events-none z-10" data-test="link-new">…</span>
  </div>
  <span data-test="link-thumb" class="sm:hidden">…</span>   <!-- vignette 64 × 64 < sm -->
  <h2 data-test="link-title"><a href="app_link_open" target="_blank" class="after:absolute after:inset-0">…</a></h2>
  …
  <span data-test="link-category">…</span>   <!-- plus un lien -->
  <a data-test="link-open" class="link-card-open relative z-10" href="app_link_open">Ouvrir ↗</a>
</article>
```

Badge « Nouveau » : `link.postedAt > date('-72 hours')`, calcul d'une ligne dans le template (pas de service pour une comparaison de dates ; la règle « logique hors entité » exclut une méthode sur `Link`). Un lien repris de Chat garde sa date d'origine : il n'est pas « nouveau ».

Rendu compact (< `sm`) : vignette 64 × 64 teintée (icône du type, pas d'image), ligne « type · nouveau » dans la couleur du type, titre et contexte limités à 2 lignes, ligne source · date ; visuel, description, signature, catégories et pied masqués (`hidden sm:…`). La façade n'existe qu'à partir de `sm` : en compact, toute la carte ouvre YouTube.

### Mécanismes mobilisés

- **Remplacement de données par une table** (table de thèmes) : la teinte est déjà pilotée par une table Twig ; on la complète au lieu d'ajouter des conditions, une seule source de couleurs pour la carte.
- **Stretched link** (pseudo-élément `after:absolute after:inset-0` sur le lien du titre) : une carte entièrement cliquable sans imbriquer de `<button>` ou d'`<a>` dans un `<a>` (HTML invalide), avec un seul lien annoncé aux lecteurs d'écran.
- **Classes de composant Tailwind** (`@layer components` dans `app.css`) : `motion.css` intégré, tokens dans `@theme`, neutralisé sous `prefers-reduced-motion` ; adapté à la cascade de Tailwind 4, où les utilitaires l'emportent sur `@layer components` : apparition en `backwards` (un `both` figerait `transform` et bloquerait la montée au survol), ombre de survol via `--tw-shadow` (composée avec le `ring-1` au lieu de l'écraser), écart de « Ouvrir » porté par `.link-card-open` plutôt qu'un utilitaire `gap-*`.
- **Rendu responsive par variantes** (`sm:`) : un seul DOM pour bureau et mobile, donc aucun `data-test` dupliqué.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Envelopper toute la carte dans un `<a>` (comme la maquette) | HTML invalide avec la façade (`<button>`) et « Ouvrir » (`<a>`) à l'intérieur ; comportement imprévisible au clic. |
| Template séparé `_card_compact.html.twig` pour le mobile | Deux DOM dans la page (l'un masqué) : `data-test` dupliqués, comptages de tests faussés, deux cartes à maintenir. |
| Extraire les thèmes dans une extension Twig / un enum PHP | Usage unique au départ. Toujours non extrait après l'extension du périmètre : le carré de la pastille Type (`LinkList`) et le badge du type détecté (`LinkForm`) portent chacun leur table de couleurs pleines, à côté de `theme.solid` de la carte. |
| Ratios d'aperçu par type (16:9, 3.2:1, 2.4:1… de la maquette) | Les og:image sont au format 1.91:1 : les rogner perdrait du contenu ; ratio actuel conservé. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `tests/E2E/CardOpenTest.php` | Caractérisation : un clic sur le visuel (article, dépôt, post X) ouvre le lien ; puis clic dans le corps de la carte, rendu compact à 390 px, absence d'animation sous `prefers-reduced-motion` |
| `tests/Controller/LinkCardBadgeTest.php` | Badge de type par variante (dont « Source suivie »), badge « Nouveau » présent à 71 h et absent à 73 h, catégories non cliquables |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `templates/link/_card.html.twig` | Table de thèmes 4 → 6 entrées ; lien du titre étendu ; catégories en `<span>` neutres ; badges type et Nouveau ; « Ouvrir » au style de la maquette ; rendu compact < `sm` |
| `templates/link/_card_visual.html.twig` | Fonds de repli par type de la spec ; retrait de tout lien |
| `templates/link/_card_repository.html.twig` | Fond `violet-50` / texte violet (dark décliné) ; retrait du lien `absolute inset-0` |
| `templates/link/_card_post.html.twig` | Fond `sky-50` (dark décliné) ; retrait du lien `absolute inset-0` |
| `templates/components/LinkList.html.twig` | `style="--i: {{ loop.index0 }}"` sur chaque carte |
| `assets/styles/app.css` | Intégration de `docs/redesign/motion.css` (tokens, keyframes, `.link-card`, `.filter-chip`, reduced motion) |
| `tests/Controller/CategoryControllerTest.php` | `testCardOpensOriginalLinkAndLinksToCategories` : la pastille n'est plus un lien (assertion inversée, test renommé) |
| `tests/Controller/EnrichedLinkWebTestCase.php` | `enrichedLink()` accepte un `postedAt` facultatif (badge Nouveau) |
| `tests/Controller/LinkCardVariantsTest.php` | Assertion du badge de type sur chaque variante existante |

### Fichiers ajoutés par l'extension du périmètre

| Fichier | Rôle |
|---|---|
| `src/Enum/Type/FeedType.php` | Type d'un lien dans le filtre du fil (plateforme, ou source suivie qui prime) |
| `src/Search/LinkFilter.php` | Filtres cumulés : recherche, équipe, catégorie, type |
| `src/Repository/LinkFacetRepository.php` | Compteurs en entonnoir (par équipe, catégorie, type) sur la base de `LinkRepository::filtered()` |
| `templates/admin/_tabs.html.twig` | Onglets d'administration |
| `tests/Controller/LinkFilterTest.php`, `tests/Controller/AdminTabsTest.php`, `tests/E2E/FilterTest.php` | Filtres, onglets admin, filtre Type et bouton flottant en E2E |
| Modifiés | `src/Repository/LinkRepository.php` (`findFiltered()` remplace `findLatest()` / `findByCategory()`), `src/Twig/Components/LinkList.php`, `src/Twig/Components/LinkForm.php` (`getDetectedType()`), `src/Twig/LinkExtension.php` (`link_title`), `src/Form/LinkType.php` (focus sur l'adresse), `src/Controller/PageController.php`, `src/Controller/CategoryController.php`, `templates/base.html.twig`, `templates/components/LinkForm.html.twig`, `templates/components/_category_picker.html.twig`, `templates/link/{new,seed,show,_search_empty}.html.twig`, `templates/page/index.html.twig`, `templates/category/show.html.twig`, les templates admin, extension et connexion, `DESIGN.md` |
| Supprimés | `src/Twig/Components/CategoryNav.php`, `templates/components/CategoryNav.html.twig`, `tests/Controller/CategoryNavTest.php` |

### Clients identifiés

- **Vues** — `components/LinkList.html.twig` (via `_list_card` et `PendingLinkCard`), `link/show.html.twig`, `link/_duplicate.html.twig` (carte en doublon dans la modale d'ajout) : impactées, elles héritent du nouveau rendu sans changement de leur côté (sauf `--i` dans `LinkList`).
- **Contrôleurs Stimulus** — `video-facade` (inchangé, même contrôleur et mêmes valeurs), `image-fallback` (inchangé, mêmes cibles), `masonry` (inchangé : il mesure la hauteur, que `transform` n'affecte pas).
- **Live Components** — morphing de `LinkList` à la recherche : les cartes conservées restent les mêmes nœuds (pas de rejouage de l'apparition) ; `PendingLinkCard` (polling) inchangé.
- **Tests fonctionnels** — `LinkCardTest`, `LinkCardVariantsTest`, `LinkControllerTest`, `FeedTest`, `LinkSearchTest`, `LinkPlayTest`, `LinkDuplicateTest`, `SourceLinkTest` (classe `ring-orange-200`), `AdminUserStatusTest`, `LinkSeedTest` : non impactés (mêmes `data-test`) ; `CategoryControllerTest` impacté (une assertion).
- **Tests E2E** — `RichCardTest`, `ExploreCategoryTest`, `AddLinkTest`, `SourceTest`, `SearchTest`, `ExtensionTest` : non impactés à condition que `link-open` et `link-video-play` restent cliquables au-dessus de la couche du lien (`z-10`).
- **Configuration** — aucune.

## Hors scope

- ~~**Titres de repli lisibles**~~ : intégrés (§Extension du périmètre).
- ~~**Barre de filtres, suppression de la sidebar, mise en page mobile**~~ : intégrées (§Extension du périmètre).
- ~~**Écrans secondaires**~~ : intégrés (§Extension du périmètre), sauf les tuiles radio de l'équipe (« Nouveau compte »).
- ~~**Mise à jour de `DESIGN.md`**~~ : faite (§Extension du périmètre).
- **Date relative dans le pied** (« aujourd'hui », « hier » de la maquette) : non listée par la spec, format `d/m/Y` conservé.
- **Badge « Nouveau » depuis la dernière visite** : écarté (fenêtre fixe 72 h retenue, rien à stocker).

## Impacts transverses

- **Cloisonnement des données** : inchangé (aucune requête modifiée).
- **Déclinaisons / thèmes** : mode sombre décliné pour chaque nouvelle teinte, même logique que l'existant.
- **Traduction / i18n** : inchangé (libellés en dur en français, convention actuelle du projet).
- **API / exposition externe** : inchangé.
- **Droits d'accès** : inchangé.
- **Emails / notifications** : inchangé.
- **Migration de données** : aucune.

## Comportement externe à préserver

- **URLs** : inchangées ; toute ouverture passe par `app_link_open` (`/liens/{id}/ouvrir`), nouvel onglet, `rel="noopener noreferrer"`.
- **Noms de routes** : inchangés (`app_link_open`, `app_link_play`, `app_media`, `app_link_show`).
- **Signatures publiques** : aucune signature PHP touchée ; filtres Twig (`link_host`, `youtube_id`, `github_repository`, `x_handle`) inchangés.
- **Droits d'accès** : inchangés.
- **Cloisonnement** : inchangé.
- **Effets de bord** : comptage des ouvertures inchangé — un clic sur la carte ou « Ouvrir » passe par `app_link_open`, la lecture de la façade par `app_link_play` (POST avec jeton `link-play`).
- **Vues rendues** : mêmes templates, mêmes variables ; `id="link-card-{id}"`, `data-preview-status` et tous les `data-test` existants conservés (`link-card`, `link-title`, `link-source`, `link-byline`, `link-author`, `link-published-at`, `link-description`, `link-context`, `link-category`, `link-auto`, `link-source-name`, `link-poster`, `link-date`, `link-seeded`, `link-open`, `link-video`, `link-video-play`, `link-video-player`, `link-repository`, `link-repository-owner`, `link-repository-name`, `link-stars`, `link-language`, `link-post`, `link-handle`, `link-card-fallback`, `link-card-favicon`, `link-card-image`) ; `link-open` garde son `title="Ouvrir <url>"` ; le liseré d'une carte de source suivie garde la classe `ring-orange-200`.
- **Messages utilisateur** : inchangés (libellés de type, « Ouvrir », « Publié automatiquement depuis la source … »).
- **Changements assumés** (validés) : carte entièrement cliquable ; `link-category` n'est plus un lien ; badge « Nouveau » (< 72 h) ; teintes X (`sky`) et Article (`teal`) ; visuels éclaircis ; rendu compact < `sm`.
- **Changements assumés par l'extension du périmètre** (demandée le 2026-11-03 — ce n'est plus un refacto pur) : paramètres `team` et `type` sur `/` et `/categories/{id}` (valeur inconnue → 404) ; sidebar et `category-nav*` supprimés, catégories dans la barre de filtres (catégories sans lien affichées à 40 %) ; titre de carte sans aperçu = repli lisible au lieu de l'adresse ; formulaire d'ajout dans l'ordre adresse › catégories › contexte, focus sur l'adresse ; textes d'aide de l'ajout, de la reprise et de la recherche vide reformulés ; identifiants de test de la connexion affichés en dev seulement ; contenu en pleine largeur (pas de `max-w-[1320px]` de la maquette, pour garder ≥ 5 colonnes à 2560 px).

## Stratégie de test

### Tests existants utilisés comme filet

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `tests/Controller/LinkCardTest.php` | functional | Titre, ligne source (« Article », « Dépôt GitHub »), catégorie, repli |
| `tests/Controller/LinkCardVariantsTest.php` | functional | Façade YouTube (id, image, bouton), dépôt (propriétaire, nom), post X (auteur, texte), favicon du repli |
| `tests/Controller/CategoryControllerTest.php` | functional | `link-open` : href `/liens/{id}/ouvrir`, title, target, rel ; titre et « Ouvrir » vers la même URL |
| `tests/Controller/SourceLinkTest.php` | functional | Carte de source suivie : nom de la source, pas de posteur, liseré `ring-orange-200` |
| `tests/Controller/LinkPlayTest.php` | functional | Jeton de la façade et comptage de la lecture |
| `tests/Controller/LinkControllerTest.php`, `FeedTest.php`, `LinkSearchTest.php`, `LinkDuplicateTest.php` | functional | Posteur, catégories (comptage), contexte, pagination, recherche, carte en doublon |
| `tests/E2E/RichCardTest.php` | e2e | Lecture de la façade dans la carte ; une colonne à 390 px sans débordement ; ≥ 5 colonnes et cartes ≤ 600 px à 2560 px |
| `tests/E2E/ExploreCategoryTest.php`, `AddLinkTest.php` | e2e | Clic sur `link-open` → popup vers l'URL d'origine |
| `tests/E2E/SearchTest.php` | e2e | Les cartes restent les mêmes nœuds au fil de la recherche (morphing) |

### Tests de caractérisation

| Test à créer | Comportement à verrouiller | Type |
|---|---|---|
| `tests/E2E/CardOpenTest.php` (`testClickingTheVisualOpensTheLink`) | Un clic sur le visuel d'une carte article (image ou repli), d'un dépôt GitHub et d'un post X ouvre un popup vers l'URL d'origine — aujourd'hui assuré par les couches de lien des visuels, qui vont disparaître. Clic en `force: true` (souris à la position du visuel) : le lien étendu recouvre le visuel et Playwright refuse un clic sur un élément recouvert ; vert sur les anciens et les nouveaux templates | e2e |

**Règle absolue** : aucun code de production touché tant que ces tests ne sont pas écrits, verts et committés.

Tests des changements assumés, écrits dans l'étape qui les introduit :

| Test | Comportement nouveau | Type |
|---|---|---|
| `CardOpenTest::testClickingTheCardBodyOpensTheLink` | Un clic sur le contexte ou la ligne source ouvre le lien | e2e |
| `CardOpenTest::testPhoneShowsCompactCards` | À 390 px : `link-thumb` visible, visuel masqué, un clic sur la carte ouvre le lien | e2e |
| `CardOpenTest::testCardsAreNotAnimatedWithReducedMotion` | Sous `prefers-reduced-motion: reduce`, `animation-name` de `.link-card` vaut `none` | e2e |
| `LinkCardBadgeTest` | `link-type` porte le libellé attendu pour chaque variante (dont « Source suivie ») ; `link-new` présent à −71 h, absent à −73 h (« `link-category` n'est pas un `<a>` » vérifié par `CategoryControllerTest` seul) | functional |
| `CategoryControllerTest` (adapté) | Le test qui cliquait sur la pastille vérifie désormais qu'elle n'est pas un lien | functional |

**Hors scope tests** :

- Rendu exact des couleurs et du mode sombre : vérification visuelle (captures en clair et en sombre, bureau et 390 px), pas de test sur les classes Tailwind, sauf le `ring-orange-200` déjà verrouillé.
- Animations de survol : vérification visuelle.

## Ordre d'exécution

1. [x] **Test de caractérisation**
   - Objectif : verrouiller « un clic sur le visuel ouvre le lien » avant de retirer les couches de lien.
   - Fichiers : `tests/E2E/CardOpenTest.php` (créé).
   - Vérification : `make playwright` vert, test committé. Fait sans commit : l'utilisateur a choisi de ne rien commiter avant la fin.
   - Commitable seule : oui.

2. [x] **Mouvement dans `app.css`, inutilisé**
   - Objectif : tokens d'easing, keyframes, classes `.link-card` / `.filter-chip` et neutralisation `prefers-reduced-motion` disponibles.
   - Fichiers : `assets/styles/app.css`.
   - Vérification : build Tailwind sans erreur ; aucune carte ne change (classes non posées) ; `make lint` vert.
   - Commitable seule : oui.

3. [x] **Couleurs par type et visuels éclaircis**
   - Objectif : table de thèmes à 6 entrées (clair + sombre), fonds de repli de la spec, dépôt sur `violet-50`, post sur `sky-50`, « Ouvrir » texte coloré + flèche, catégories neutres `gray-100`.
   - Fichiers : `_card.html.twig`, `_card_visual.html.twig`, `_card_repository.html.twig`, `_card_post.html.twig`.
   - Vérification : `make phpunit` et `make playwright` verts sans modifier de test ; captures clair/sombre de chaque type.
   - Commitable seule : oui.

4. [x] **Carte entièrement cliquable**
   - Objectif : lien du titre étendu ; retrait des couches de lien des visuels ; façade et « Ouvrir » en `relative z-10` ; catégories en `<span>`.
   - Fichiers : les 4 templates de carte ; `tests/Controller/CategoryControllerTest.php` ; `CardOpenTest::testClickingTheCardBodyOpensTheLink`.
   - Vérification : caractérisation (étape 1) toujours verte ; `RichCardTest` (façade), `ExploreCategoryTest` et `AddLinkTest` (clic sur `link-open`) verts.
   - Commitable seule : oui.

5. [x] **Badges type et « Nouveau »**
   - Objectif : badge de type en haut à gauche de l'aperçu (couleur pleine, texte blanc, capitales 11 px) ; badge « Nouveau » en haut à droite (< 72 h, point de la couleur du type avec `animate-radar-ping`).
   - Fichiers : `_card.html.twig` ; `tests/Controller/LinkCardBadgeTest.php` ; `EnrichedLinkWebTestCase.php` ; `LinkCardVariantsTest.php`.
   - Vérification : `make phpunit` vert.
   - Commitable seule : oui.

6. [x] **Animations branchées**
   - Objectif : classes `link-card`, `link-card-media`, `link-card-open` posées ; `--i` dans la boucle de `LinkList` (plafonné à 10 par le CSS).
   - Fichiers : `_card.html.twig`, `components/LinkList.html.twig` ; `CardOpenTest::testCardsAreNotAnimatedWithReducedMotion`.
   - Vérification : `RichCardTest` (masonry, colonnes) et `SearchTest` (morphing) verts ; test reduced motion vert.
   - Commitable seule : oui.

7. [x] **Rendu compact mobile**
   - Objectif : sous `sm`, vignette 64 × 64 teintée, type · nouveau, titre et contexte sur 2 lignes, source · date ; le reste masqué ; pas de façade.
   - Fichiers : `_card.html.twig` ; `CardOpenTest::testPhoneShowsCompactCards`.
   - Vérification : `RichCardTest::testCardsFitAPhoneScreen` et le nouveau test verts ; captures à 390 px clair/sombre.
   - Commitable seule : oui.

8. [x] **Vérification finale**
   - Objectif : passe visuelle complète (fil, catégorie, page d'un lien, carte en doublon dans la modale), clair et sombre, bureau et 390 px.
   - Fichiers : aucun (corrections éventuelles dans les templates ci-dessus).
   - Vérification : `make lint`, `make phpunit`, `make playwright` verts.
   - Commitable seule : oui.

Pas de coexistence ancien/nouveau : chaque étape est elle-même un toggle par revert.

## Critères de sortie

- [x] Le test de caractérisation passe **avant ET après** chaque étape.
- [x] `make phpunit` et `make playwright` verts ; tests supprimés ou modifiés seulement pour les changements assumés (pastille non cliquable, puis ceux de l'extension du périmètre : `CategoryNavTest` retiré, assertions listées en §Extension du périmètre).
- [x] `make lint` vert (Mago : zéro finding).
- [x] Une seule occurrence de `app_link_open` couvrant la carte en plus de « Ouvrir » : `grep -c "app_link_open" templates/link/_card*.html.twig` donne 2 au total (contre 5 aujourd'hui).
- [x] Aucun `<a>` dans les pastilles de catégorie de la carte : `grep -n 'data-test="link-category"' templates/link/_card.html.twig` ne montre qu'un `<span>`.
- [ ] Les 6 types ont chacun leur liseré distinct (captures clair et sombre jointes à la review).
- [x] Sous `prefers-reduced-motion: reduce`, aucune carte n'est animée (test E2E).
- [ ] Chaque étape committée est déployable seule (revert atomique possible).

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| La couche du lien étendu intercepte les clics sur la façade ou « Ouvrir » (Playwright : « element intercepts pointer events ») | élevée | `relative z-10` sur `link-video-play` et `link-open` ; `RichCardTest` et `ExploreCategoryTest` exécutés à l'étape 4 |
| L'animation d'apparition se rejoue à chaque frappe de recherche (Live) ou à chaque polling de `PendingLinkCard` | moyenne | Le morphing conserve les nœuds (verrouillé par `SearchTest`) ; vérifier visuellement la recherche et une carte en attente |
| Le `transform` au survol gêne la masonry ou la mesure des hauteurs | faible | `getBoundingClientRect().height` n'est pas affecté par `translateY` ; `RichCardTest` (colonnes) exécuté à l'étape 6 |
| Classes Tailwind composées dynamiquement non générées | moyenne | Toutes les classes écrites en entier dans la table de thèmes (pas de concaténation `'bg-' ~ couleur`) |
| Contraste insuffisant du texte coloré (fond blanc ou sombre) | faible | Teintes 600/700 en clair, 300/400 en sombre (spec §3.1) ; contrôle visuel |
| Texte de la carte non sélectionnable à cause de la couche du lien | moyenne | Accepté : l'intention est « ouvrir en un clic » ; l'URL reste dans le `title` de « Ouvrir » |
| Badge « Nouveau » instable dans les tests (heure réelle) | faible | Marges de ±1 h autour de 72 h (liens créés à −71 h et −73 h) |

## Extension du périmètre (2026-11-03)

À la demande de l'utilisateur, le reste du redesign (stories 017, 018 et 019 prévues en « Hors scope ») est regroupé dans cette story, enchaîné sans validation intermédiaire, tests lancés à la fin uniquement :

- **Filtres en entonnoir (ex-017)** : `LinkList` gagne `team` et `type` (`LiveProp(url: true)`), lus aussi par `PageController` et `CategoryController` (`MapQueryParameter`, valeur inconnue → 404). Enum `FeedType` (type dans le fil : plateforme, ou source suivie qui prime), objet `Search\LinkFilter`, `LinkRepository::findFiltered()` remplace `findLatest()` et `findByCategory()`, compteurs dans `LinkFacetRepository`. Les pastilles sont des liens (catégorie = sa route), pas des actions live.
- **Layout (ex-017)** : sidebar et composant `CategoryNav` supprimés (test `CategoryNavTest` retiré, ses cas portés sur la barre de filtres dans `CategoryControllerTest`) ; topbar blanche collante ; mobile : recherche pleine largeur, rangées de filtres qui défilent, bouton flottant `add-link-fab`.
- **Titres de repli lisibles (ex-019)** : filtre Twig `link_title` (`@compte sur X`, `@chaîne` YouTube, `propriétaire/dépôt` GitHub — ajout au-delà de la spec —, sinon le domaine).
- **Écrans secondaires (ex-018)** : modale d'ajout (adresse d'abord, type détecté via `LinkForm::getDetectedType()`, coche sur la catégorie choisie), reprise de Chat (encart d'information), onglets d'administration (`admin/_tabs.html.twig`), pages Sources, Comptes, Nouveau/Modifier un compte, Catégories, Extension, Connexion (identifiants de test en dev uniquement). Radio en tuiles pour l'équipe non fait : `AdminUserTest` sélectionne l'équipe dans le select.
- **Mouvement** : menu du compte (`animate-radar-drop` sur son contenu, Flowbite positionnant le menu par transform), modale d'ajout (ressort 280 ms), badge du type détecté (`animate-radar-badge`).
- **`DESIGN.md`** mis à jour (couleurs par type, barre de filtres, layout sans sidebar, abandon du panneau de détail).

Tests adaptés (comportements assumés) : `CategoryControllerTest` (barre de filtres au lieu de la sidebar, ordre vérifié par l'adresse d'« Ouvrir » puisque le titre de repli est le domaine), `LinkCardTest` (titre de repli d'un dépôt), `AddLinkTest` (ordre adresse › catégories › contexte, focus sur l'adresse), `ExploreCategoryTest` et `SearchTest` (catégorie choisie dans la barre de filtres), `FeedTest`, `LinkControllerTest`, `LinkSearchTest`, `LinkDuplicateTest` (adresse lue dans le `title` d'« Ouvrir » et non plus dans le titre de carte), `SourceControllerTest` (entrées d'administration présentes une seule fois — review [TEST]). Nouveaux : `LinkFilterTest`, `AdminTabsTest`, `FilterTest` (E2E), `LinkCardVariantsTest::testLinkWithoutTitleShowsReadableFallback`, `AddLinkTest::testTypedAddressShowsItsDetectedType`, `SecurityControllerTest` (identifiants masqués hors dev).

## Questions ouvertes

_(aucune — les 6 décisions du redesign et les 2 décisions propres à cette story ont été tranchées le 2026-11-03.)_

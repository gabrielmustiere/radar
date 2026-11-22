# Plan technique — Installer Radar comme une app sur son téléphone et y partager un lien depuis Android

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `forge/story/021-f-app-mobile-installable/`
> **Amont** : `pitch.md`

## Approche retenue

Radar devient une PWA sans changer d'architecture. Le monolithe Twig, Turbo et Live Components reste tel quel. Trois fichiers statiques s'ajoutent dans `public/` :
- un manifeste, qui porte l'identité de l'app, ses icônes et la cible de partage Android ;
- un service worker, qui ne fait que le repli hors ligne ;
- une page hors ligne autonome.

Ils sont statiques **parce que** `access_control` impose `ROLE_USER` sur `^/`. Le navigateur charge le manifeste sans cookie, et la page hors ligne doit s'afficher sans session. Les servir depuis `public/` les sort de Symfony, sans ouvrir de règle `PUBLIC_ACCESS`. Le layout (`base.html.twig` et `security.html.twig`, par un partial commun) déclare le manifeste, les couleurs système et l'icône iOS. Il applique aussi les marges de zone de sécurité.

Le partage Android cible directement l'action d'ajout existante, `GET /liens/ajouter?url&text&title`. `LinkController::new()` reconnaît un partage à la présence de l'un de ces paramètres et délègue l'extraction du lien à `SharedLinkExtractor`. Il préremplit alors `LinkSubmission` et y pose le canal `share`. Le canal circule ensuite dans un champ caché de `LinkType`. Il survit ainsi aux rendus du Live Component `LinkForm` et arrive à la soumission POST habituelle. `LinkManager::add()` l'enregistre sur `Link`, et l'API de l'extension pose `extension`. Les reprises (`seed`) et les articles de source (`publish`) n'ont pas de canal : la soumission le porte à null (`HistoricalLinkSubmission`, `publish()`). Enfin, un contrôleur Stimulus pilote l'entrée « Installer l'app » du menu : invite native si le navigateur l'a fournie, sinon une page d'aide authentifiée sur le modèle de `/extension`.

### Mécanismes mobilisés

- **Web App Manifest + `share_target` (GET)** : c'est le seul moyen pour une PWA d'apparaître dans le menu Partager d'Android. Le GET suffit, puisqu'on ne reçoit pas de fichier, et il retombe sur l'action d'ajout existante sans nouvelle route.
- **Service worker minimal** (`install` met en cache `/offline.html`, `activate` purge les autres caches, `fetch` agit seulement sur `request.mode === 'navigate'` en réseau d'abord, avec la page hors ligne en repli) : c'est le strict besoin de la règle 8 du pitch. Aucune autre requête n'est interceptée, donc pas d'asset ni de HTML authentifié périmé.
- **Champ caché + `CallbackTransformer`** dans `LinkType` (enum vers chaîne, et au retour `LinkChannel::tryFrom()` avec repli sur `Web`) : le Live Component recrée le formulaire à partir de la saisie en cours (`LinkForm::instantiateForm()`), donc une donnée qui doit survivre aux rendus doit faire partie du formulaire. `form_end` rend déjà les champs restants (`templates/components/LinkForm.html.twig:65`).
- **Backed string enum** `LinkChannel` dans `src/Enum/Type/` : convention du projet (`CLAUDE.md`), même forme que `SourceType`.
- **Chemin cible de `form_login`** (`always_use_default_target_path: false` par défaut) : un membre déconnecté qui partage est renvoyé, après connexion, vers l'URL demandée, query string comprise. Rien à configurer.
- **Contrôleur Stimulus** `pwa-install` : il garde au niveau du module l'événement `beforeinstallprompt` (le contrôleur est chargé tôt, sans `stimulusFetch: 'lazy'`) pour qu'il survive aux visites Turbo. Il se masque si `matchMedia('(display-mode: standalone)')` ou `navigator.standalone` le demande.
- **Utilitaires Tailwind en valeurs arbitraires** (`env(safe-area-inset-*)`) avec `viewport-fit=cover` : les marges de l'encoche et de la barre d'accueil, sans CSS dédié.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Réécriture en SPA, avec une API complète | Turbo donne déjà la navigation sans rechargement. Ce serait refaire tout le front et exposer tout le domaine en API pour aucun gain à l'installation. |
| Coque native (Hotwire Native, Capacitor) et stores | Seul gain : le partage iOS natif. En échange, compte Apple Developer, distribution interne, code Swift et Kotlin à maintenir. Hors scope du pitch. |
| Route dédiée `/liens/partager` avec une LiveProp `shared` sur `LinkForm` | Canal non modifiable côté client, mais une deuxième action quasi identique à `new()` et un changement de l'adresse de soumission. Pour une statistique agrégée, l'intégrité ne justifie pas ce coût. |
| Manifeste, service worker et page hors ligne servis par des routes Symfony | Ces routes sont bloquées par `ROLE_USER` sur `^/`. Il faudrait des exceptions `PUBLIC_ACCESS`, sans valeur ajoutée par rapport à des fichiers statiques. |
| Détecter le navigateur (user agent) pour afficher l'entrée de menu | Détection fragile (iPadOS se présente comme macOS), et l'entrée manquerait sur Android quand Chrome n'émet pas l'invite. Une entrée toujours visible hors standalone, qui retombe sur la page d'aide, couvre tous les cas. |
| Mettre en cache les assets AssetMapper dans le service worker | Gain de vitesse non demandé, et une purge des versions à gérer. |
| Ajouter dès maintenant une méthode de comptage par canal | Rien ne la consommerait avant `indicateurs-mensuels` (`CLAUDE.md` : rien de spéculatif). |

## Modèle de données

### Nouvelle enum `LinkChannel`

`src/Enum/Type/LinkChannel.php` : backed string enum, cases `Web = 'web'`, `Extension = 'extension'`, `Share = 'share'`.

### Modification de `Link`

`src/Entity/Link.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `channel` | `LinkChannel` (colonne `channel`, `length: 20`, `enumType`) | oui | null pour les liens antérieurs, les reprises (`seeded`) et les articles de source (`source`). Pas d'index : aucune requête ne filtre dessus pour l'instant. |

Le constructeur gagne un dernier paramètre `?LinkChannel $channel = null`, et un getter `getChannel()` s'ajoute. La migration est générée par `symfony console make:migration` : un `ADD COLUMN` nullable, sans reprise de données. SQLite le gère sans recréer la table.

### Modification de `LinkSubmission`

`src/Form/Model/LinkSubmission.php` : `public ?LinkChannel $channel = LinkChannel::Web;` est un DTO, pas une donnée persistée telle quelle. `HistoricalLinkSubmission` le redéclare à `null`, et `publish()` le pose à `null` : `LinkManager::create()` recopie le canal de la soumission sans condition.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Enum/Type/LinkChannel.php` | Canal d'entrée d'un ajout : web, extension, partage. |
| `migrations/Version20261122150027.php` | Ajout de la colonne nullable `link.channel` (générée par `make:migration`). |
| `public/manifest.json` | Manifeste : `id`, `name`/`short_name` « Radar », `lang: fr`, `start_url`/`scope` `/`, `display: standalone`, `theme_color`/`background_color` `#ffffff`, icônes, `share_target` GET vers `/liens/ajouter` (`title`, `text`, `url`). Extension `.json` plutôt que `.webmanifest` pour un type MIME connu de tous les serveurs. |
| `public/sw.js` | Service worker de repli hors ligne, cache nommé avec une constante de version (`radar-offline-v1`). |
| `public/offline.html` | Page hors ligne autonome (CSS et logo SVG en ligne, bouton « Réessayer »), aucune donnée. `data-test="offline-page"`. |
| `public/icons/icon-192.png`, `public/icons/icon-512.png` | Icônes `purpose: any`, rasterisées une fois depuis `public/logo.svg`. |
| `public/icons/icon-maskable-512.png` | Icône `purpose: maskable` : fond `#1c1917` pleine surface, motif dans la zone sûre de 80 %. |
| `public/icons/apple-touch-icon.png` | Icône iOS 180 px pleine surface (iOS arrondit lui-même les coins). |
| `templates/common/_pwa_head.html.twig` | Balises de tête communes aux deux layouts : manifeste, `theme-color` clair `#ffffff` et sombre `#1c1917` (`stone-900`, le `gray-900` du thème), `apple-touch-icon`, `apple-mobile-web-app-title`. |
| `assets/controllers/pwa_install_controller.js` | Entrée « Installer l'app » : masquée en standalone, invite native gardée au niveau du module, sinon navigation vers la page d'aide. |
| `src/Controller/InstallController.php` | `GET /installer` (`app_install`), page d'aide authentifiée. |
| `templates/install/index.html.twig` | Procédures iPhone (Safari, Partager, Sur l'écran d'accueil, avec la phrase sur la reconnexion et « Se souvenir de moi »), Android (menu, Installer l'application, puis Radar dans Partager) et ordinateur (icône d'installation de la barre d'adresse). |
| `src/Service/SharedLinkExtractor.php` | Extraction du lien partagé : `url` s'il contient un lien http(s) valide, sinon le premier `https?://…` de `text` puis de `title`, ponctuation finale retirée (parenthèse gardée si le lien l'ouvre), et null si rien. |
| `tests/Service/SharedLinkExtractorTest.php` | Unitaire : cas d'extraction du lien partagé. |
| `tests/Service/LinkManagerChannelTest.php` | Unitaire : canal enregistré par `add()`, null pour `seed()` et `publish()`. |
| `tests/Controller/LinkShareTest.php` | Fonctionnel : préremplissage, partage sans lien, doublon, membre déconnecté, canaux à la soumission. |
| `tests/Controller/InstallControllerTest.php` | Fonctionnel : page d'aide pour un membre, redirection vers la connexion pour un anonyme, balises de tête PWA présentes sur une page authentifiée et sur la page de connexion. |
| `tests/E2E/PwaTest.php` | E2E : manifeste lié et valide, icônes servies, service worker actif, page hors ligne, entrée de menu visible ou masquée en standalone, parcours partage, rendu Live, ajout. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Entity/Link.php` | Ajouter la colonne `channel` nullable, le paramètre de constructeur et le getter. |
| `src/Form/Model/LinkSubmission.php` | Ajouter `?LinkChannel $channel`, par défaut `LinkChannel::Web`. |
| `src/Form/Model/HistoricalLinkSubmission.php` | Redéclarer `$channel` à `null` : une reprise n'a pas de canal. |
| `src/Form/LinkType.php` | Ajouter le champ `channel` en `HiddenType` avec un `CallbackTransformer` (valeur inconnue → `Web`). |
| `src/Service/LinkManager.php` | `create()` transmet `$submission->channel` à `Link`. `publish()` pose le canal de sa soumission à null. |
| `src/State/ExtensionLinkProcessor.php` | Poser `LinkChannel::Extension` sur la soumission. |
| `src/Controller/LinkController.php` | Dans `new()`, en GET avec `url`, `text` ou `title` : préremplir l'URL extraite, poser `LinkChannel::Share`, passer au template l'indicateur « partage sans lien ». |
| `templates/link/new.html.twig` | Alerte « Aucun lien trouvé dans le partage, colle l'URL » (`data-test="share-no-link"`) quand l'indicateur est vrai. |
| `templates/base.html.twig` | Inclure `_pwa_head`, ajouter `viewport-fit=cover`, la marge `env(safe-area-inset-top)` sur la topbar et `bottom: calc(1rem + env(safe-area-inset-bottom))` sur le bouton flottant, et l'entrée « Installer l'app » (`data-controller="pwa-install"`, `data-test="install-app-link"`) dans le menu utilisateur, à côté de « Extension navigateur ». |
| `templates/security.html.twig` | Inclure `_pwa_head` et ajouter `viewport-fit=cover`, pour que l'installation depuis la page de connexion porte l'icône et que l'app installée s'ouvre correctement sur la connexion. |
| `assets/app.js` | Enregistrer `/sw.js` si `serviceWorker` est disponible. |
| `templates/common/flash-messages.html.twig` | Marge basse `env(safe-area-inset-bottom)` : les messages restent au-dessus de la barre d'accueil. |
| `tests/Controller/LinkWebTestCase.php` | Helper `linkByUrl()` pour lire le lien créé et son canal. |
| `tests/Api/ExtensionLinkTest.php` | Un ajout par l'API porte le canal `extension`. |
| `tests/Controller/LinkControllerTest.php` | Un ajout depuis le formulaire sans paramètre de partage porte le canal `web`. |

## Hors scope

- **Cache des assets et lecture hors ligne** : le service worker n'intercepte que les navigations.
- **Méthode de comptage par canal** : la lecture se fait en SQL (`SELECT channel, COUNT(*) FROM link WHERE posted_at >= … GROUP BY channel`) jusqu'à la story `indicateurs-mensuels`.
- **Notifications push, Raccourci iOS, coque native** : hors scope du pitch.
- **Affichage du canal** sur la carte ou dans l'admin : R9.
- **Ajustements propres à l'app installée sur ordinateur** : pitch, hors scope.

## Impacts transverses

- **Cloisonnement des données** : le service worker ne met en cache que `/offline.html`, statique et sans donnée. Le HTML authentifié n'est jamais stocké, donc aucun risque d'afficher les données d'un autre compte sur un appareil partagé.
- **Déclinaisons / thèmes** : `theme-color` en clair et en sombre par `media="(prefers-color-scheme: …)"`. Couleurs du manifeste en clair, car il n'accepte qu'une valeur.
- **Traduction / i18n** : libellés français en dur dans les templates, comme le reste de l'interface (R11 et convention actuelle du projet). `lang: fr` dans le manifeste.
- **API / exposition externe** : l'API de l'extension pose désormais le canal, sans changement de contrat (ni champ en entrée ni en sortie).
- **Droits d'accès** : inchangés. `/installer` et `/liens/ajouter` restent en `ROLE_USER`. Les trois fichiers statiques et les icônes sont publics par nature, et ne contiennent rien de sensible.
- **Emails / notifications** : non. L'annonce Chat d'un ajout par partage suit le chemin habituel (`LinkAnnouncer`).
- **Migration de données** : une colonne nullable ajoutée, sans reprise. Les liens existants restent à `channel = null`.
- **Comportement par défaut** : sans installation, Radar se comporte comme aujourd'hui. Seuls l'entrée de menu et des marges nulles hors iOS standalone apparaissent. Un ajout ordinaire est enregistré en `web`.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/SharedLinkExtractor.php` | unit | `url` valide retenu. `url` absent et lien dans `text` (« Titre https://youtu.be/x ») : lien extrait. Plusieurs liens : le premier. Ponctuation finale (`.`, `)`, `»`) retirée. Repli sur `title`. Schéma non http(s) ignoré. Rien de valide : null. |
| `src/Service/LinkManager.php` | unit | `add()` enregistre le canal de la soumission. `seed()` et `publish()` donnent `channel = null`. |
| `src/Controller/LinkController.php` (partage) | functional | Avec `?text=…https://…`, le champ URL est prérempli et le champ caché vaut `share`. Avec `?url=`, prérempli. Avec un texte sans lien, l'alerte `share-no-link` s'affiche et l'URL est vide. Avec `?url=` d'un lien existant, l'alerte de doublon s'affiche. Un anonyme sur `?text=…` est redirigé vers la connexion, puis, une fois connecté, vers `/liens/ajouter?text=…`. Un POST avec `channel=share` crée un lien `Share`. Un POST avec une valeur inconnue crée un lien `Web`. |
| `src/Controller/LinkController.php` (ajout ordinaire) | functional | Un ajout sans paramètre de partage donne un lien `Web`. |
| `src/State/ExtensionLinkProcessor.php` | functional | Un ajout par l'API donne un lien `Extension`. |
| `src/Controller/InstallController.php` | functional | Page d'aide en 200 pour un membre, avec les sections iPhone, Android et ordinateur et la phrase de reconnexion. Un anonyme est redirigé vers la connexion. Le fil et la page de connexion portent `link[rel=manifest]`, `theme-color`, `apple-touch-icon` et `viewport-fit=cover`. |
| `tests/E2E/PwaTest.php` | E2E | `link[rel=manifest]` pointe un JSON valide (`display: standalone`, `share_target.action = /liens/ajouter`, icônes servies en 200). `navigator.serviceWorker.ready` est actif. Contexte hors ligne puis navigation : `offline-page` visible. Entrée `install-app-link` visible dans le menu, et masquée quand `matchMedia('(display-mode: standalone)')` est simulé par un script d'initialisation. Partage ouvert, catégorie cochée (rendu Live) : le canal reste `share`, puis « Lien ajouté. ». |

**Recette manuelle sur appareils réels**, consignée à l'implémentation (un Android avec Chrome et un iPhone avec Safari). Elle couvre les critères du pitch que l'automatisation ne peut pas toucher :
- installation et ouverture en plein écran avec l'icône et le nom ;
- encoche et barre d'accueil ;
- invite native au clic sur l'entrée de menu ;
- Radar dans le menu Partager de Chrome et de YouTube ;
- partage puis ajout puis annonce Chat ;
- ouverture d'un lien d'origine depuis l'app installée (sortie vers le navigateur ou un onglet intégré).

**Hors scope tests** :
- L'installation réelle, le menu Partager et les zones de sécurité : Playwright ne sait pas les simuler, la recette manuelle les couvre.
- L'invite `beforeinstallprompt` : impossible à déclencher en headless. Le contrôleur Stimulus est vérifié sur le seul repli vers la page d'aide et sur le masquage en standalone.
- Pas de test du contenu de `offline.html` au-delà de sa présence : c'est un fichier statique sans logique.

## Ordre d'exécution

1. [x] **Canal d'entrée d'un ajout**
   - Objectif : chaque nouvel ajout porte `web`, `extension` ou null (reprise, source).
   - Fichiers : `LinkChannel` (créé), `Link`, `LinkSubmission`, `LinkType`, `LinkManager`, `ExtensionLinkProcessor`, migration générée, tests `LinkManagerChannelTest`, `ExtensionLinkTest`, `LinkControllerTest`.
   - Vérification : `symfony console make:migration` puis relecture du `up()`/`down()`, `symfony console doctrine:schema:validate`, `make phpunit`, `make lint`. Proposer `/symfony:doctrine-entity` et `/symfony:doctrine-migration` pour cette étape.
   - Commitable seule : oui.

2. [x] **Préremplissage par partage**
   - Objectif : `/liens/ajouter?url|text|title` ouvre le formulaire prérempli avec le canal `share`, ou l'alerte « aucun lien ».
   - Fichiers : `SharedLinkExtractor` (créé), `LinkController`, `templates/link/new.html.twig`, tests `SharedLinkExtractorTest`, `LinkShareTest`.
   - Vérification : `make phpunit`, `make lint`. Manuellement : `https://radar.wip/liens/ajouter?text=Vid%C3%A9o%20https://youtu.be/xyz`.
   - Commitable seule : oui.

3. [x] **Socle installable**
   - Objectif : Radar est installable, s'affiche correctement en standalone et sert la page hors ligne.
   - Fichiers : `public/manifest.json`, `public/sw.js`, `public/offline.html`, `public/icons/*`, `_pwa_head.html.twig`, `base.html.twig` (tête et marges), `security.html.twig`, `assets/app.js`, `InstallControllerTest` (partie balises de tête), `PwaTest` (manifeste, service worker, hors ligne).
   - Vérification : Chrome DevTools, onglet Application, rubrique Manifest sans erreur d'installabilité. Service worker « activated ». Mode hors ligne : page de repli. `make phpunit`, `make playwright`.
   - Commitable seule : oui.

4. [x] **Entrée de menu et page d'aide**
   - Objectif : « Installer l'app » dans le menu, invite native ou page d'aide, masquée en standalone.
   - Fichiers : `pwa_install_controller.js`, `InstallController`, `templates/install/index.html.twig`, `base.html.twig` (entrée de menu), `InstallControllerTest`, `PwaTest` (entrée de menu).
   - Vérification : `make phpunit`, `make playwright`, `make lint`.
   - Commitable seule : oui.

5. [ ] **Recette sur appareils réels et QA finale**
   - Objectif : valider les critères d'acceptation du pitch non automatisables, en préproduction ou en production après déploiement (le partage Android exige une URL HTTPS joignable par le téléphone).
   - Fichiers : aucun. Le résultat est consigné dans l'entrée `Implémentation` du `metadata.json`.
   - Vérification : checklist de la recette manuelle ci-dessus, `make quality`.
   - Commitable seule : non (validation).

## Critères de sortie

- [x] `link.channel` existe (migration générée, `doctrine:schema:validate` vert). Un ajout web, extension ou partage enregistre respectivement `web`, `extension` ou `share`. Une reprise ou un article de source enregistre null.
- [x] `GET /liens/ajouter?text=…` préremplit le premier lien du texte. Sans lien, l'alerte `share-no-link` s'affiche.
- [ ] `public/manifest.json`, `public/sw.js` et `public/offline.html` sont servis sans authentification. Le manifeste ne remonte aucune erreur d'installabilité dans Chrome DevTools.
- [x] Le service worker n'intercepte que les navigations et ne met en cache que `/offline.html`.
- [x] L'entrée `install-app-link` est absente en mode standalone.
- [ ] Recette manuelle Android et iPhone consignée, critères d'acceptation du pitch couverts.
- [x] `make phpunit` et `make playwright` verts, sans nouvelle régression.
- [x] Mago à zéro finding (`make lint`, `make quality`).

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Une modification de `offline.html` sans incrément de la version du cache dans `sw.js` laisse l'ancienne page dans les apps installées | moyenne | Commentaire en tête de `sw.js` et de `offline.html`. Règle à ajouter à `forge/stack.md` à la sync (« tout changement de `offline.html` incrémente la version du cache »), comme celle de l'extension. |
| `beforeinstallprompt` émis avant que le contrôleur soit connecté, ou perdu entre deux visites Turbo | moyenne | Écouteur posé à l'évaluation du module (contrôleur chargé tôt) et événement gardé au niveau du module. En dernier recours, l'entrée retombe sur la page d'aide : jamais de bouton mort. |
| L'E2E ne peut pas enregistrer le service worker sur `https://radar.wip` si Chromium ne fait pas confiance au certificat local (`ignoreHTTPSErrors` ne suffit pas pour un service worker) | moyenne | Vérifier que la CA de Symfony CLI est installée (`symfony server:ca:install`). Sinon, faire tourner la partie service worker de `PwaTest` sur `http://localhost` (contexte sûr) ou la marquer skipped, avec la raison. |
| Marges de zone de sécurité mal calées (topbar trop haute hors iOS, bouton flottant décalé) | faible | `env()` vaut 0 hors appareil à encoche, donc pas de régression sur ordinateur. Vérification visuelle en recette iPhone. |
| Le type MIME du manifeste est refusé par le serveur de production | faible | Extension `.json`, connue de Caddy et FrankenPHP. Vérification en recette (DevTools, rubrique Manifest). |
| Le partage Android n'est testable qu'avec une URL HTTPS joignable par le téléphone | élevée | Recette après déploiement sur `radar.mustiere.fr`, ou via un tunnel vers le poste. Prévu à l'étape 5. |
| Un membre modifie le champ caché du canal | faible | Sans enjeu : statistique agrégée, valeur inconnue ramenée à `web`. |

## Questions ouvertes

- **Rasterisation des icônes** : il n'y a pas d'outil SVG vers PNG installé (`sips` ne rasterise pas le SVG). Options : (a) capture d'un SVG pleine page par Playwright ou Chrome headless, (b) `qlmanage -t`, (c) un outil ajouté ponctuellement (`brew install librsvg`). Recommandé : (a), Playwright est déjà là et le rendu est fidèle. Le procédé est noté dans le message de commit, et les PNG sont commités sans script. → tranché : Chrome headless (`--screenshot`, fond transparent) sur une page SVG à la taille cible, PNG commités sans script.

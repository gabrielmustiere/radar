# Plan technique — Ajouter la page en cours à Radar depuis le navigateur, sans quitter la page

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/014-f-extension-navigateur/`
> **Amont** : `pitch.md`

## Approche retenue

Radar expose une **API JSON-LD sous `/api/extension`**, construite avec API Platform. Les ressources ne sont pas des entités mais des **DTO dédiés** (`src/ApiResource/`), qui ne déclarent que les opérations dont la bulle a besoin : lister et créer une catégorie, détecter un doublon, ajouter un lien, compléter les catégories d'un doublon. Des **State Providers et Processors** relaient vers les services existants, sans rien dupliquer des règles : `CategoryResolver::resolve()`, `LinkManager::add()`, `LinkManager::findDuplicate()` et `LinkManager::complete()`. Le Processor d'ajout reconstruit une `LinkSubmission` à partir des identifiants de catégories et la fait valider par `ValidatorInterface`. Les contraintes et les messages sont donc exactement ceux du formulaire. L'aperçu, puis l'annonce Chat, suivent sans changement : `LinkManager::add()` déclenche `FetchLinkPreview`, dont le handler appelle `LinkAnnouncer`.

L'API vit derrière un **pare-feu `api` sans état** (`^/api`), authentifié par `access_token` (en-tête `Authorization: Bearer`). Un handler maison retrouve le membre par l'empreinte SHA-256 de la clé, refuse une clé expirée (90 jours sans usage) et met à jour la date de dernière utilisation à **chaque requête authentifiée**. La portée « ajout seulement » tient par construction : le pare-feu `main` n'accepte aucun jeton, et `/api` ne contient que les ressources de l'extension. La clé est stockée sur **`User`**, dans trois colonnes nullables : une clé au plus par membre, facultative. La page « Extension navigateur » (`/extension`, pare-feu `main`) génère, régénère et révoque la clé. La clé en clair n'est affichée que dans la réponse au POST de génération, sans redirection ni flash, pour qu'elle ne transite jamais par la session. L'extension (`extension/`, Manifest V3) est en HTML, CSS et JS vanilla, sans build. La bulle appelle l'API avec `host_permissions` sur l'origine de Radar, donc sans CORS côté serveur. Elle choisit l'instance selon son mode d'installation : publiée, `radar.mustiere.fr` ; non empaquetée, `radar.wip`.

### Mécanismes mobilisés

- **API Platform (`api-platform/symfony` ^5.0, v5.0.5 résolue sans conflit sur Symfony 8.1)** : choix de l'utilisateur. Ressources DTO avec `#[ApiResource]` et des opérations explicites (`GetCollection`, `Get` sans variable d'URI, `Post`). Format JSON-LD/Hydra par défaut (la bulle lit `member` et `violations`). `enable_docs`, `enable_entrypoint`, `enable_swagger`, `enable_swagger_ui`, `enable_re_doc` et `enable_scalar` sont à `false` partout. L'intégration Doctrine d'API Platform est coupée (`doctrine.enabled: false`) : les ressources sont des DTO, et sans cette option le cache ne se vide pas faute de `api-platform/doctrine-orm`. Les catégories d'un doublon sont renvoyées comme tableaux `{id, name}` et non comme ressources, qu'API Platform réduirait à leur IRI.
- **State Provider / State Processor** : seul point de passage de l'API vers les services. Aucune logique métier dans les DTO (règle d'architecture du projet).
- **`ValidationException` d'API Platform** levée par le Processor d'ajout à partir des violations de `LinkSubmission` : réponse 422 avec les messages français existants (« Choisissez au moins une catégorie, ou créez-en une. », « Un lien porte au plus trois catégories. »).
- **`exception_to_status`** : `DuplicateLinkException` et `CategoryLimitException` donnent un 409. La bulle réinterroge alors le doublon, comme `LinkForm` le fait au fil de la saisie.
- **Security `access_token` + `AccessTokenHandlerInterface`** sur un pare-feu `stateless: true` déclaré avant `main`. Le handler renvoie un `UserBadge` avec le `User` déjà chargé. Un échec lève une `BadCredentialsException`, ce qui donne un 401.
- **Empreinte SHA-256** de la clé (`rdr_` + 32 octets aléatoires encodés), recherchée par un index unique. Un secret à 256 bits d'entropie n'a pas besoin d'un hachage lent : seule l'empreinte est stockée, et la présenter ne sert à rien puisque le serveur hache ce qu'il reçoit.
- **Rate Limiter** (`symfony/rate-limiter`, déjà installé) : limiteur `extension_api_failure` en fenêtre glissante de 10 échecs par 15 minutes et par IP. Le `login_throttling` natif ne convient pas : il compte sur `CheckPassportEvent`, jamais émis quand le handler rejette la clé avant de créer le passeport. Deux écouteurs se partagent le travail : sur `LoginFailureEvent` (pare-feu `api`), consommer un jeton ; sur `kernel.request` (priorité au-dessus du pare-feu, chemins `^/api`), répondre 429 si la limite est atteinte.
- **`#[IsCsrfTokenValid]`** sur les POST de génération et de révocation, comme `SourceController`. Un jeton invalide est une erreur d'authentification : le pare-feu redirige vers la connexion (comportement du projet, cf. `LinkPlayTest`).
- **`AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER`** sur la réponse qui affiche la clé : sans ce marqueur, le listener de session remplace `Cache-Control: no-store` par `private, must-revalidate`.
- **`ClockInterface`**, déjà injectée partout : création, dernière utilisation et expiration sont testables avec une horloge figée.
- **Extension Manifest V3** : `action.default_popup`, `contextMenus` et `activeTab`, ni `tabs` ni `<all_urls>`, pour limiter les permissions et faciliter la revue Google. `storage` pour la clé (`chrome.storage.local`), et `host_permissions` sur `https://radar.mustiere.fr/*` et `https://radar.wip/*`. Le clic droit appelle `chrome.action.openPopup()` et, s'il échoue, ouvre `chrome.windows.create({type: 'popup'})` sur `popup.html?url=…`. Cette même entrée par paramètre sert à l'E2E.
- **Bulle ouverte en `file://` dans l'E2E** : Playwright PHP déclare `launchPersistentContext()` sans l'implémenter, et Chrome n'active pas les extensions (`ERR_BLOCKED_BY_CLIENT`) dans les contextes qu'il crée. `tests/E2E/ExtensionTestCase.php` ouvre donc `extension/popup.html?url=…` en `file://`, avec une API `chrome` simulée par `addInitScript` (`runtime.getManifest`, `storage.local` sur `localStorage`), et la bulle appelle la vraie API de `radar.wip` (Chromium lancé par Playwright PHP avec `--disable-web-security`). Une origine factice servie par `context->route()` a été écartée : le gestionnaire PHP bloque toute navigation ultérieure.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Contrôleurs Symfony avec `#[MapRequestPayload]` | Plus léger (aucune dépendance) et suffisant pour 5 points d'entrée, mais écarté par l'utilisateur au profit d'API Platform. |
| `#[ApiResource]` sur les entités `Link` et `Category` | Les entités porteraient la forme de l'API, et la création passerait de toute façon par un Processor (doublon, aperçu). |
| JSON simple + `problem+json` | Plus sobre pour un client vanilla, mais écarté par l'utilisateur au profit du format JSON-LD/Hydra par défaut. |
| Entité `ExtensionKey` en un-à-un | Une table et une jointure de plus pour une clé unique et facultative. L'empreinte sur `User`, sérialisée en session, est inexploitable. |
| Bulle en fragments HTML rendus par Radar | Protocole maison fragile, et la CSP de Manifest V3 complique l'exécution du comportement injecté. |
| Bulle intégrant la page Radar (iframe + session) | Cookies tiers bloqués, et il faudrait assouplir `frame-ancestors`. Le pitch a retenu la clé. |
| `login_throttling` sur le pare-feu `api` | Inopérant : un rejet dans le handler n'émet pas `CheckPassportEvent`, donc l'échec n'est jamais compté. |
| Bundle CORS (Nelmio) | Inutile : les pages d'extension avec `host_permissions` ne sont pas soumises à CORS, et l'absence d'en-têtes CORS ferme l'API aux pages web. |

## Modèle de données

### Modification de `User`

`src/Entity/User.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `extensionKeyHash` (`extension_key_hash`) | `string(64)` | oui | index unique `UNIQ_USER_EXTENSION_KEY_HASH` (plusieurs `NULL` admis par SQLite) |
| `extensionKeyCreatedAt` (`extension_key_created_at`) | `datetime_immutable` | oui | renseigné en même temps que l'empreinte |
| `extensionKeyLastUsedAt` (`extension_key_last_used_at`) | `datetime_immutable` | oui | `NULL` tant que la clé n'a pas servi |

Méthodes de l'entité, sans logique de décision : `replaceExtensionKey(string $hash, \DateTimeImmutable $createdAt)` (remet la dernière utilisation à `NULL`), `revokeExtensionKey()` (remet les trois champs à `NULL`), `markExtensionKeyUsed(\DateTimeImmutable $at)` et les getters. La génération, l'expiration et la vérification vivent dans `ExtensionKeyManager`. `__serialize()` est inchangée : l'empreinte en session n'ouvre rien.

Migration générée par `symfony console make:migration` : trois `ADD COLUMN` nullables et l'index unique, sans reprise de données. Aucun membre n'a de clé à la livraison.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `config/packages/api_platform.yaml` | Créé par la recette : titre, formats JSON-LD, docs, entrypoint et Swagger désactivés, `exception_to_status` (doublon et plafond → 409). |
| `config/routes/api_platform.yaml` | Créé par la recette : routes API Platform sous le préfixe `/api`. |
| `migrations/VersionYYYYMMDDHHMMSS.php` | Colonnes de clé d'extension sur `user` et index unique (générée). |
| `src/Service/ExtensionKeyManager.php` | Génère une clé (renvoie le clair une seule fois, stocke l'empreinte), la révoque, l'authentifie (empreinte, expiration à 90 jours, dernière utilisation). |
| `src/Security/ExtensionKeyHandler.php` | `AccessTokenHandlerInterface` : délègue à `ExtensionKeyManager`, renvoie le `UserBadge` ou lève `BadCredentialsException`. |
| `src/EventListener/ExtensionApiThrottleListener.php` | Compte les échecs d'authentification du pare-feu `api` par IP, répond 429 quand la limite est atteinte. |
| `src/Controller/ExtensionKeyController.php` | Page « Extension navigateur » : `GET /extension`, `POST /extension/generer` (rend la clé en clair sans redirection), `POST /extension/revoquer`. |
| `templates/extension_key/index.html.twig` | Page de la clé : clé affichée une fois avec un bouton Copier (contrôleur Stimulus de copie), ou dates de création et de dernière utilisation, avec Régénérer et Révoquer. |
| `assets/controllers/clipboard_controller.js` | Copie de la clé dans le presse-papiers. |
| `src/ApiResource/ExtensionCategory.php` | Ressource `GetCollection /extension/categories` et `Post /extension/categories` (`name`, `Length(2, 60)`) ; sortie `{id, name}`. |
| `src/ApiResource/ExtensionLink.php` | Ressource `Post /extension/links` : entrée `{url, context, categories: list<int>}`, sortie `{id, cardUrl}`. |
| `src/ApiResource/ExtensionDuplicate.php` | Ressource `Get /extension/duplicate?url=` (404 si aucun doublon) : `{id, title, categories: [{id, name}], freeSlots, cardUrl}`, construite par `fromLink()` qui génère aussi l'URL de la carte ; `Post /extension/duplicate/{id}/categories` pour compléter. |
| `src/ApiResource/ExtensionCategorySelection.php` | Entrée du complément de doublon : `{categories: list<int>}`. |
| `src/State/ExtensionCategoryProvider.php` | Liste les catégories (`CategoryRepository::findAllOrderedByName()`). |
| `src/State/ExtensionCategoryProcessor.php` | Crée ou réutilise une catégorie via `CategoryResolver::resolve()`. |
| `src/State/ExtensionLinkProcessor.php` | Résout les identifiants (une catégorie inconnue donne une violation), construit et valide `LinkSubmission`, appelle `LinkManager::add()` avec l'utilisateur courant. |
| `src/State/ExtensionDuplicateProvider.php` | Lit `url` dans la requête, appelle `LinkManager::findDuplicate()` et calcule les places restantes. |
| `src/State/ExtensionDuplicateProcessor.php` | Appelle `LinkManager::complete()` avec les catégories choisies. |
| `public/confidentialite-extension.html` | Politique de confidentialité exigée par le Web Store : page statique servie sans Symfony, hors `/extension` pour éviter une collision avec la route. |
| `extension/manifest.json` | Manifest V3 : bulle, service worker, permissions `activeTab`, `contextMenus` et `storage`, `host_permissions` prod et dev. |
| `extension/config.js` | Adresse de Radar : prod si `chrome.runtime.getManifest().update_url` existe, sinon `https://radar.wip`. |
| `extension/background.js` | Crée l'entrée « Ajouter cette page à Radar » ; au clic, `openPopup()` ou fenêtre de repli avec `?url=&title=`. |
| `extension/popup.html` | Écrans : saisie de la clé, formulaire (contexte, catégories, adresse), confirmation, clé invalide. |
| `extension/popup.css` | Identité Radar (noir et blanc, clair et sombre via `prefers-color-scheme`), sans Tailwind. |
| `extension/popup.js` | Client de l'API, filtre des pastilles, Entrée qui crée une catégorie, plafond de 3 affiché, alerte de doublon et complément, gestion 401/409/422/429. |
| `extension/icons/` | Icônes 16, 32, 48 et 128 px. |
| `tests/Service/ExtensionKeyManagerTest.php` | Unit : format de la clé, empreinte stockée et pas le clair, régénération qui remplace, révocation, bornes d'expiration (89 et 91 jours), dernière utilisation. |
| `tests/Controller/ExtensionKeyControllerTest.php` | Functional : page sans clé, génération qui affiche la clé une fois, revisite sans la clé, régénération qui invalide l'ancienne, révocation, CSRF exigé, anonyme redirigé vers la connexion. |
| `tests/Api/ExtensionApiTestCase.php` | Base des tests API : création d'un membre avec une clé, requêtes JSON-LD avec `Authorization: Bearer`. |
| `tests/Api/ExtensionAuthTest.php` | Functional : sans clé, clé fausse, révoquée ou expirée (401) ; régénération ; clé valide qui met à jour la dernière utilisation. |
| `tests/Api/ExtensionScopeTest.php` | Functional : jeton sur une route `main` sans effet (redirection vers la connexion) ; session ouverte par le vrai formulaire de connexion refusée sur `/api` ; 11e échec en 429, autre IP non affectée ; documentation absente (404). |
| `tests/Api/ExtensionCategoryTest.php` | Functional : liste triée, création avec majuscule initiale, réutilisation d'un nom existant (casse ignorée), nom de 1 ou de 61 caractères refusé en 422. |
| `tests/Api/ExtensionLinkTest.php` | Functional : ajout avec posteur, catégories, aperçu déclenché et annonce envoyée ; 0 ou 4 catégories, URL invalide et catégorie inconnue refusées en 422 avec les messages du formulaire ; doublon en 409. |
| `tests/Api/ExtensionDuplicateTest.php` | Functional : 404 sans doublon ; données du doublon ; complément ajouté sans annonce ; complément au-delà de 3 en 409. |
| `tests/E2E/ExtensionTestCase.php` | Bulle ouverte en `file://` avec API `chrome` simulée (voir §Mécanismes mobilisés). |
| `tests/E2E/ExtensionTest.php` | E2E : génération de la clé dans Radar, collage dans la bulle, ajout d'un lien (catégorie existante + catégorie créée), confirmation et carte dans Radar ; doublon et complément ; erreurs 422 affichées ; 4e catégorie créée non sélectionnée ; clé révoquée puis message. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `composer.json` / `composer.lock` / `symfony.lock` | Ajouter `api-platform/symfony` ^5.0 (et ses dépendances transitives). |
| `config/bundles.php` | Enregistré par la recette : `ApiPlatformBundle`. |
| `config/reference.php` | Régénéré par Symfony : référence de configuration incluant `api_platform`. |
| `config/packages/security.yaml` | Ajouter le pare-feu `api` (`pattern: ^/api`, `stateless: true`, `access_token.token_handler: App\Security\ExtensionKeyHandler`) avant `main` ; règle `access_control` explicite `^/api` en `ROLE_USER`. |
| `config/packages/rate_limiter.yaml` | Ajouter le limiteur `extension_api_failure` (`sliding_window`, 10 par 15 minutes). |
| `src/Entity/User.php` | Ajouter les trois champs de clé et leurs méthodes (voir §Modèle de données). |
| `src/Repository/UserRepository.php` | Ajouter `findOneByExtensionKeyHash(string $hash): ?User`. |
| `templates/base.html.twig` | Ajouter l'entrée « Extension navigateur » dans le menu du membre (`data-test="extension-key-link"`). |
| `Makefile` | Ajouter la cible `extension`, qui produit `var/extension/radar-extension-<version>.zip` depuis `extension/`, déclarée en `.PHONY` (le dossier `extension/` porte son nom). |
| `docs/stack.md` | Mentionner API Platform, le pare-feu `api` et l'extension (à réaligner au `/forge:sync` si non fait à l'implémentation). |

## Hors scope

- **Avis de sécurité existants** (`symfony/expression-language` CVE-2026-109449, `symfony/mime` CVE-2026-45070) : antérieurs à la story, à traiter à part.
- **Documentation OpenAPI et Swagger UI** : désactivées. Les tests fonctionnels servent de contrat.
- **CORS, OAuth, révocation par un admin** : voir les alternatives écartées et le hors scope du pitch.
- **Outillage de build JS pour l'extension** (bundler, TypeScript, Tailwind) : le JS vanilla tient la bulle.
- **Publication automatisée sur le Web Store** (CI, API du Store) : envoi du zip à la main.
- **Variante Firefox** : hors scope du pitch.
- **Réécriture de `LinkForm` pour partager le calcul du doublon avec l'API** : `LinkManager::complete()` filtre déjà les catégories présentes. Seules les places restantes sont recalculées, en une ligne, dans le Provider.

## Impacts transverses

- **Cloisonnement des données** : non. Tout est partagé (R8). La clé n'est lue et gérée que par son propriétaire (`#[CurrentUser]` sur `/extension`), et aucune route ne prend un membre en paramètre.
- **Déclinaisons / thèmes** : la page Radar suit le thème existant (clair et sombre). La bulle reproduit l'identité en CSS autonome avec `prefers-color-scheme`.
- **Traduction / i18n** : libellés en français en dur, comme le reste du projet (R11). Les dates de la page de clé sont formatées à l'heure de Paris, comme ailleurs.
- **API / exposition externe** : oui, une première API. Cinq opérations sous `/api/extension`, joignables seulement avec une clé valide, sans CORS. Pas de documentation exposée.
- **Droits d'accès** : nouveau pare-feu `api`. Le jeton n'est accepté que sous `^/api`, où ne vivent que les ressources de l'extension. Le pare-feu `main` est inchangé, et un `Authorization: Bearer` y est ignoré. Aucun voter : `ROLE_USER` suffit (R2), et la clé d'un admin n'ouvre rien de plus sous `/api`.
- **Emails / notifications** : non. L'annonce Chat passe par le flux existant (`FetchLinkPreviewHandler` → `LinkAnnouncer`), et le complément n'est pas annoncé (`LinkManager::complete()` ne déclenche rien).
- **Migration de données** : création de trois colonnes nullables et d'un index sur `user`, sans reprise.
- **Comportement par défaut** : inchangé pour qui n'a pas de clé. Les routes existantes et le formulaire Live ne bougent pas.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/ExtensionKeyManager.php` | unit | Clé `rdr_…` de longueur fixe ; seule l'empreinte est stockée ; régénérer remplace l'empreinte et remet la dernière utilisation à `NULL` ; révoquer vide les champs ; expirée à plus de 90 jours depuis `lastUsedAt ?? createdAt`, valide à 89 jours ; `markExtensionKeyUsed` à l'heure de l'horloge. |
| `src/Controller/ExtensionKeyController.php` | functional | Parcours génération → clé visible une fois → revisite sans clé avec la date de création ; régénération ; révocation ; badge « expirée » après 90 jours sans usage ; jeton CSRF invalide redirigé vers `/login` sans clé créée ; anonyme redirigé vers `/login`. |
| `src/Security/ExtensionKeyHandler.php` + `security.yaml` | functional | 401 sans clé, fausse, révoquée ou expirée ; 200 et dernière utilisation à jour avec une clé valide ; `GET /` avec `Bearer` valide et sans session redirige vers `/login` ; cookie de session sans jeton refusé sur `/api`. |
| `src/EventListener/ExtensionApiThrottleListener.php` | functional | 10 échecs depuis une IP, puis 429 même avec une clé valide ; une autre IP n'est pas affectée. |
| `src/State/ExtensionCategory*.php` | functional | Liste triée par nom ; création avec majuscule initiale ; nom existant (autre casse) réutilisé sans doublon ; 422 sur 1 ou 61 caractères. |
| `src/State/ExtensionLinkProcessor.php` | functional | 201 avec posteur = propriétaire de la clé et catégories enregistrées ; aperçu puis annonce Chat (transport `sync://` en test, comme `LinkAnnouncementTest`) ; 422 sur 0 ou 4 catégories, URL `chrome://…` ou catégorie inconnue, avec les messages de `LinkSubmission` ; 409 sur doublon. |
| `src/State/ExtensionDuplicate*.php` | functional | 404 sans doublon ; données et places restantes ; complément enregistré sans annonce ; 409 si le plafond serait dépassé. |
| `extension/` + page de clé | E2E | Parcours complets de la bulle ouverte en `file://` contre l'API réelle (voir `tests/E2E/ExtensionTest.php`). |

**Hors scope tests** :

- **Ouverture de la bulle par l'icône et par le clic droit** (`openPopup()`, fenêtre de repli) : Playwright ne pilote pas l'interface du navigateur. Recette manuelle sur Chrome et Edge, et l'E2E passe par `popup.html?url=` comme la fenêtre de repli.
- **Préremplissage depuis l'onglet actif** (`activeTab`) : même raison, recette manuelle.
- **Manifest, service worker et permissions réelles** : Playwright PHP ne charge pas d'extension (pas de contexte persistant) ; recette manuelle de l'extension non empaquetée.
- **Mode publié** (`update_url` présent, instance prod) : recette manuelle après la première publication non répertoriée.
- **JS de la bulle en unitaire** : pas d'outillage de test JS dans le projet. Le comportement est couvert par l'E2E.
- **Contexte `ExtensionTestCase` dans `make phpunit`** : l'E2E reste dans la testsuite `e2e` (`make playwright`), qui demande `make serve`.

## Ordre d'exécution

1. [x] **Prototype : charger une extension dans l'E2E**
   - Objectif : prouver que Playwright PHP ouvre `chrome-extension://<id>/popup.html` avec une extension non empaquetée. Échec constaté (contexte persistant non implémenté, `ERR_BLOCKED_BY_CLIENT`) : bulle ouverte en `file://` avec API `chrome` simulée, choix validé par l'utilisateur.
   - Fichiers : `extension/manifest.json`, `extension/popup.html` (squelette), `tests/E2E/ExtensionTestCase.php`, `tests/E2E/ExtensionTest.php` (un test qui lit le titre de la bulle).
   - Vérification : `make playwright` vert sur ce test.
   - Commitable seule : oui.

2. [x] **Clé sur `User` et `ExtensionKeyManager`**
   - Objectif : champs, méthodes, migration et service de génération, révocation et authentification.
   - Fichiers : `src/Entity/User.php`, `src/Repository/UserRepository.php`, `migrations/Version…php`, `src/Service/ExtensionKeyManager.php`, `tests/Service/ExtensionKeyManagerTest.php`.
   - Vérification : `symfony console make:migration` relue, `symfony console doctrine:schema:validate`, `make phpunit`.
   - Commitable seule : oui.

3. [x] **Page « Extension navigateur »**
   - Objectif : générer, afficher une fois, régénérer et révoquer depuis Radar ; entrée de menu.
   - Fichiers : `src/Controller/ExtensionKeyController.php`, `templates/extension_key/index.html.twig`, `assets/controllers/clipboard_controller.js`, `templates/base.html.twig`, `tests/Controller/ExtensionKeyControllerTest.php`.
   - Vérification : `make phpunit` ; contrôle visuel en clair et en sombre.
   - Commitable seule : oui.

4. [x] **API Platform, pare-feu `api` et limiteur**
   - Objectif : installer et configurer API Platform (docs coupées, 409), authentifier par clé, limiter les échecs. Livrer d'abord la seule ressource `GetCollection /extension/categories` pour éprouver la chaîne.
   - Fichiers : `composer.json`/`.lock`, `config/packages/api_platform.yaml`, `config/routes/api_platform.yaml`, `config/packages/security.yaml`, `config/packages/rate_limiter.yaml`, `src/Security/ExtensionKeyHandler.php`, `src/EventListener/ExtensionApiThrottleListener.php`, `src/ApiResource/ExtensionCategory.php`, `src/State/ExtensionCategoryProvider.php`, `tests/Api/ExtensionApiTestCase.php`, `tests/Api/ExtensionAuthTest.php`.
   - Vérification : `make phpunit` ; `symfony console debug:router | grep api` ne montre ni `/api/docs` ni entrypoint ; `make lint`.
   - Commitable seule : oui.

5. [x] **Ressources d'ajout, de catégorie et de doublon**
   - Objectif : création de catégorie, ajout de lien, détection et complément de doublon, à parité avec le formulaire.
   - Fichiers : `src/ApiResource/ExtensionCategory.php` (Post), `src/ApiResource/ExtensionLink.php`, `src/ApiResource/ExtensionDuplicate.php`, `src/State/ExtensionCategoryProcessor.php`, `src/State/ExtensionLinkProcessor.php`, `src/State/ExtensionDuplicateProvider.php`, `src/State/ExtensionDuplicateProcessor.php`, `tests/Api/ExtensionCategoryTest.php`, `tests/Api/ExtensionLinkTest.php`, `tests/Api/ExtensionDuplicateTest.php`.
   - Vérification : `make phpunit`, `make lint`.
   - Commitable seule : oui.

6. [x] **Bulle, clic droit et paquet**
   - Objectif : extension complète (écrans de clé, formulaire, confirmation, erreurs), menu contextuel avec repli, politique de confidentialité, cible `make extension`.
   - Fichiers : `extension/*`, `public/confidentialite-extension.html`, `Makefile`, `tests/E2E/ExtensionTest.php` (parcours complets).
   - Vérification : `make playwright` ; `make extension` produit un zip qui se charge sans erreur dans `chrome://extensions`.
   - Commitable seule : oui.

7. [ ] **QA finale et recette manuelle**
   - Objectif : suite complète verte. Recette sur Chrome et Edge : icône sur une page web, clic droit, page `chrome://` refusée, clé révoquée, mode sombre, ajout en moins de 15 secondes.
   - Fichiers : `docs/stack.md`.
   - Vérification : `make lint`, `make phpunit`, `make playwright`, `symfony console doctrine:schema:validate` ; checklist de recette consignée dans le compte rendu d'implémentation.
   - Commitable seule : oui.

## Critères de sortie

- [x] `make lint` sans aucun finding (Mago fmt, lint, analyze), sans baseline.
- [x] `make phpunit` vert, sans régression sur les tests existants (`LinkControllerTest`, `LinkDuplicateTest`, `LinkAnnouncementTest`…).
- [x] `make playwright` vert, avec `ExtensionTest` (ou bascule en recette manuelle documentée si le prototype de l'étape 1 a échoué).
- [x] `symfony console doctrine:schema:validate` OK ; la migration ne fait qu'ajouter des colonnes nullables et un index.
- [x] La base ne contient jamais une clé en clair : seules des empreintes de 64 caractères hexadécimaux dans `user.extension_key_hash`.
- [x] `GET /api/docs`, `GET /api` et `GET /api/docs.jsonopenapi` ne renvoient pas de documentation (404 ou 401).
- [x] Un `Authorization: Bearer <clé valide>` n'authentifie aucune route hors de `^/api` (couvert par `ExtensionScopeTest`).
- [x] `make extension` produit un zip dont le manifest ne déclare ni `tabs`, ni `<all_urls>`, ni d'autre hôte que les deux instances Radar.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| API Platform 5 alourdit le projet (14 paquets) et sa recette ajoute une configuration ou des routes non voulues | moyenne | Relire le diff de la recette ; docs et entrypoint coupés ; critère de sortie sur `/api/docs`. |
| Le contexte JSON-LD (`@context`) dépend de routes de documentation coupées | faible | Vérifier en étape 4 qu'une réponse JSON-LD reste valide avec `enable_docs: false` ; sinon garder la route `api_jsonld_context` seule. |
| `chrome.action.openPopup()` est indisponible ou refusé depuis le menu contextuel sur certains navigateurs | moyenne | Fenêtre de repli `popup.html?url=` systématique en cas d'erreur ; recette sur Chrome et Edge. |
| Le prototype E2E de l'extension échoue avec Playwright PHP | moyenne | Survenu à l'étape 1 : bulle testée en `file://` avec API `chrome` simulée ; manifest, icône et clic droit en recette manuelle. |
| Revue Google refusée (permissions jugées larges, politique de confidentialité absente) | faible | Permissions minimales (`activeTab`), page de confidentialité publique, justification de chaque permission dans la fiche. |
| Fuite de la clé par le profiler ou les journaux (en-tête `Authorization`) | faible | Le profiler n'existe qu'en dev ; Monolog ne journalise pas les en-têtes ; rien de la clé n'est logué par le handler. |
| Écriture SQLite à chaque requête API (dernière utilisation) | faible | Quelques requêtes par ajout, base en WAL : négligeable au volume visé (5 à 20 liens par semaine). |
| Contournement du limiteur par une IP partagée (NAT de bureau) qui bloque des membres légitimes | faible | Seuls les échecs sont comptés ; 10 par 15 minutes laisse de la marge à une équipe ; `PRIVATE_SUBNETS` déjà en proxys de confiance pour l'IP réelle. |

## Questions ouvertes

- **Compte développeur du Chrome Web Store** (reprise du pitch) : (a) compte personnel du porteur du projet, (b) compte rattaché à l'organisation. Pas de recommandation : dépend de la politique de l'organisation. Bloque la publication (après l'étape 7), pas le développement.
- **Rattachement au backlog** (reprise du pitch) : la story ne correspond à aucune ligne de `docs/product-backlog.md`. Options : (a) `/forge:product-backlog` pour ajouter capacité et ligne V2, puis renseigner `backlog` dans `metadata.json`, (b) au `/forge:sync` de clôture. Recommandé : (b), le plan étant déjà rédigé. → tranché : (b), ligne `extension-navigateur` et capacité C1.9 ajoutées au `/forge:sync`.
- **Règle 9 du pitch** : le pitch dit que « chaque ajout » repousse l'expiration ; le plan retient « chaque requête authentifiée » (choix de l'utilisateur au plan). Recommandé : réaligner la formulation du pitch au `/forge:sync`. → tranché : pitch §Règles métier n°9 réalignée.
- **Version de l'extension** : (a) version propre dans `manifest.json` (semver indépendant), (b) alignée sur les releases de Radar. Recommandé : (a), car une release Radar ne republie pas forcément l'extension, et chaque version passe une revue Google. → tranché : (a), `extension/manifest.json` en `0.1.0`.

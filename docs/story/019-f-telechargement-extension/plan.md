# Plan technique — Télécharger et installer l'extension depuis Radar, avec une FAQ sur la même page

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/019-f-telechargement-extension/`
> **Amont** : `pitch.md`

## Approche retenue

Radar assemble le zip de l'extension à la demande. Un service lit les sources embarquées dans l'image (`extension/`), remplace l'adresse de `config.js` par `DEFAULT_URI` et ajoute au manifest une `key` publique fixe, qui donne à l'extension téléchargée une identité stable d'une version à l'autre. Le service expose aussi la version du manifest, lue par trois consommateurs : le contrôleur de téléchargement, la page `/extension` et une nouvelle ressource d'API en lecture seule. Aucune donnée n'est persistée.

La page `/extension` devient un parcours en trois étapes numérotées (télécharger, installer, clé) suivies d'une FAQ repliable. La carte de clé devient l'étape 3 sans changer de comportement. Dans la bulle, une fois les catégories chargées, donc la clé prouvée valide, un appel non attendu récupère la version disponible et affiche une ligne discrète si elle est plus récente que celle du poste. Toute erreur de cet appel est ignorée, sans effet sur le formulaire.

### Mécanismes mobilisés

- **Service autowiré (`ExtensionPackage`)** : point unique pour la version et l'assemblage, évite de répartir la lecture du manifest entre contrôleur, provider et template. `#[Autowire]` lui fournit `%kernel.project_dir%`, `%env(DEFAULT_URI)%` et la clé publique.
- **`ZipArchive` (extension PHP `zip`)** : assemblage dans un fichier temporaire, relu puis supprimé. La réponse est une `Response` simple : le paquet pèse quelques dizaines de Ko, et un flux n'apporterait rien.
- **`DEFAULT_URI`** : déjà requis en production (`config/packages/routing.yaml`) ; il évite de coder l'adresse en dur et donne un zip juste dans chaque environnement.
- **Paramètre de conteneur (`app.extension_public_key`, `config/services.yaml`)** : la clé publique n'est pas un secret et ne change jamais, un `.env` serait inutile.
- **Ressource API Platform en lecture seule** : même forme que `ExtensionDuplicate` (`Get`, `uriVariables: []`, DTO + Provider). Le pare-feu `api` impose la clé, sans nouvelle règle d'accès.
- **`access_control` existant** : la règle `^/` en `ROLE_USER` couvre la route de téléchargement (R1).
- **`<details>` natif** : la FAQ se replie sans JavaScript ni contrôleur Stimulus, comme le veut la consigne de simplicité de `DESIGN.md`.
- **Ancre `#cle` sur le formulaire de génération** : la page s'allonge, et la réponse du POST doit ramener le membre sur sa clé affichée une seule fois.
- **Comparaison numérique par segment** dans la bulle : la règle 8 du pitch demande « plus récente », pas « différente » ; un poste en avance ne reçoit pas d'alerte.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Construire le zip au build de l'image | Exige `zip` et un script dans le Dockerfile, et un chemin différent en dev et en test : une erreur de paquet n'apparaîtrait qu'en production. |
| Déposer le zip dans `public/` | Servi sans Symfony, il échapperait à R1. |
| Pas de `key`, geste de mise à jour strict | Mesuré au prototype : charger un autre dossier change l'identité et perd la clé stockée. Trop fragile pour les non-développeurs. |
| `key` dans le manifest du dépôt | L'extension locale et celle de production partageraient la même identité sur un poste, et le zip Web Store devrait retirer ce champ, que le Store refuse à l'envoi. |
| Version dans la réponse de `/api/extension/categories` | Mélange deux sujets et modifie un contrat existant, pour économiser un appel. |
| `update_url` auto-hébergée ou fichier `.crx` signé | Hors Web Store, Chrome ne les honore que par politique d'entreprise (écarté par le pitch) ; une extension non empaquetée ne se met pas à jour seule. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Service/ExtensionPackage.php` | `version()` lit le manifest ; `zip()` assemble le paquet (adresse dans `config.js`, `key` dans le manifest) et échoue si l'adresse n'a pas pu être remplacée. |
| `src/ApiResource/ExtensionVersion.php` | DTO en lecture seule, `GET /api/extension/version`, champ `version`. |
| `src/State/ExtensionVersionProvider.php` | Provider : renvoie le DTO à partir d'`ExtensionPackage::version()`. |
| `templates/extension_key/_install.html.twig` | Étapes 1 et 2 : téléchargement, version affichée, navigateurs compatibles, procédure d'installation. |
| `templates/extension_key/_faq.html.twig` | FAQ fixe en `<details>` : installation, mises à jour, clé personnelle, orientation vers l'espace Chat. |
| `tests/Service/ExtensionPackageTest.php` | Contenu du zip, manifest, `config.js`, version, garde sur l'adresse. |
| `tests/Api/ExtensionVersionTest.php` | Réponse avec clé valide, refus sans clé ou avec une clé révoquée. |
| `tests/Controller/ExtensionDownloadTest.php` | Page complète avec ou sans clé, ancre `#cle`, thèmes de la FAQ, téléchargement, accès refusé au visiteur. |
| `tests/E2E/ExtensionUpdateTest.php` | Alerte présente, absente, poste en avance et appel en échec, avec ajout toujours possible. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Controller/ExtensionKeyController.php` | Ajouter `GET /extension/telecharger` (`app_extension_download`), servant `radar-extension.zip` en pièce jointe, sans cache ; transmettre la version à la vue dans `renderPage()`. |
| `templates/extension_key/index.html.twig` | Recomposer la page : en-tête et démo, étapes 1-2 (partial), carte de clé en étape 3 (identifiant `cle`, formulaire de génération vers `#cle`), FAQ (partial). |
| `extension/config.js` | Commentaire réaligné : les deux paquets y mettent leur adresse. |
| `extension/manifest.json` | Version `0.2.0`, condition pour que l'alerte fonctionne à partir de la prochaine version. |
| `extension/popup.html` | Ligne d'alerte masquée en tête de l'écran du formulaire, avec lien `data-radar-link="/extension"`. |
| `extension/popup.js` | Après le chargement des catégories, appel non attendu de `/version`, comparaison avec `chrome.runtime.getManifest().version`, erreurs ignorées. |
| `extension/popup.css` | Style de la ligne d'alerte, en clair et en sombre. |
| `config/services.yaml` | Paramètre `app.extension_public_key`. |
| `docker/Dockerfile` | Ajouter `zip` à `install-php-extensions`. |
| `composer.json`, `composer.lock` | Déclarer `ext-zip`, ce qui fait échouer le build si l'extension manque. |
| `Makefile` | Préciser que `make extension` produit le paquet destiné au Web Store. |
| `docs/stack.md` | Extension `zip` de l'image, service du paquet, règle « tout changement de `extension/` incrémente la version ». |
| `tests/E2E/E2ETestCase.php` | Fermer la barre de profiler sur l'origine de Radar, pour qu'elle ne recouvre plus le bas de l'écran. |
| `tests/E2E/ExtensionTestCase.php` | Simuler `chrome.runtime.getManifest()` avec une version configurable ; accueillir `generateKey()` et `pasteKey()`. |
| `tests/E2E/ExtensionTest.php` | Vérifier qu'aucun appel `/version` ne part avec une clé révoquée ; céder ses deux aides à `ExtensionTestCase`. |

## Hors scope

- **Refonte de `make extension`** : la cible reste en shell et sert le futur zip Web Store ; la partager avec le service PHP serait spéculatif.
- **Correction de `public/confidentialite-extension.html`** : le texte dit que l'adresse n'est envoyée qu'à l'ajout, alors que la bulle l'envoie à l'ouverture pour détecter un doublon. Écart antérieur à la story, à traiter à part.
- **Mise en cache du zip** : le volume ne le justifie pas.
- **Détection de la version installée côté page** : impossible sans ouvrir une communication entre la page et l'extension (hors scope du pitch).
- **Ajustement de `DEFAULT_URI` dans `.env`** : `http://radar.wip` reste la valeur par défaut ; la Symfony CLI fournit au serveur web local une adresse en `https://radar.wip`, sans `.env.local`.

## Impacts transverses

- **Cloisonnement des données** : non. Le paquet et la version sont identiques pour tous (R8), aucune donnée de membre n'y entre.
- **Déclinaisons / thèmes** : la page suit le thème existant, clair et sombre. La bulle suit `prefers-color-scheme` dans `popup.css`.
- **Traduction / i18n** : libellés français en dur, comme le reste du projet (R11).
- **API / exposition externe** : une opération ajoutée, `GET /api/extension/version`, joignable uniquement avec une clé valide. Elle ne révèle aucun contenu de Radar (règle 11 du pitch). La bulle ne transmet aucune donnée nouvelle : elle reçoit la version et compare localement.
- **Droits d'accès** : mécanismes existants suffisants, pare-feu `main` pour la page et le zip, pare-feu `api` pour la version.
- **Emails / notifications** : non.
- **Migration de données** : aucune.
- **Comportement par défaut** : inchangé pour un membre sans extension, hormis le contenu de la page. Les clés existantes restent valables.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/ExtensionPackage.php` | unit | Fichiers attendus dans le zip, `manifest.json` portant `key` et la version, `config.js` visant `DEFAULT_URI` sans `radar.wip`, exception si l'adresse à remplacer manque. |
| `src/Controller/ExtensionKeyController.php` | functional | Bouton, version, procédure et FAQ visibles avec et sans clé ; zip de la version affichée en pièce jointe, nommé `radar-extension.zip`, sans cache ; visiteur redirigé vers la connexion sur la page et sur le fichier. |
| `src/State/ExtensionVersionProvider.php` | functional | Clé valide : version du manifest ; sans clé ou clé révoquée : 401. |
| `extension/popup.js` | E2E | Alerte avec lien quand le poste est en retard, ajout toujours possible ; pas d'alerte à version égale ou en avance ; pas d'alerte et ajout fonctionnel si l'appel échoue ; aucun appel de version avec une clé révoquée. |

**Hors scope tests** :

- **Installation réelle, icône, clic droit, mise à jour et délai de 15 secondes** : Playwright PHP ne charge pas d'extension (story 014). Couverts par la recette manuelle, condition de livraison.
- **Lancement réel en Chrome de l'extension empaquetée** : vérifié par la recette ; les tests unitaires contrôlent le contenu du paquet.
- **Lisibilité sombre et mobile** : contrôle visuel à l'étape de la page, sans test dédié.

## Ordre d'exécution

1. [x] **Paquet de l'extension**
   - Objectif : `ExtensionPackage` produit un zip complet et expose la version. Le porteur génère une paire RSA 2048, conserve la clé privée hors dépôt, et la clé publique (DER en base64) va dans `app.extension_public_key`.
   - Fichiers : `src/Service/ExtensionPackage.php`, `config/services.yaml`, `docker/Dockerfile`, `composer.json`, `composer.lock`, `tests/Service/ExtensionPackageTest.php`.
   - Vérification : `tests/Service/ExtensionPackageTest.php` vert, `make lint`.
   - Commitable seule : oui.

2. [x] **Téléchargement**
   - Objectif : route `app_extension_download`, version transmise à la vue.
   - Fichiers : `src/Controller/ExtensionKeyController.php`, `tests/Controller/ExtensionDownloadTest.php`.
   - Vérification : tests fonctionnels du téléchargement et du refus au visiteur.
   - Commitable seule : oui.

3. [x] **Ressource de version**
   - Objectif : `GET /api/extension/version` protégée par la clé.
   - Fichiers : `src/ApiResource/ExtensionVersion.php`, `src/State/ExtensionVersionProvider.php`, `tests/Api/ExtensionVersionTest.php`.
   - Vérification : `tests/Api/` vert, y compris les tests de portée existants.
   - Commitable seule : oui.

4. [x] **Alerte dans la bulle**
   - Objectif : ligne d'alerte non bloquante, version `0.2.0`.
   - Fichiers : `extension/manifest.json`, `extension/popup.html`, `extension/popup.js`, `extension/popup.css`, `tests/E2E/ExtensionTestCase.php`, `tests/E2E/ExtensionTest.php`, `tests/E2E/ExtensionUpdateTest.php`.
   - Vérification : `make playwright`, avec simulation d'échec de l'appel par `fetch` dans le script d'initialisation (`context->route()` bloque les navigations, story 014).
   - Commitable seule : oui, après l'étape 3.

5. [x] **Page complète**
   - Objectif : parcours en trois étapes et FAQ, ancre `#cle`.
   - Fichiers : `templates/extension_key/index.html.twig`, `templates/extension_key/_install.html.twig`, `templates/extension_key/_faq.html.twig`, `tests/Controller/ExtensionDownloadTest.php`.
   - Vérification : tests fonctionnels verts ; contrôle visuel en clair, en sombre et sur mobile ; génération d'une clé ramenée sur la carte.
   - Commitable seule : oui.

6. [x] **Documentation et QA automatique**
   - Objectif : documentation à jour, suite complète verte.
   - Fichiers : `Makefile`, `docs/stack.md`.
   - Vérification : `make lint`, `make phpunit`, `make playwright`.
   - Commitable seule : oui.

7. [ ] **Recette manuelle locale, avant commit final**
   - Objectif : prouver le parcours réel sur Chrome et Edge, avec le zip servi par le site local, qui vise déjà `https://radar.wip`.
   - Fichiers : ajustements éventuels de `templates/extension_key/_faq.html.twig`.
   - Vérification : installation via « Charger l'extension non empaquetée » en suivant la seule procédure de la page, clé collée, icône, clic droit, ajout en moins de 15 secondes ; passage à une version supérieure (remplacement du contenu du dossier + « Recharger », puis chargement d'un autre dossier) avec clé conservée ; résultats consignés dans le compte rendu.
   - Commitable seule : non, elle valide la story.

8. [ ] **Contrôle en production, avant l'annonce Chat**
   - Objectif : valider le déploiement avant toute diffusion.
   - Fichiers : aucun.
   - Vérification : valeur de `SYMFONY_TRUSTED_PROXIES` confirmée sur le serveur (dette n°1 de la story 014) ; zip téléchargé depuis `https://radar.mustiere.fr`, ajout d'un lien réussi.
   - Commitable seule : non concerné.

## Critères de sortie

- [x] `make lint` à zéro finding, `make phpunit` et `make playwright` verts, sans nouvelle régression.
- [x] Le zip servi porte la version du manifest, la `key` publique et l'adresse `DEFAULT_URI` ; `extension/manifest.json` reste sans `key`.
- [x] `extension/config.js` vise toujours `https://radar.wip` dans le dépôt.
- [x] `GET /api/extension/version` répond 401 sans clé valide.
- [ ] L'image de production contient l'extension PHP `zip` et le dossier `extension/`, et le téléchargement y répond 200.
- [x] La clé privée n'est pas dans le dépôt.
- [ ] Recette Chrome et Edge consignée, mise à jour sans perte de clé incluse.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| « Charger l'extension non empaquetée » se comporte autrement que `--load-extension`, seul mode prototypé | moyenne | Recette de l'étape 7 sur Chrome et Edge ; le remplacement du même dossier, le geste principal de la FAQ, ne dépend pas de la `key`. |
| Extension PHP `zip` ou dossier `extension/` absent en production | faible | `ext-zip` fait échouer le build ; contrôle réel à l'étape 8, avant l'annonce. |
| `DEFAULT_URI` erroné : zip visant la mauvaise adresse | faible | Variable déjà exploitée par les liens absolus des annonces Chat, test unitaire de `config.js`, contrôle à l'étape 8. |
| Version non incrémentée après un changement de `extension/` : aucune alerte | moyenne | Règle notée dans `docs/stack.md`, à vérifier à chaque review touchant `extension/`. |
| Identité différente au passage au Web Store : clés à recoller | moyenne | Clé privée conservée pour tenter de garder l'identité ; à traiter dans la story Web Store. |
| Une écriture de plus à chaque ouverture de la bulle (`markExtensionKeyUsed`) | faible | Volume faible (50 personnes), base en WAL, déjà deux écritures par ouverture. |

## Questions ouvertes

_(aucune)_

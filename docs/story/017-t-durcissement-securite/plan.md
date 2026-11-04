# Plan technique — Durcir Radar : en-têtes HTTP de sécurité, dépendances patchées, connexion sans fuite et annonces Chat neutralisées

> **But** : figer le comment des quatre correctifs retenus de l'audit de sécurité du 2026-11-03 — brique, métriques, ordre et retour arrière.
> **Registre** : technique
> **Story** : `docs/story/017-t-durcissement-securite/`
> **Amont** : aucun

## Motivation

L'audit de sécurité du 2026-11-03 (revue de code, `composer audit`, en-têtes de la production lus par `curl`) n'a relevé aucune faille critique ni élevée, mais quatre points corrigeables à faible coût :

- **M1 — en-têtes HTTP absents.** `https://radar.mustiere.fr` ne renvoie ni `Strict-Transport-Security`, ni `X-Frame-Options`, ni `Content-Security-Policy`, et expose `X-Powered-By: PHP/8.5.12`. Le Caddy hôte (`deploy.sh:101-110`) se contente d'un `reverse_proxy`, l'application ne pose aucun en-tête. Le back office est donc encadrable dans une iframe : les boutons « Désactiver », « Retirer », « Générer la clé » sont des POST en un clic, que le jeton CSRF ne protège pas du *clickjacking*.
- **M3 — dépendances vulnérables.** `composer audit` remonte 2 avis : `symfony/mime` 8.1.7 (CVE-2026-45070, corrigé en 8.1.10) et `symfony/expression-language` 8.1.6 (CVE-2026-109449, corrigé en 8.1.9). Aucun n'est exploitable aujourd'hui (`MAILER_DSN=null://null` en prod, aucune expression venue d'un utilisateur), mais l'ensemble des paquets `symfony/*` 8.1 a 1 à 3 patchs de retard (8.1.10 disponible).
- **F1 — énumération des comptes désactivés.** `UserChecker::checkPreAuth` (`src/Security/UserChecker.php:15`) s'exécute sur `CheckPassportEvent` à la priorité 256, avant la vérification du mot de passe (priorité 0), et Symfony n'escamote pas une `CustomUserMessageAccountStatusException` (`vendor/symfony/security-http/Authentication/AuthenticatorManager.php:274`). Avec un **mauvais** mot de passe, la page de connexion affiche « Ce compte est désactivé. » — reproduit par un test fonctionnel temporaire pendant l'audit.
- **M2 — balisage Google Chat injectable.** `LinkAnnouncer::text` (`src/Service/LinkAnnouncer.php:41-65`) insère tels quels le titre de la page (contrôlé par le site cible ou le flux RSS), le contexte, les catégories, le nom de la source et le nom du posteur. Un titre contenant `<users/all>` notifie tout l'espace ; `<https://piege.example|Se connecter>` y affiche un lien déguisé, signé Radar. L'URL d'un article RSS n'est validée que sur son schéma et son hôte (`src/Feed/FeedItem.php:20-31`) : un `|` ou un `>` peut détourner la balise `<url|Ouvrir le lien>`.

**Pourquoi maintenant** : l'outil est en production depuis le 2026-11-02, exposé sur internet, et l'audit vient d'être fait — les correctifs sont petits et indépendants, le coût d'attendre est d'oublier.

## Approche retenue

Quatre correctifs indépendants, chacun dans une étape commitable et déployable seule.

**M3** — montée des patchs `symfony/*` dans la contrainte `8.1.*` (8.1.10) par le gestionnaire de dépendances. `composer.json` déclare les paquets UX en `^3.5.1` (`composer.json:41-48`) : une mise à jour par motif `symfony/*` les ferait passer en 3.6.0, mineure hors périmètre. Ils sont donc épinglés pour la durée de la commande (`--with`), et le diff du lock sert de contrôle. **F1** — le contrôle du compte actif passe de `checkPreAuth` à `checkPostAuth` : il ne s'exécute plus qu'une fois le mot de passe (ou la clé d'extension) vérifié ; le message « Ce compte est désactivé. » est conservé, seul le titulaire le voit. La coupure des sessions ouvertes reste portée par `User::isEqualTo` (`src/Entity/User.php:209`), non touché. **M2** — dans `LinkAnnouncer`, chaque fragment de texte non fiable voit `<` et `>` remplacés par leurs sosies `‹` (U+2039) et `›` (U+203A) ; chaque URL insérée dans le message (lien d'origine, et titre de repli quand la page n'a pas de titre) voit `<`, `>` et `|` encodés en `%3C`, `%3E`, `%7C`. Les deux balises de lien construites par Radar sont les seules à subsister. **M1** — un listener `kernel.response` pose sur toute réponse de requête principale `X-Frame-Options: DENY`, `Content-Security-Policy: frame-ancestors 'none'`, `Referrer-Policy: strict-origin-when-cross-origin`, et `Strict-Transport-Security: max-age=31536000` quand la requête est HTTPS (`Request::isSecure()`, fiable derrière Caddy grâce à `SYMFONY_TRUSTED_PROXIES`). `expose_php = Off` dans `php.ini`, copié dans l'image (`docker/Dockerfile:20`), supprime `X-Powered-By`.

**Impact sur les clients existants** :

- Aucune signature publique modifiée : `LinkAnnouncer::announce`, `UserChecker` et les routes gardent leurs contrats.
- Un membre désactivé qui saisit un mauvais mot de passe voit désormais le message d'identifiants invalides, comme pour un compte inconnu ; avec le bon mot de passe, rien ne change.
- Les annonces Chat dont un fragment contient `<` ou `>` s'affichent avec `‹ ›` ; les annonces ordinaires sont inchangées (les tests existants de `LinkAnnouncerTest` restent valables).
- Aucune page de Radar n'est encadrée aujourd'hui (l'extension appelle l'API par `fetch`, les Turbo Frames ne sont pas des iframes) : `DENY` / `frame-ancestors 'none'` ne casse rien. L'extension n'utilise pas de cookie : HSTS ne la touche pas.

### Mécanismes mobilisés

- **Pattern** : listener de réponse (en-têtes), déplacement d'un contrôle d'authentification vers l'étape post-vérification, neutralisation de sortie au point d'assemblage (annonce), montée de patchs.
- **Lib / composant** : existant uniquement — EventDispatcher (`#[AsEventListener]`, déjà utilisé par `src/EventListener/ExtensionApiThrottleListener.php`), `UserCheckerInterface`, Composer. **Aucune nouvelle dépendance.**
- **Mécanisme d'extension** : attribut `#[AsEventListener(event: KernelEvents::RESPONSE)]` sur un service autoconfiguré ; option `--with` de Composer pour épingler temporairement UX ; directive `php.ini` déjà copiée par l'image. Aucune modification de `vendor/`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| En-têtes posés par le Caddy hôte (`deploy.sh`) | Ni versionnés avec l'app ni testables automatiquement : une régression ne se verrait qu'au `curl` manuel après déploiement. |
| `nelmio/security-bundle` | Nouvelle dépendance taillée pour une CSP complète avec nonces, hors périmètre ; quatre en-têtes fixes tiennent dans un listener. |
| HSTS en montée progressive (`max-age=300` puis 1 an) | Second déploiement pour un risque quasi nul : le TLS est géré automatiquement par Caddy, sans `includeSubDomains` ni `preload` rien n'engage `mustiere.fr`. |
| Mise à jour limitée à `symfony/mime` et `symfony/expression-language` | Lock mi-8.1.7 mi-8.1.10 entre composants qui se testent ensemble ; les autres patchs restent en retard. |
| Mise à jour incluant UX 3.6 | Mineure du front (Live, Turbo, Stimulus) sans gain de sécurité connu : risque de régression sans contrepartie. |
| Message générique même avec le bon mot de passe (F1) | Le membre désactivé croirait à une faute de frappe ; seul le titulaire du mot de passe voit le message, il n'apprend rien qu'il ne sache. |
| Supprimer `<` `>` (M2) | Dénature des titres légitimes (« Array<T> en PHP » → « ArrayT en PHP »). |
| Entités `&lt;` `&gt;` (M2) | Rien ne garantit que Google Chat décode les entités dans le texte d'un webhook : risque d'afficher `&lt;` en clair. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/EventListener/SecurityHeadersListener.php` | Pose `X-Frame-Options`, `Content-Security-Policy: frame-ancestors 'none'`, `Referrer-Policy` sur la réponse principale, et HSTS d'un an si la requête est HTTPS. |
| `tests/Controller/SecurityHeadersTest.php` | En-têtes présents sur une page, l'API (401), un média, une erreur 404 ; HSTS seulement en HTTPS. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `composer.lock` | Paquets `symfony/*` 8.1 en 8.1.10 (`symfony/expression-language` et `symfony/twig-bundle` en 8.1.9, dernière publiée) ; UX reste en 3.5.1. |
| `src/Security/UserChecker.php` | Contrôle du compte actif déplacé de `checkPreAuth` vers `checkPostAuth`. |
| `tests/Security/UserCheckerTest.php` | Les deux cas portent sur `checkPostAuth` ; `checkPreAuth` accepte un compte désactivé. |
| `tests/Controller/AdminUserStatusTest.php` | Nouveau cas : compte désactivé + mauvais mot de passe → message d'identifiants invalides, sans mention de la désactivation. |
| `src/Service/LinkAnnouncer.php` | Sosies `‹ ›` sur titre, contexte, catégories, source et posteur ; `%3C %3E %7C` sur l'URL du lien et le titre de repli. |
| `tests/Service/LinkAnnouncerTest.php` | Un cas d'entrée hostile par fragment (`<users/all>`, `<https://x|y>`) et une URL RSS contenant `|` et `>`. |
| `php.ini` | `expose_php = Off`. |

## Hors scope

- **CSP complète** (`script-src`, `style-src`…) : Live Components, Turbo et l'importmap inline exigent des nonces — chantier distinct.
- **F2 — protections de l'admin permanent** (email et mot de passe modifiables par tout admin) : question de confiance entre admins, pas un correctif.
- **F3 — durcissement du conteneur** (utilisateur non root, `.env.dev` exclu de l'image), hors `expose_php` qui est inclus.
- **F4 — déconnexion par GET sans CSRF** : impact quasi nul avec `SameSite=Lax`.
- **Points informationnels de l'audit** (garde `..git` de `GitHubRepository`, reprise attribuable à un collègue, limiteur par domaine).
- **Caractères de mise en forme Chat** (`*`, `_`, `~`, `` ` ``) : n'altèrent que l'apparence, ne créent ni mention ni lien.
- **Paquets UX 3.6 et `symfony/ai-*` 0.15** : mineures sans avis de sécurité.

## Impacts transverses

- **Modules clients impactés** : pare-feux `main` et `api` (même `UserChecker`) ; handler d'aperçu qui appelle `LinkAnnouncer` ; toutes les réponses HTTP (listener).
- **Migration de données** : aucune.
- **Impacts prod / infra** : rebuild de l'image (lock et `php.ini`), déploiement standard par `./deploy.sh` (sans `--reset`). Rien à configurer côté Caddy ni DNS.
- **Droits d'accès** : inchangés.
- **Sécurité** : vecteurs supprimés — clickjacking du back office, fuite de version PHP, énumération des comptes désactivés, injection de mentions et de liens dans Chat, deux CVE. Pas de nouveau vecteur. Pour l'API, un compte désactivé reste refusé par `ExtensionKeyManager::authenticate` avant même le `UserChecker` (`src/Service/ExtensionKeyManager.php:51`).

## Rollback et kill switch

- **Kill switch** : aucun, assumé. Ce sont des correctifs de sécurité sans chemin critique nouveau ni dépendance externe : le retour arrière est un `git revert` du commit de l'étape concernée puis `./deploy.sh`. Chaque étape étant isolée, une régression n'oblige à revenir que sur elle.
- **Comportement en panne de dépendance** : aucune nouvelle dépendance d'infra — logique entièrement en processus.
- **Cohabitation ancienne/nouvelle version** : sans objet (déploiement avec arrêt du web et du worker, `deploy.sh` étape 4).
- **Sessions / cookies** : aucune session perdue (sessions sur le volume `data`). HSTS est mémorisé un an par les navigateurs : un retour arrière de M1 ne le retire pas, sans conséquence tant que le site reste en HTTPS.

## Métriques (baseline → cible)

| Métrique | Baseline | Cible | Méthode de mesure |
|---|---|---|---|
| Avis de sécurité Composer | 2 (`d1e8915`) | **0** | `symfony composer audit` |
| Paquets UX modifiés par M3 | n/a | **0** (3.5.1) | `git diff composer.lock` |
| Message affiché, compte désactivé + mauvais mot de passe | « Ce compte est désactivé. » (`d1e8915`, test temporaire de l'audit) | **message d'identifiants invalides** | `tests/Controller/AdminUserStatusTest.php` |
| Balises Chat non construites par Radar dans une annonce dont les fragments sont hostiles | ≥ 1 par fragment (`d1e8915`, lecture de code) | **0** | `tests/Service/LinkAnnouncerTest.php` |
| En-têtes attendus sur `https://radar.mustiere.fr/login` (HSTS, `X-Frame-Options`, `frame-ancestors`, absence de `X-Powered-By`) | 0 / 4 (`curl -D`, 2026-11-03) | **4 / 4** | `curl -sS -o /dev/null -D - https://radar.mustiere.fr/login` |

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Security/UserChecker.php` | unit | `checkPostAuth` refuse un compte désactivé avec le message ; `checkPreAuth` laisse passer un compte désactivé ; un compte actif passe les deux. |
| `src/Security/UserChecker.php` | functional | Compte désactivé + mauvais mot de passe → identifiants invalides, sans « désactivé » ; + bon mot de passe → « Ce compte est désactivé. » (cas existant de `AdminUserStatusTest`) ; clé d'extension d'un compte désactivé toujours refusée (tests existants de `tests/Api/`). |
| `src/Service/LinkAnnouncer.php` | unit | Entrée hostile dans le titre, le contexte, une catégorie, le nom de source et le posteur → `‹ ›`, aucune occurrence de `<users/` ; URL RSS contenant `|` et `>` → encodée, la balise de lien reste fermée ; titre de repli (URL) encodé ; les cas nominaux existants inchangés. |
| `src/EventListener/SecurityHeadersListener.php` | functional | `X-Frame-Options: DENY`, `frame-ancestors 'none'`, `Referrer-Policy` sur une page HTML, une réponse de l'API (401), un média et une 404 ; HSTS présent en HTTPS (`HTTPS=on`), absent en HTTP. |
| Mise à jour des dépendances | functional + E2E | Suites `make phpunit` et `make playwright` vertes après la montée de version. |

**Hors scope tests** :

- La suppression de `X-Powered-By` dépend du `php.ini` de l'image : vérifiée par `curl` sur la prod, cf. §Ordre d'exécution, étape 5.
- La présence des en-têtes derrière Caddy (HTTPS vu par l'app grâce aux proxys de confiance) ne se vérifie qu'en production : `curl -D` après déploiement.
- Le rendu réel des sosies `‹ ›` dans Google Chat : vérification visuelle sur une annonce réelle, si l'occasion se présente.

## Ordre d'exécution

1. [x] **M3 — patchs `symfony/*` 8.1**
   - Objectif : `composer audit` à 0 avis, UX inchangé. Commande : `symfony composer update "symfony/*" -W --with symfony/ux-live-component:3.5.1 --with symfony/ux-turbo:3.5.1 --with symfony/ux-icons:3.5.1 --with symfony/ux-toolkit:3.5.1 --with symfony/stimulus-bundle:3.5.1` (compléter les `--with` si le diff montre un autre paquet UX en mouvement, par exemple `symfony/ux-twig-component`).
   - Fichiers : `composer.lock`.
   - Vérification : `symfony composer audit` = 0 avis ; `git diff composer.lock` sans paquet UX ; `make lint`, `make phpunit`, `make playwright` verts.
   - Commitable seule : oui.

2. [x] **F1 — contrôle du compte actif après le mot de passe**
   - Objectif : plus aucune mention de la désactivation sans le bon mot de passe.
   - Fichiers : `src/Security/UserChecker.php`, `tests/Security/UserCheckerTest.php`, `tests/Controller/AdminUserStatusTest.php`.
   - Vérification : le nouveau cas échoue avant la modification puis passe ; `make phpunit` vert, dont `tests/Api/`.
   - Commitable seule : oui.

3. [x] **M2 — neutralisation des annonces Chat**
   - Objectif : seules les deux balises de lien construites par Radar subsistent dans une annonce.
   - Fichiers : `src/Service/LinkAnnouncer.php`, `tests/Service/LinkAnnouncerTest.php`.
   - Vérification : cas hostiles rouges avant, verts après ; cas existants inchangés ; `make lint` sans finding.
   - Commitable seule : oui.

4. [x] **M1 — listener d'en-têtes et `expose_php`**
   - Objectif : quatre en-têtes sur toute réponse principale, HSTS en HTTPS seulement ; `X-Powered-By` supprimé.
   - Fichiers : `src/EventListener/SecurityHeadersListener.php`, `tests/Controller/SecurityHeadersTest.php`, `php.ini`.
   - Vérification : `tests/Controller/SecurityHeadersTest.php` vert ; `make phpunit`, `make playwright` verts ; en local, `curl -sk -D - -o /dev/null https://radar.wip/login` montre les en-têtes et la barre de debug du profiler s'affiche toujours.
   - Commitable seule : oui.

5. [ ] **Bascule prod**
   - Objectif : déployer les quatre étapes (`./deploy.sh`, sans `--reset`).
   - Fichiers : aucun.
   - Vérification : `curl -sS -o /dev/null -D - https://radar.mustiere.fr/login` montre `strict-transport-security`, `x-frame-options: DENY`, `content-security-policy: frame-ancestors 'none'`, `referrer-policy`, et plus de `x-powered-by` ; connexion, ajout d'un lien et extension fonctionnent.
   - Commitable seule : non — dépend du déploiement.

6. [x] **Documentation**
   - `docs/stack.md` : versions résolues (Symfony 8.1.10), en-têtes de sécurité posés par l'app.

## Critères de sortie

- [ ] `make phpunit` et `make playwright` verts.
- [ ] `make lint` sans finding.
- [ ] `symfony composer audit` : 0 avis.
- [ ] Aucun paquet UX modifié dans `composer.lock`.
- [ ] Compte désactivé + mauvais mot de passe : message d'identifiants invalides (test vert).
- [ ] Annonce aux fragments hostiles : 0 balise Chat non construite par Radar (tests verts).
- [ ] En local : en-têtes présents sur `https://radar.wip/login`.
- [ ] Bascule prod validée : 4 / 4 en-têtes attendus sur `https://radar.mustiere.fr/login`.
- [ ] Retour arrière documenté (pas de kill switch, `git revert` + `./deploy.sh` par étape).
- [ ] `docs/stack.md` aligné.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Un patch 8.1.x casse un comportement | faible | Patchs seuls, suites PHPUnit et E2E complètes à l'étape 1, avant tout autre changement. |
| `--with` incomplet : un paquet UX bouge quand même | moyenne | Diff du lock contrôlé ; ajouter le `--with` manquant et relancer. |
| `X-Frame-Options: DENY` casse le profiler en dev (panneaux encadrés) | faible | Vérifié à l'étape 4 ; si besoin, ne pas poser l'en-tête sur `/_profiler` et `/_wdt` (dev seulement). |
| HSTS mémorisé un an alors que le site repasserait en HTTP | faible | TLS automatique par Caddy ; pas d'`includeSubDomains` ni de `preload`, aucun autre domaine engagé. |
| Le déplacement en post-auth laisse passer un compte désactivé quelque part | faible | Tests existants de désactivation (formulaire et API) rejoués ; l'API refuse aussi en amont (`ExtensionKeyManager`). |
| Mago refuse un nombre de méthodes trop élevé dans un fichier de test | faible | Découper le test comme pour la story 015 (`AdminUserWebTestCase`). |

## Questions ouvertes

_(aucune)_

# Stack technique — Radar

> Dernière mise à jour : 2026-11-02 — cartographie factuelle de la stack. Chaque entrée est prouvée par un fichier du dépôt (source entre parenthèses) ou marquée _non renseigné_. Les briques prévues mais pas encore en place figurent dans « Cible non encore en place ».

## Vue d'ensemble

Monolithe Symfony 8.1 rendu côté serveur (Twig + Symfony UX : Live Components, Stimulus, Turbo), base SQLite, assets via AssetMapper sans bundler. Amorcé depuis `gabrielmustiere/symfony-template`. Cible de production : un container FrankenPHP unique sur un VPS existant.

| Couche | Techno principale |
|---|---|
| Langage(s) | PHP 8.5 |
| Backend | Symfony 8.1, Doctrine ORM 3.7, Messenger |
| Frontend | Twig + Symfony UX (Live Component, Stimulus, Turbo), Tailwind CSS 4 + Flowbite 4, AssetMapper |
| Données | SQLite |
| Ops | VPS existant, container FrankenPHP (cible) |
| DevOps | GitHub (`gabrielmustiere/radar`), CI à construire |

## Langages & runtimes

- **PHP** `>=8.5` — source : `composer.json` ; épinglé `8.5` — source : `.php-version`
- **Node** — outillage de dev uniquement (Tailwind, serveur embarqué de Playwright PHP qui exige Node 20+), aucune contrainte de version déclarée — source : `package.json`

## Backend

- **Framework** : Symfony `8.1.*`, résolu `v8.1.11` (`composer.json`, `composer.lock`)
- **ORM / données** : Doctrine ORM `^3.7.2` (résolu `3.7.2`) + Doctrine Migrations (`composer.json`, `composer.lock`)
- **Async** : Symfony Messenger, transport Doctrine (`.env` → `MESSENGER_TRANSPORT_DSN=doctrine://default`) ; `sync://` en test (`.env.test`)
- **Planification** : `symfony/scheduler` `8.1.*`, résolu `v8.1.10` — planning `App\Schedule` (`#[AsSchedule]`, relève horaire des sources), consommé par un worker `messenger:consume scheduler_default` (`composer.json`, `composer.lock`, `src/Schedule.php`, `.symfony.local.yaml`)
- **Notifications** : Symfony Notifier, `ChatMessage` routé en `async` (`config/packages/messenger.yaml`) ; bridge Google Chat `symfony/google-chat-notifier` 8.1, transport `googlechat` sur `GOOGLE_CHAT_DSN` (`null://null` par défaut) (`composer.json`, `config/packages/notifier.yaml`, `.env`)
- **HTTP sortant** : Symfony HttpClient `8.1.*`, résolu `v8.1.11` (`composer.lock`) ; client des aperçus `app.preview_http_client` = `NoPrivateNetworkHttpClient` + limiteur par hôte, composé par `App\HttpClient\PreviewHttpClientFactory` (`config/services.yaml`)
- **Limitation de débit** : `symfony/rate-limiter` `8.1.*`, résolu `v8.1.11` — limiteurs `preview_host` (token bucket) et `extension_api_failure` (fenêtre glissante, clés refusées par IP) (`composer.json`, `config/packages/rate_limiter.yaml`)
- **En-têtes de sécurité** : posés par `App\EventListener\SecurityHeadersListener` sur toute réponse principale — `X-Frame-Options: DENY`, `Content-Security-Policy: frame-ancestors 'none'`, `Referrer-Policy: strict-origin-when-cross-origin`, HSTS d'un an en HTTPS ; `expose_php = Off` dans `php.ini`
- **Auth** : Security bundle, `form_login` email / mot de passe, provider entité `User` ; pare-feu `api` sans état (`^/api/`) par `access_token` (clé d'extension, empreinte SHA-256 sur `User`, `App\Security\ExtensionKeyHandler`) (`config/packages/security.yaml`, `src/Entity/User.php`)
- **API** : API Platform `^5.0`, résolu `v5.0.5` — ressources DTO de l'extension navigateur sous `/api/extension` (`src/ApiResource/`, `src/State/`), JSON-LD, sans intégration Doctrine ni documentation exposée (`composer.json`, `config/packages/api_platform.yaml`)
- **Mail** : Symfony Mailer (`composer.json`)

## Frontend

- **Rendu** : Twig + Symfony UX — Live Component `v3.5.1`, Stimulus bundle `v3.5.1`, Turbo `v3.5.1`, UX Icons, UX Toolkit (`composer.lock`, `importmap.php`)
- **Assets** : AssetMapper + importmap, sans bundler JS (`config/packages/asset_mapper.yaml`, `importmap.php`)
- **CSS** : Tailwind CSS `4.3.3` via `symfonycasts/tailwind-bundle` (`config/packages/symfonycasts_tailwind.yaml`, `package-lock.json`) ; Flowbite `4.0.2` (`package-lock.json`, `importmap.php`)
- **Design system** : « Radar », inspiré de Markit — tokens `@theme` (`DESIGN.md`, `assets/styles/app.css`), composants Twig (`templates/components/`)
- **TypeScript** : non

## Données & stockage

- **Base de données** : SQLite — `var/data.db` (dev), `var/data_test.db` (test), `var/data/radar.db` en prod sur le volume `data`, en mode WAL (lectures du web non bloquées par les écritures du worker) (`.env`, `.env.dev`, `.env.test`, `environments/prod/.env.example` → `DATABASE_URL`, `environments/prod/compose.yaml`)
- **File / queue** : table Doctrine via Messenger (voir Backend)
- **Fichiers** : Flysystem — `league/flysystem-bundle` `^3.7` (résolu `3.7.1`), storage `media.storage` : adapter local `var/storage/media` (dev, prod), `memory` en test (`league/flysystem-memory`, dev) — visuels d'aperçu servis par la route authentifiée `/media` (`composer.json`, `config/packages/flysystem.yaml`)
- **Mail (dev)** : Mailpit, en dev uniquement (`compose.yaml`, `.env` → `MAILER_DSN=smtp://localhost:1027`)

## Ops / Infrastructure

- **Conteneurisation** : image FrankenPHP (`dunglas/frankenphp:1-php8.5.12-bookworm`, mode classique, extensions `gd`, `intl`, `pcntl`, `pdo_sqlite`) construite sur le VPS ; Compose de prod : `app` (web, `127.0.0.1:9018`), `worker` (un seul `messenger:consume scheduler_default async`, même image) et `migrate` (profil `tools`, lancé au déploiement) ; volumes `data` (base + sessions) et `storage` (médias) partagés par web et worker (`docker/Dockerfile`, `environments/prod/compose.yaml`)
- **Hébergement de production** : VPS `srv-lwskvm` (partagé avec kadence), `https://radar.mustiere.fr` (`environments/prod/.env.script.example`)
- **CDN / reverse proxy** : Caddy de l'hôte (TLS) → `127.0.0.1:9018`, configuré et rechargé par `deploy.sh` ; proxys de confiance `PRIVATE_SUBNETS` (`deploy.sh`, `environments/prod/.env.example`)
- **Gestion des secrets** : `environments/prod/.env` (non versionné, modèle `.env.example`), copié par `deploy.sh` en `chmod 600` (`.gitignore`, `deploy.sh`)
- **Environnements** : dev (local) + prod (VPS), pas de staging — _source : déclaratif utilisateur_
- **Sauvegarde** : aucune pour l'instant, données sur volumes Docker nommés seulement — _source : déclaratif utilisateur (2026-11-02)_

## DevOps / CI-CD

- **Dépôt** : GitHub, `gabrielmustiere/radar` — _source : déclaratif utilisateur_
- **Pipeline CI** : _non en place_ (aucun `.github/workflows/`)
- **Tests** : PHPUnit `13.3.5` (Unit + Functional) (`composer.lock`, `phpunit.dist.xml`) ; Playwright PHP `1.5.0` (E2E, testsuite `e2e` dans `tests/E2E/`) (`composer.lock`, `phpunit.dist.xml`)
- **Analyse statique / style** : Mago `1.59.0` — formatter, linter (intégrations Symfony + PHPUnit) et analyseur statique en mode strict, zéro finding sans baseline (`composer.lock`, `mago.toml`)
- **Déploiement** : `./deploy.sh` depuis le poste — git pull sur le VPS, copie du `.env`, build, arrêt web + worker, migrations (jamais de fixtures), redémarrage, Caddy ; `./deploy.sh --reset` (confirmation par le nom du site) efface d'abord la base et recrée le compte `RADAR_USER_EMAIL` / `RADAR_USER_PASSWORD` de `environments/prod/.env.script`, mot de passe passé par l'entrée standard (`app:user:create --password-stdin`) ; autres comptes par `bin/console app:user:create <email>` dans le conteneur `app` (`deploy.sh`, `src/Command/CreateUserCommand.php`)

## Monitoring / observabilité

- **Erreurs / logs** : Monolog (`config/packages/monolog.yaml`) ; en prod, logs du container consultés via `docker logs` — _source : déclaratif utilisateur_
- **Métriques / traces** : aucune — choix assumé

## Outillage de développement local

- **Commandes** : `make init`, `make serve`, `make phpunit`, `make playwright`, `make lint`, `make quality`, `make db-reset`, `make extension`… (`Makefile`)
- **Extension navigateur** : Manifest V3 en JS vanilla, sans build (`extension/`) ; `make extension` construit la version de production (adresse `https://radar.mustiere.fr` dans `config.js`) : un dossier à charger dans Chrome et un zip pour le Chrome Web Store (`var/extension/`) ; le dossier `extension/` du dépôt vise `https://radar.wip` (`extension/config.js`, `Makefile`)
- **Serveur local** : Symfony CLI, proxy HTTPS `https://radar.wip`, workers Messenger (`async`, `scheduler_default`) + Tailwind watch (`.symfony.local.yaml`)
- **Services de dev** : `docker compose up -d` → Mailpit (`compose.yaml`)
- **Assistance IA** : Symfony AI Mate `^0.14` (`composer.json`, `mate/`), serveurs MCP (`.mcp.json`)

## Cible non encore en place

Briques décidées, à construire via `/tech-plan` (pas encore prouvées par un fichier) :

- **Image et CI sur GitHub** — GitHub Actions (lint, tests, build de l'image) ; registre d'image à confirmer (GHCR pressenti).
- **Connexion Google restreinte au domaine Workspace** — exigée par `docs/vision.md` (outil strictement interne) ; remplace ou complète le `form_login` du template.
- **Annonce dans Google Chat** — webhook entrant de l'espace ; dépend de l'autorisation des webhooks par l'admin Workspace (risque externe, `docs/vision.md`).

## Contraintes & dette de stack connues

- PHP `>=8.5` imposé par le socle : l'image FrankenPHP embarque PHP 8.5.12 (`docker/Dockerfile`).
- SQLite impose une seule instance applicative et un volume local (pas de stockage réseau) — cohérent avec un container unique sur un VPS.
- Les visuels d'aperçu (`var/storage/media`) doivent vivre sur le même volume persistant que la base ; un adapter objet (S3 ou équivalent) se branchera dans `config/packages/flysystem.yaml` sans toucher au code (`config/packages/flysystem.yaml`).

## Changelog

- 2026-10-29 — Création — amorcé depuis gabrielmustiere/symfony-template
- 2026-10-29 — Qualité — PHPStan, PHP-CS-Fixer et PHP Insights remplacés par Mago (story 001)
- 2026-10-29 — Enrichir — Backend (notifications) — sync post-livraison de la story 008-f-annonce-google-chat
- 2026-10-30 — Enrichir — Backend (HTTP sortant, limitation de débit), Données (fichiers Flysystem), Contraintes (volume des médias) — sync post-livraison de la story 011-f-cartes-par-source
- 2026-10-30 — Enrichir — Backend (planification), Outillage local (worker scheduler), Cible (workers de production) — sync post-livraison de la story 012-f-sources-automatiques
- 2026-11-02 — Enrichir — Données (base de prod, WAL), Ops (conteneurisation FrankenPHP, reverse proxy, secrets, sauvegarde), DevOps (déploiement), Cible (container de prod livré) — déploiement en production
- 2026-11-02 — Enrichir — Backend (API Platform, pare-feu `api`, limiteur `extension_api_failure`), Outillage (extension navigateur, `make extension`) — implémentation de la story 014-f-extension-navigateur

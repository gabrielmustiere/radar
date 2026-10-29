# Stack technique — Radar

> Dernière mise à jour : 2026-10-29 — cartographie factuelle de la stack. Chaque entrée est prouvée par un fichier du dépôt (source entre parenthèses) ou marquée _non renseigné_. Les briques prévues mais pas encore en place figurent dans « Cible non encore en place ».

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

- **Framework** : Symfony `8.1.*`, résolu `v8.1.7` (`composer.json`, `composer.lock`)
- **ORM / données** : Doctrine ORM `^3.7.2` (résolu `3.7.2`) + Doctrine Migrations (`composer.json`, `composer.lock`)
- **Async** : Symfony Messenger, transport Doctrine (`.env` → `MESSENGER_TRANSPORT_DSN=doctrine://default`) ; `sync://` en test (`.env.test`)
- **Notifications** : Symfony Notifier, `ChatMessage` routé en `async` (`config/packages/messenger.yaml`) — aucun bridge de chat installé
- **HTTP sortant** : Symfony HttpClient `8.1.*`, résolu `v8.1.7` (`composer.lock`)
- **Auth** : Security bundle, `form_login` email / mot de passe, provider entité `User` (`config/packages/security.yaml`, `src/Entity/User.php`)
- **Mail** : Symfony Mailer (`composer.json`)

## Frontend

- **Rendu** : Twig + Symfony UX — Live Component `v3.5.1`, Stimulus bundle `v3.5.1`, Turbo `v3.5.1`, UX Icons, UX Toolkit (`composer.lock`, `importmap.php`)
- **Assets** : AssetMapper + importmap, sans bundler JS (`config/packages/asset_mapper.yaml`, `importmap.php`)
- **CSS** : Tailwind CSS `4.3.3` via `symfonycasts/tailwind-bundle` (`config/packages/symfonycasts_tailwind.yaml`, `package-lock.json`) ; Flowbite `4.0.2` (`package-lock.json`, `importmap.php`)
- **Design system** : « Paper » — tokens `@theme` (`DESIGN.md`, `assets/styles/app.css`), composants Twig (`templates/components/`)
- **TypeScript** : non

## Données & stockage

- **Base de données** : SQLite — `var/data.db` (dev), `var/data_test.db` (test) (`.env`, `.env.dev`, `.env.test` → `DATABASE_URL`)
- **File / queue** : table Doctrine via Messenger (voir Backend)
- **Mail (dev)** : Mailpit, en dev uniquement (`compose.yaml`, `.env` → `MAILER_DSN=smtp://localhost:1027`)

## Ops / Infrastructure

- **Conteneurisation** : _non en place_ — voir « Cible non encore en place »
- **Hébergement de production** : serveur / VPS existant de l'organisation — _source : déclaratif utilisateur_
- **CDN / reverse proxy** : _non renseigné_
- **Gestion des secrets** : _non renseigné_
- **Environnements** : dev (local) + prod (VPS), pas de staging — _source : déclaratif utilisateur_
- **Sauvegarde** : backup du volume qui porte la base SQLite — _source : déclaratif utilisateur_ ; mécanisme précis _non renseigné_

## DevOps / CI-CD

- **Dépôt** : GitHub, `gabrielmustiere/radar` — _source : déclaratif utilisateur_
- **Pipeline CI** : _non en place_ (aucun `.github/workflows/`)
- **Tests** : PHPUnit `13.3.5` (Unit + Functional) (`composer.lock`, `phpunit.dist.xml`) ; Playwright PHP `1.5.0` (E2E, testsuite `e2e` dans `tests/E2E/`) (`composer.lock`, `phpunit.dist.xml`)
- **Analyse statique / style** : Mago `1.59.0` — formatter, linter (intégrations Symfony + PHPUnit) et analyseur statique en mode strict, zéro finding sans baseline (`composer.lock`, `mago.toml`)
- **Déploiement** : _non renseigné_

## Monitoring / observabilité

- **Erreurs / logs** : Monolog (`config/packages/monolog.yaml`) ; en prod, logs du container consultés via `docker logs` — _source : déclaratif utilisateur_
- **Métriques / traces** : aucune — choix assumé

## Outillage de développement local

- **Commandes** : `make init`, `make serve`, `make phpunit`, `make playwright`, `make lint`, `make quality`, `make db-reset`… (`Makefile`)
- **Serveur local** : Symfony CLI, proxy HTTPS `https://radar.wip`, workers Messenger + Tailwind watch (`.symfony.local.yaml`)
- **Services de dev** : `docker compose up -d` → Mailpit (`compose.yaml`)
- **Assistance IA** : Symfony AI Mate `^0.14` (`composer.json`, `mate/`), serveurs MCP (`.mcp.json`)

## Cible non encore en place

Briques décidées, à construire via `/tech-plan` (pas encore prouvées par un fichier) :

- **Container de production FrankenPHP** — image unique (FrankenPHP + app + assets compilés), volume persistant pour SQLite, déployée sur le VPS existant.
- **Image et CI sur GitHub** — GitHub Actions (lint, tests, build de l'image) ; registre d'image à confirmer (GHCR pressenti).
- **Connexion Google restreinte au domaine Workspace** — exigée par `docs/vision.md` (outil strictement interne) ; remplace ou complète le `form_login` du template.
- **Annonce dans Google Chat** — webhook entrant de l'espace ; dépend de l'autorisation des webhooks par l'admin Workspace (risque externe, `docs/vision.md`).

## Contraintes & dette de stack connues

- PHP `>=8.5` imposé par le socle : l'image FrankenPHP devra embarquer PHP 8.5.
- SQLite impose une seule instance applicative et un volume local (pas de stockage réseau) — cohérent avec un container unique sur un VPS.

## Changelog

- 2026-10-29 — Création — amorcé depuis gabrielmustiere/symfony-template
- 2026-10-29 — Qualité — PHPStan, PHP-CS-Fixer et PHP Insights remplacés par Mago (story 001)

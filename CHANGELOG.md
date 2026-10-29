# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Chaque version porte un **titre** et distingue les **évolutions fonctionnelles**
(perceptibles à l'usage) des **évolutions techniques** (internes, outillage, plomberie).

## [Unreleased]

## [0.2.0] - 2026-10-29 — MVP de la veille partagée

### ✨ Fonctionnel
- **Ajout d'un lien en quelques secondes** — coller l'adresse, choisir une ou deux catégories-besoins (ou en créer une), ajouter un contexte si on veut.
- **Cartes enrichies** — titre, image et description de la source, type de source (GitHub, YouTube, X, image, article), posteur, date et catégories lisibles sans clic, avec une carte de repli soignée quand la source ne livre rien.
- **Doublons évités** — un lien déjà présent est refusé et sa carte est montrée ; chaque carte a sa propre page.
- **Reprise de l'historique Google Chat** — reprendre un lien avec sa date et son posteur d'origine, marqué « repris de Chat » et jamais réannoncé.
- **Exploration par catégorie** — les catégories-besoins et leur nombre de liens dans la barre latérale, une page par catégorie, et l'ouverture du lien d'origine depuis chaque carte.
- **Recherche par mot** — depuis la barre du haut, dans les titres, descriptions, contextes, adresses et catégories.
- **Annonces Google Chat** — chaque nouveau lien est annoncé une fois dans l'espace de l'équipe avec un accès à sa carte (à activer en renseignant l'espace).
- **Fil des derniers liens** — l'accueil regroupe les liens par jour, page par page ; catégories et recherche sont paginées aussi.
- **Nouvelle apparence** — interface claire et sombre inspirée de Markit, avec des illustrations pour l'accueil et la connexion.

### 🔧 Technique
- **Mesure de la North Star** — chaque ouverture d'un lien depuis Radar est enregistrée avec l'âge du lien, en vue des indicateurs mensuels.
- **Outillage qualité Mago** — formateur, linter et analyse statique uniques à la place de PHPStan, PHP-CS-Fixer et PHP Insights ; les cibles `make phpstan`, `php-cs-fix`, `php-cs-check` et `insights` sont remplacées par `make lint` et `make quality`.
- **Aperçus en tâche de fond** — construction des aperçus et envoi des annonces via Messenger, client HTTP interdit au réseau interne.
- **Interface et messages en français** — locale par défaut `fr`.

## [0.1.0] - 2026-10-29 — Socle applicatif & outillage qualité

### ✨ Fonctionnel
- **Connexion par email et mot de passe** — accès à l'outil réservé aux comptes enregistrés.
- **Tableau de bord** — page d'accueil après connexion, avec le menu utilisateur et la déconnexion.

### 🔧 Technique
- **Socle Symfony 8.1 / PHP 8.5** — SQLite, Messenger, Tailwind CSS 4, Symfony UX et design system « Paper » avec sa page de démonstration.
- **Outillage qualité** — PHPUnit 13, PHPStan level 10, PHP-CS-Fixer, PHP Insights et Symfony AI Mate.
- **Tests E2E en PHP** — Playwright PHP remplace Playwright JS : les tests tournent avec PHPUnit (testsuite `e2e`, `make playwright`).

[Unreleased]: https://github.com/gabrielmustiere/radar/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/gabrielmustiere/radar/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/gabrielmustiere/radar/releases/tag/v0.1.0

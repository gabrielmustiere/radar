# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Chaque version porte un **titre** et distingue les **évolutions fonctionnelles**
(perceptibles à l'usage) des **évolutions techniques** (internes, outillage, plomberie).

## [Unreleased]

## [0.1.0] - 2026-10-29 — Socle applicatif & outillage qualité

### ✨ Fonctionnel
- **Connexion par email et mot de passe** — accès à l'outil réservé aux comptes enregistrés.
- **Tableau de bord** — page d'accueil après connexion, avec le menu utilisateur et la déconnexion.

### 🔧 Technique
- **Socle Symfony 8.1 / PHP 8.5** — SQLite, Messenger, Tailwind CSS 4, Symfony UX et design system « Paper » avec sa page de démonstration.
- **Outillage qualité** — PHPUnit 13, PHPStan level 10, PHP-CS-Fixer, PHP Insights et Symfony AI Mate.
- **Tests E2E en PHP** — Playwright PHP remplace Playwright JS : les tests tournent avec PHPUnit (testsuite `e2e`, `make playwright`).

[Unreleased]: https://github.com/gabrielmustiere/radar/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/gabrielmustiere/radar/releases/tag/v0.1.0

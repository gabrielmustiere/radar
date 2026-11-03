# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Chaque version porte un **titre** et distingue les **évolutions fonctionnelles**
(perceptibles à l'usage) des **évolutions techniques** (internes, outillage, plomberie).

## [Unreleased]

## [1.0.0] - 2026-11-03 — Menu allégé et reset de production

### ✨ Fonctionnel
- **Menu du compte allégé** — les entrées « Mon profil » et « Paramètres », qui ne menaient nulle part, disparaissent.

### 🔧 Technique
- **Remise à zéro de la production sur demande** — `./deploy.sh --reset`, confirmé en tapant le nom du site, efface la base, rejoue les migrations et recrée le compte admin défini dans le fichier de déploiement local ; `./deploy.sh` seul conserve les données.
- **`app:user:create --password-stdin`** — le mot de passe peut être lu sur l'entrée standard, sans apparaître dans la ligne de commande.

## [0.10.0] - 2026-11-03 — Comptes et équipes

### ✨ Fonctionnel
- **Gestion des comptes** — un admin crée les comptes depuis Radar (email, équipe, mot de passe provisoire affiché pour être transmis), les modifie, leur définit un nouveau mot de passe et leur donne ou retire le statut admin, sans passer par le serveur.
- **Désactivation d'un compte** — un collègue parti ne peut plus se connecter, sa session ouverte est coupée et sa clé d'extension refusée ; ses liens restent, à son nom. Un compte se réactive avec son mot de passe et son équipe.
- **Admins portés par le compte** — la page « Administrateurs » disparaît : les emails de l'ancienne liste qui ont un compte deviennent admins. Un admin ne peut ni se désactiver ni se retirer le statut, et l'admin permanent reste protégé.
- **Équipes** — chaque membre appartient à une équipe (Développement, Produit, QA ou Management). Une catégorie prend l'équipe de la personne qui la crée, et un admin peut la corriger depuis la page « Catégories ».
- **Sidebar centrée sur la lecture** — « Mon équipe » en tête avec les catégories de votre équipe, puis les autres catégories avec leur équipe, triées dans l'ordre alphabétique français. Sources, Comptes et Catégories passent dans une section « Administration » du menu du compte, visible des admins seulement.
- **Listes d'administration plus lisibles** — boutons d'action visibles, listes déroulantes au chevron bien placé, et pages Comptes, Catégories et Sources adaptées au mobile.

### 🔧 Technique
- **Migration des admins** — la liste `admin_email` est reprise dans les comptes puis supprimée ; les emails sans compte sont listés dans la sortie de la migration, à lire au déploiement.

## [0.9.3] - 2026-11-03 — Boutons radio noir et blanc

### ✨ Fonctionnel
- **Boutons radio assortis aux cases à cocher** — l'option choisie se repère d'un coup d'œil : rond noir avec un point blanc en thème clair, rond blanc avec un point foncé en thème sombre.

## [0.9.2] - 2026-11-03 — Cases à cocher noir et blanc

### ✨ Fonctionnel
- **Cases à cocher aux couleurs du site** — fini le bleu du navigateur : la case est noire une fois cochée en thème clair, blanche en thème sombre, comme le reste de l'interface (« Se souvenir de moi » à la connexion compris).

## [0.9.1] - 2026-11-02 — Extension branchée sur la production

### ✨ Fonctionnel
- **L'extension parle à la bonne instance** — la version distribuée vise radar.mustiere.fr ; la bulle affiche le serveur auquel elle est reliée, pour repérer d'un coup d'œil une extension mal branchée.
- **Clé plus simple à coller** — la clé collée dans l'extension reste lisible, et le curseur est déjà dans le champ.
- **Copie de la clé plus fiable** — la page « Extension navigateur » affiche la clé en entier et la laisse sélectionnée après « Copier » ; si le navigateur refuse la copie, un message invite à faire Cmd+C.

### 🔧 Technique
- **`make extension`** construit la version de production : un dossier à charger dans Chrome et un zip pour le Chrome Web Store ; le dossier `extension/` du dépôt reste réglé sur le serveur local.

## [0.9.0] - 2026-11-02 — Extension navigateur

### ✨ Fonctionnel
- **Extension navigateur** — ajouter la page que vous lisez à Radar sans quitter l'onglet, depuis l'icône de l'extension ou le clic droit « Ajouter cette page à Radar ». L'adresse est préremplie, avec les mêmes catégories, la même alerte de doublon et la même annonce dans Google Chat. La publication sur le Chrome Web Store est à venir.
- **Clé personnelle de l'extension** — elle se génère depuis le menu du compte (« Extension navigateur »), s'affiche une seule fois, et peut être régénérée ou révoquée. Elle ne sert qu'à ajouter des liens et expire après 90 jours sans usage.

### 🔧 Technique
- **API de l'extension** — API Platform sous `/api/extension`, derrière un pare-feu sans session authentifié par clé (seule son empreinte est conservée). Une adresse est bloquée après 10 clés refusées en 15 minutes.
- **Paquet de l'extension** — `make extension` produit le zip pour le Chrome Web Store, et une page de confidentialité publique est en ligne.

## [0.8.1] - 2026-11-02 — Cartes ajustées aux grands écrans

### ✨ Fonctionnel
- **Plus de colonnes sur grand écran** — le fil ajoute des colonnes au lieu d'étirer les cartes : 5 colonnes en 1920 px, 7 sur un écran 2K, une seule sur tablette étroite. Les cartes gardent une taille lisible et leurs visuels restent nets.
- **Pied de carte lisible sur les cartes étroites** — un lien publié par une source suivie affiche le nom de la source à la place de « radar », au lieu d'un badge tronqué ; « repris de Chat » devient une icône avec infobulle.

## [0.8.0] - 2026-11-02 — Catégories éditables

### ✨ Fonctionnel
- **Renommer une catégorie** — un admin corrige le libellé d'une catégorie depuis sa page, via un crayon à côté du titre qui ouvre une petite modale ; un nom déjà pris est refusé.
- **Bouton « + » pour créer une catégorie** — à côté du champ de saisie des catégories, il crée et sélectionne la catégorie d'un clic, comme Entrée.

## [0.7.0] - 2026-11-02 — Sources suivies & mise en production

### ✨ Fonctionnel
- **Interface en noir et blanc** — l'accent sarcelle disparaît, en clair comme en sombre ; seules les cartes gardent la couleur de leur plateforme (YouTube, GitHub, X).
- **Sources suivies** — un admin enregistre un flux RSS ou Atom avec ses catégories : chaque nouvel article est publié automatiquement, avec un badge « auto » et une carte orange, puis annoncé dans Google Chat. Relève toutes les heures, ou à la demande en reprenant les articles des dernières heures.
- **Gestion des admins** — la page « Administrateurs » liste les personnes autorisées à gérer les sources.
- **Formulaire d'ajout réorganisé** — on dit d'abord pourquoi c'est utile, puis on choisit les catégories, puis on colle l'adresse.
- **Catégories créées à la frappe** — taper un nom puis Entrée crée la catégorie, avec une majuscule initiale, et la sélectionne ; la saisie filtre aussi les catégories existantes.
- **Jusqu'à trois catégories** par lien et par source, au lieu de deux.
- **Compléter un lien déjà présent** — quand l'adresse existe déjà, l'alerte propose d'ajouter vos catégories au lien existant.
- **Ancres respectées** — deux adresses qui ne diffèrent que par leur ancre (`#…`) désignent désormais deux liens distincts.

### 🔧 Technique
- **Déploiement en production** — image FrankenPHP (PHP 8.5), Compose avec web, worker Messenger et migrations, volumes persistants, Caddy de l'hôte ; `deploy.sh` ne s'interrompt plus avant la configuration de Caddy.
- **Comptes et connexion en production** — commande `app:user:create` pour créer les comptes, tentatives de connexion limitées.
- **Relève par Symfony Scheduler** — un message par source sur le transport `async`, parseur RSS/Atom maison.
- **Saisie des catégories en Live Component** — les formulaires des liens et des sources partagent la même saisie ; celui des sources est réservé aux admins.

## [0.6.0] - 2026-10-30 — Fil en mosaïque

### ✨ Fonctionnel
- **Fil continu en mosaïque** — les cartes s'enchaînent sans séparation par jour et s'emboîtent selon leur hauteur, du plus récent au plus ancien, sur le fil comme dans les catégories.
- **Visuel des dépôts GitHub** — propriétaire, nom du dépôt, étoiles et langage à la place de l'image générique de GitHub.
- **Posts X mis en avant** — auteur, @compte et texte du post en tête de carte, plutôt qu'un visuel vide.
- **Barre latérale allégée** — « Design System » et « Tester l'email » n'y figurent plus ; les pages restent accessibles par leur adresse.

### 🔧 Technique
- **Mosaïque en Stimulus** — contrôleur `masonry` (rangées de 1 px, hauteur mesurée en continu) ; sans JS, la grille reste classique.
- **Catégories des fixtures à un mot** — Symfony, IA, Agents, Architecture.

## [0.5.0] - 2026-10-30 — Cartes adaptées à chaque source

### ✨ Fonctionnel
- **Cartes adaptées à la source** — une vidéo YouTube se lance depuis sa carte, un dépôt GitHub affiche ses étoiles et son langage, un article son auteur et sa date de publication, un post X son auteur et son texte.
- **Visuels conservés par Radar** — les images des cartes restent en place des mois plus tard, et le navigateur n'appelle plus les sites des liens.
- **Repli avec l'icône du site** — une carte sans image affiche la favicon du site.

### 🔧 Technique
- **Cascade de sources** — adaptateurs YouTube, GitHub et X, puis oEmbed, OpenGraph, Twitter Cards, JSON-LD et HTML, fusionnés champ par champ.
- **Visuels via Flysystem** — stockage local `var/storage/media` (à placer sur le volume persistant), servi par la route authentifiée `/media`.
- **Commande `app:links:enrich`** — rafraîchit les aperçus échus (`--dry-run`, `--force`), sans annonce ; à lancer après le déploiement.
- **Lecture vidéo mesurée** — lancer une vidéo dans sa carte compte comme une ouverture du lien.
- **Aperçus sécurisés** — réseau interne refusé à chaque redirection, limite par domaine, images vérifiées (5 Mo, 16 Mpx) ; étoiles GitHub si `GITHUB_TOKEN` est renseigné.

## [0.4.1] - 2026-10-29 — Jeu de données réaliste

### 🔧 Technique
- **Fixtures réalistes** — l'installation locale démarre avec 20 vrais liens (dépôts GitHub, vidéos YouTube, posts X, articles) sur Symfony, l'IA, Claude Code et l'architecture logicielle, avec leurs aperçus réels, classés dans 4 catégories-besoins.
- **Tests E2E sur de vrais liens** — l'ajout de lien est testé avec un catalogue de 20 autres liens réels plutôt qu'avec des adresses fictives ; le catalogue se renouvelle avec `make db-reset`.

## [0.4.0] - 2026-10-29 — Recherche intégrée à la liste

### ✨ Fonctionnel
- **Une seule barre de recherche** — la barre du haut filtre directement la liste affichée pendant la frappe, sans changer de page ; les cartes qui restent dans les résultats ne bougent pas, et le mot-clé reste dans l'adresse pour être partagé.
- **Recherche dans une catégorie** — sur la page d'une catégorie, la recherche ne porte que sur ses liens.
- **Recherche sur mobile** — la barre est désormais accessible sur petit écran.
- **Page « Recherche » retirée** — remplacée par le filtre de la liste ; les anciennes adresses `/recherche` ne fonctionnent plus.

### 🔧 Technique
- **Liste en Live Component** — le fil et les catégories partagent un composant `LinkList`, piloté par la barre du haut ; la recherche est intégrée aux requêtes du fil et des catégories.

## [0.3.0] - 2026-10-29 — Interface fluide

### ✨ Fonctionnel
- **Navigation sans à-coup** — les pages se préchargent au survol et le contenu change en fondu, la barre du haut et la barre latérale restent en place.
- **Recherche au fil de la frappe** — les résultats se mettent à jour pendant la saisie, le mot-clé reste dans l'adresse pour être partagé ; la touche « / » place le curseur dans la recherche.
- **Ajout de lien en modale** — « Ajouter un lien » ouvre le formulaire par-dessus la page ; une fois le lien ajouté, le fil s'affiche avec la confirmation. La reprise d'un lien de Chat s'ouvre dans la même modale.
- **Formulaire qui répond tout de suite** — un doublon est signalé dès que l'adresse est saisie, les catégories se grisent au-delà de deux, les erreurs apparaissent au fil de la saisie.
- **Cartes qui s'enrichissent seules** — une carte dont l'aperçu est en construction se met à jour sans recharger la page.

### 🔧 Technique
- **Symfony UX en place** — Live Components pour la recherche, le formulaire d'ajout et les cartes en attente ; Turbo Frame pour la modale ; contrôleurs Stimulus pour la modale et le raccourci clavier.
- **Rafraîchissement plafonné** — une carte en attente interroge le serveur toutes les 3 s et s'arrête au bout de 20 essais si aucun worker ne répond.

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

[Unreleased]: https://github.com/gabrielmustiere/radar/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/gabrielmustiere/radar/compare/v0.10.0...v1.0.0
[0.10.0]: https://github.com/gabrielmustiere/radar/compare/v0.9.3...v0.10.0
[0.9.3]: https://github.com/gabrielmustiere/radar/compare/v0.9.2...v0.9.3
[0.9.2]: https://github.com/gabrielmustiere/radar/compare/v0.9.1...v0.9.2
[0.9.1]: https://github.com/gabrielmustiere/radar/compare/v0.9.0...v0.9.1
[0.9.0]: https://github.com/gabrielmustiere/radar/compare/v0.8.1...v0.9.0
[0.8.1]: https://github.com/gabrielmustiere/radar/compare/v0.8.0...v0.8.1
[0.8.0]: https://github.com/gabrielmustiere/radar/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/gabrielmustiere/radar/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/gabrielmustiere/radar/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/gabrielmustiere/radar/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/gabrielmustiere/radar/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/gabrielmustiere/radar/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/gabrielmustiere/radar/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/gabrielmustiere/radar/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/gabrielmustiere/radar/releases/tag/v0.1.0

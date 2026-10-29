# Product Backlog — Radar

> Carte des capacités fonctionnelles et backlog priorisé dérivé de `docs/vision.md`.

_Document vivant — enrichi/édité au fil du cycle de vie, refondu lors d'un pivot. Date de dernière mise à jour : 2026-10-29._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture chronologique. Détails fins dans `git log`.

| Date | Nature | Éléments | Motif |
|------|--------|----------|-------|
| 2026-10-29 | Création | — | Backlog initial dérivé de la vision |

## Domaines fonctionnels

| # | Domaine | Résumé en une ligne |
|---|---------|---------------------|
| D1 | Contribution | Ajouter un lien en moins de 15 secondes, y compris la reprise sélective de l'historique Chat |
| D2 | Cartes | Rendre chaque lien lisible sans clic, quelle que soit la source |
| D3 | Classement | Classer les liens par besoin, collectivement et sans curateur |
| D4 | Exploration | Retrouver un lien par besoin ou par mot, et découvrir les nouveautés |
| D5 | Diffusion Chat | Annoncer chaque ajout dans Google Chat, où la discussion continue |
| D6 | Mesure | Mesurer en volumes agrégés si l'équipe retrouve ses liens anciens |

## Capacités

### D1 — Contribution

- **C1.1** — Un membre peut ajouter un lien en collant son URL et en choisissant une ou deux catégories, en moins de 15 secondes.
- **C1.2** — Un membre peut joindre à son lien une phrase de contexte facultative (pour dire pourquoi c'est utile).
- **C1.3** — Le système peut signaler qu'un lien existe déjà et renvoyer vers sa carte (pour éviter deux cartes classées différemment).
- **C1.4** — Un contributeur peut reprendre un lien de l'historique Chat en conservant sa date et son posteur d'origine (pour amorcer la base avant l'ouverture).
- **C1.5** — Le posteur peut corriger le contexte de son lien ou retirer ce lien.

### D2 — Cartes

- **C2.1** — Le système peut construire l'aperçu d'un lien (titre, visuel, description) à partir de ce que la source expose.
- **C2.2** — Le système peut adapter la carte au type de source : dépôt GitHub, vidéo YouTube, post ou compte X, image, article.
- **C2.3** — Le système peut afficher une carte de repli soignée quand la source ne fournit pas d'aperçu.
- **C2.4** — Un membre peut lire sur la carte le type de source, le posteur, la date et les catégories sans cliquer (pour décider si le lien vaut le clic).
- **C2.5** — Un membre peut relancer l'aperçu d'un lien dont l'enrichissement a échoué ou vieilli.

### D3 — Classement

- **C3.1** — Un membre peut créer une catégorie-besoin au moment de l'ajout, quand aucune ne convient.
- **C3.2** — Un membre peut reclasser n'importe quel lien ou lui ajouter une catégorie.
- **C3.3** — Un membre peut décrire en une phrase le besoin que couvre une catégorie (pour que chacun classe de la même façon).
- **C3.4** — Un membre peut renommer une catégorie ou en fusionner deux quasi-doublons.

### D4 — Exploration

- **C4.1** — Un membre peut parcourir les liens d'une catégorie-besoin.
- **C4.2** — Un membre peut rechercher par mot dans les titres, descriptions, contextes et catégories.
- **C4.3** — Un membre peut ouvrir le lien d'origine depuis sa carte.
- **C4.4** — Un membre peut consulter le fil des derniers liens ajoutés.
- **C4.5** — Un membre peut filtrer les liens par type de source (vidéos, dépôts, articles…).
- **C4.6** — Un membre peut voir ce qui a été ajouté depuis sa dernière visite.

### D5 — Diffusion Chat

- **C5.1** — Le système peut annoncer chaque nouveau lien dans l'espace Google Chat (titre, catégories, posteur, contexte).
- **C5.2** — Un membre peut ouvrir la carte Radar depuis l'annonce, là où le lien est classé et retrouvable.

### D6 — Mesure

- **C6.1** — Le système peut enregistrer chaque ouverture d'un lien d'origine avec l'âge du lien à ce moment-là.
- **C6.2** — L'équipe peut consulter les indicateurs agrégés du mois : liens de plus de 30 jours rouverts, visiteurs distincts, contributeurs distincts.

## Parcours utilisateurs principaux

### P1 — Partager une trouvaille

- **Acteur** : tout contributeur (développeur, producteur, QA, manager).
- **Déclencheur** : il tombe sur un lien utile.
- **Étapes** : C1.1 (+ C3.1 si aucune catégorie ne convient) → C1.3 si le lien existe déjà → C1.2 → C2.1 / C2.2 / C2.3 → C5.1.
- **État final** : le lien est classé, sa carte est enrichie et l'annonce est postée dans Chat.
- **Fréquence** : 5 à 20 fois par semaine.

### P2 — Retrouver pour décider

- **Acteur** : le développeur face à un choix technique (utilisateur principal).
- **Déclencheur** : « on avait vu un truc là-dessus… ».
- **Étapes** : C4.1 ou C4.2 → C4.5 → C2.4 → C4.3 → C6.1.
- **État final** : un lien ancien est rouvert et sert la décision (North Star).
- **Fréquence** : cible de 20 liens anciens rouverts par mois à 6 mois, 40 à 1 an.

### P3 — Rebondir sur une annonce

- **Acteur** : tout membre.
- **Déclencheur** : une annonce Radar apparaît dans Google Chat.
- **Étapes** : C5.1 → C5.2 → C2.4 → C4.3, ou C3.2 si le lien est mal classé.
- **État final** : le lien est lu ou mieux classé ; la discussion continue dans Chat.
- **Fréquence** : à chaque ajout.

### P4 — Faire le tour des nouveautés

- **Acteur** : tout membre.
- **Déclencheur** : visite spontanée de Radar.
- **Étapes** : C4.6 ou C4.4 → C2.4 → C4.3.
- **État final** : le membre a rattrapé ce que l'équipe a partagé.
- **Fréquence** : environ une fois par semaine et par personne.

### P5 — Entretenir le classement

- **Acteur** : tout membre.
- **Déclencheur** : un lien mal classé ou deux catégories quasi-doublons.
- **Étapes** : C3.2 → C3.3 → C3.4.
- **État final** : les catégories restent cohérentes (hypothèse 3 de la vision).
- **Fréquence** : quelques fois par mois.

### P6 — Amorcer la base

- **Acteur** : les contributeurs habituels.
- **Déclencheur** : avant l'ouverture à toute l'organisation.
- **Étapes** : C3.1 → C1.4 → C1.3 si le lien a déjà été repris → C3.3.
- **État final** : 50 à 100 liens classés ; la première recherche par besoin trouve déjà quelque chose et les catégories sont éprouvées.
- **Fréquence** : une fois.

### P7 — Suivre la North Star

- **Acteur** : le porteur du projet.
- **Déclencheur** : début de mois.
- **Étapes** : C6.2.
- **État final** : les seuils et le signal d'arrêt sont évalués.
- **Fréquence** : une fois par mois.

## Règles métier transverses

### Permissions et rôles

- **R1 — Interne** : rien n'est visible sans être connecté comme membre de l'organisation.
- **R2 — Pas de curateur** : tous les membres ont les mêmes droits pour ajouter, classer, reclasser, créer, renommer ou fusionner une catégorie.
- **R3 — Attribution** : chaque lien porte son posteur, affiché sur la carte.
- **R12 — Retrait réservé au posteur** : seul le posteur corrige ou retire son lien ; un lien problématique se signale au posteur dans Chat.

### Workflows et états

- **R4 — Toujours classé** : un lien naît avec une ou deux catégories et n'en a jamais zéro ; on peut en ajouter après coup.
- **R5 — Une seule annonce** : un lien est annoncé une fois, à son ajout. Reclassements et corrections ne sont pas annoncés, et les liens d'amorçage ne le sont jamais.

### Contraintes de gestion

- **R6 — Unicité** : un même lien n'existe qu'une fois dans Radar.
- **R7 — Ajout minimal** : seuls l'URL et une catégorie sont obligatoires ; aucune feature n'ajoute de champ obligatoire à l'ajout (principe 1).
- **R8 — Tout est partagé** : pas de collection privée et pas de fil de commentaires (anti-objectifs).
- **R9 — Mesure agrégée** : les indicateurs sont des volumes ; aucune vue « qui a contribué combien » (anti-objectif « outil de reporting »).

### Exigences réglementaires

- Aucune exigence identifiée : outil strictement interne.

### Conventions transverses

- **R10 — Date d'origine** : l'âge d'un lien se compte depuis sa date d'origine ; un lien d'amorçage repris de Chat compte comme ancien pour la North Star.
- **R11 — Langue et heure** : interface en français, dates à l'heure de Paris.

## Backlog priorisé

> Les cases sont **dérivées** de `docs/story/*/metadata.json` (champ `backlog` + `delivery`) — ne les coche pas à la main, la prochaine passe les recalculerait. Une case est cochée quand la story est **livrée** (`delivery.commit` renseigné) ; les états intermédiaires se lisent sur la ligne « Story ».

### MVP — Lancement initial · `5/9 livrées`

- [x] `ajout-lien` — Permettre à un membre d'ajouter un lien en moins de 15 secondes (URL, une ou deux catégories existantes ou nouvelles, contexte facultatif) pour que la veille entre dans Radar plutôt que dans Chat.
  - Story `002-f-ajout-lien` · **livrée**
  - C1.1, C1.2, C3.1 · P1, P6 · dép. — · Vision : principe 1 / signal d'arrêt « ajout trop coûteux »
- [x] `carte-apercu` — Permettre à un membre de juger un lien sans cliquer grâce à une carte (aperçu générique, type de source, posteur, date, catégories) et à une carte de repli soignée.
  - Story `003-f-carte-apercu` · **livrée**
  - C2.1, C2.3, C2.4 · P1, P2, P3, P4 · dép. `ajout-lien` · Vision : principe 2 / risque « sources fermées aux aperçus »
- [x] `doublon-lien` — Permettre à un membre d'être averti qu'un lien existe déjà et d'atteindre sa carte pour éviter deux cartes classées différemment.
  - Story `004-f-doublon-lien` · **livrée**
  - C1.3 · P1, P6 · dép. `ajout-lien` · Vision : hypothèse 3 / règle R6
- [x] `amorcage-historique` — Permettre aux contributeurs habituels de reprendre un lien de l'historique Chat avec sa date et son posteur d'origine, sans annonce, pour amorcer la base avant l'ouverture.
  - Story `005-f-amorcage-historique` · **livrée**
  - C1.4 · P6 · dép. `ajout-lien` · Vision : horizon 3-6 mois (amorçage) / anti-objectif « importer tout l'historique »
- [x] `exploration-categories` — Permettre à un membre de parcourir les liens d'une catégorie-besoin et d'ouvrir le lien d'origine pour retrouver ce que l'équipe a repéré sur un sujet.
  - Story `006-f-exploration-categories` · **livrée**
  - C4.1, C4.3 · P2 · dép. `carte-apercu` · Vision : problème central / principe 3
- [ ] `recherche-mot-cle` — Permettre à un membre de rechercher par mot dans les titres, descriptions, contextes et catégories pour retrouver un lien dont il se souvient vaguement.
  - Story `007-f-recherche-mot-cle` · **clôture en cours**
  - C4.2 · P2 · dép. `carte-apercu` · Vision : problème central / audience principale
- [ ] `annonce-google-chat` — Permettre à l'équipe de voir chaque nouveau lien annoncé dans Google Chat, avec un accès direct à sa carte Radar, pour que Chat reste le haut-parleur.
  - Pas encore cadrée
  - C5.1, C5.2 · P1, P3 · dép. `ajout-lien` · Vision : principe 4 / risque « webhooks bloqués »
- [ ] `mesure-clics-sortants` — Permettre à l'équipe de compter chaque ouverture de lien avec l'âge du lien pour mesurer la North Star dès le lancement.
  - Pas encore cadrée
  - C6.1 · P2 · dép. `exploration-categories` · Vision : North Star
- [ ] `fil-derniers-ajouts` — Permettre à un membre de consulter le fil des derniers liens ajoutés pour nourrir la curiosité au quotidien.
  - Pas encore cadrée
  - C4.4 · P4 · dép. `carte-apercu` · Vision : « la curiosité est le moteur de l'usage »

### V2 — Court terme post-lancement · `0/9 livrées`

- [ ] `indicateurs-mensuels` — Permettre au porteur du projet de consulter les indicateurs agrégés du mois pour évaluer les seuils et le signal d'arrêt à 3 mois.
  - Pas encore cadrée
  - C6.2 · P7 · dép. `mesure-clics-sortants` · Vision : seuils / signal d'arrêt / règle R9
- [ ] `reclassement-collectif` — Permettre à un membre de reclasser n'importe quel lien ou de lui ajouter une catégorie pour que le classement reste collectif.
  - Pas encore cadrée
  - C3.2 · P3, P5 · dép. `ajout-lien` · Vision : principe 5
- [ ] `fusion-categories` — Permettre à un membre de renommer une catégorie ou d'en fusionner deux quasi-doublons pour garder un classement cohérent.
  - Pas encore cadrée
  - C3.4 · P5 · dép. `reclassement-collectif` · Vision : hypothèse 3 / principe 3
- [ ] `description-categorie` — Permettre à un membre de décrire le besoin couvert par une catégorie pour que chacun classe de la même façon.
  - Pas encore cadrée
  - C3.3 · P5, P6 · dép. `ajout-lien` · Vision : principe 3 / hypothèse 3
- [ ] `cartes-par-source` — Permettre à un membre de lire une carte adaptée à la source (dépôt GitHub, vidéo YouTube, post ou compte X, image, article) pour juger un lien d'un coup d'œil.
  - Pas encore cadrée
  - C2.2 · P1, P2, P3, P4 · dép. `carte-apercu` · Vision : principe 2
- [ ] `relance-apercu` — Permettre à un membre de relancer l'aperçu d'un lien dont l'enrichissement a échoué ou vieilli pour rattraper une carte dégradée.
  - Pas encore cadrée
  - C2.5 · P1 · dép. `carte-apercu` · Vision : principe 2 / risque « sources fermées aux aperçus »
- [ ] `correction-retrait-lien` — Permettre au posteur de corriger le contexte de son lien ou de le retirer pour garder une base fiable.
  - Pas encore cadrée
  - C1.5 · P1 · dép. `ajout-lien` · Vision : règle R12
- [ ] `filtre-type-source` — Permettre à un membre de filtrer les liens par type de source pour cibler vidéos, dépôts ou articles.
  - Pas encore cadrée
  - C4.5 · P2 · dép. `cartes-par-source` · Vision : problème central
- [ ] `nouveautes-depuis-derniere-visite` — Permettre à un membre de voir ce qui a été ajouté depuis sa dernière visite pour rattraper la veille sans tout relire.
  - Pas encore cadrée
  - C4.6 · P4 · dép. `fil-derniers-ajouts` · Vision : « la curiosité est le moteur de l'usage »

### V3 — Long terme

_Vide volontairement : la vision ne fixe pas d'horizon à 3 ans et refuse le radar techno avant le seuil à 1 an._

## Couverture

_(dérivé — recalculé à chaque passe, ne pas maintenir à la main)_

### Capacités par horizon

- **MVP** — livrées : C1.1, C1.2, C1.3, C1.4, C2.1, C2.3, C2.4, C3.1, C4.1, C4.3 · planifiées : C4.2, C4.4, C5.1, C5.2, C6.1
- **V2** — livrées : — · planifiées : C1.5, C2.2, C2.5, C3.2, C3.3, C3.4, C4.5, C4.6, C6.2
- **V3** — livrées : — · planifiées : —

### Capacités non couvertes (à challenger)

- Aucune.

### Parcours supportés

- **P1** : entièrement supporté en MVP (C2.2 en V2, carte de repli et aperçu générique au lancement).
- **P2** : entièrement supporté en MVP (filtre par type de source C4.5 reporté en V2).
- **P3** : partiellement supporté en MVP (reclassement C3.2 reporté en V2).
- **P4** : entièrement supporté en MVP via le fil C4.4 (C4.6 en V2).
- **P5** : non supporté en MVP (C3.2, C3.3, C3.4 en V2).
- **P6** : entièrement supporté en MVP (description de catégorie C3.3 reportée en V2).
- **P7** : non supporté en MVP (C6.2 en V2) ; les données sont collectées dès le MVP par C6.1.

## Notes pour `/feature-pitch`

- `annonce-google-chat` : vérifier auprès de l'administrateur Workspace que les webhooks entrants sont autorisés sur l'espace, avant tout cadrage (risque externe de la vision).
- `indicateurs-mensuels` doit être livré avant la revue à 3 mois : c'est elle qui évalue le signal d'arrêt « moins de 5 contributeurs distincts par mois ».
- Deux indicateurs restent hors outil, comptés à la main : les contributeurs non-développeurs (seuil à 1 an) et la part de liens de veille encore postés directement dans Chat (signal d'arrêt).
- `ajout-lien` : la création de catégorie se fait dans le formulaire d'ajout ; les doublons de catégories se corrigent ensuite par fusion (`fusion-categories`).
- `amorcage-historique` : plusieurs contributeurs risquent de reprendre les mêmes « meilleurs liens » — `doublon-lien` doit être livré avant l'amorçage.
- `carte-apercu` : X/Twitter restreint l'accès anonyme aux métadonnées ; la carte de repli est le cas nominal pour cette source au lancement.
- Écarté : filtre « liens d'un posteur » (porte d'entrée du reporting, règle R9).
- À reconsidérer si le classement collectif dérape : historique des reclassements d'un lien.
- Piste conditionnée à la revue à 1 an : radar techno (statut adopté / écarté, et pourquoi).

# Vision — Radar

> Pitch en une phrase : un outil de veille partagé pour la cinquantaine de développeurs, producteurs, QA et managers de l'organisation, qui empêche les liens postés dans Google Chat de disparaître en quelques jours, en les transformant en cartes enrichies, classées par besoin et retrouvables des mois plus tard — chaque ajout restant annoncé dans Google Chat.

_Document vivant — enrichi au fil du cycle de vie, refondu lors d'un pivot stratégique. Date de dernière mise à jour : 2026-10-30._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture du haut vers le bas = ordre chronologique. Détails fins dans `git log`.

| Date | Nature | Axe | Motif |
|------|--------|-----|-------|
| 2026-10-29 | Création | — | Vision initiale |
| 2026-10-29 | Éditer | — | Renommage du projet : Veille → Radar |
| 2026-10-30 | Éditer | Principes produit | Principe 4 : un lien peut aussi entrer par une source suivie, classé d'office (story `012-f-sources-automatiques`) |

## Le problème

Un développeur doit choisir une librairie, un outil ou une approche pour un besoin précis. Il se souvient vaguement que « quelqu'un avait posté un truc là-dessus », mais il n'a aucun moyen de demander « qu'a-t-on déjà repéré sur ce sujet ? ». La veille de l'équipe existe, mais elle est inaccessible au moment où elle aurait de la valeur.

**Comment c'est résolu aujourd'hui** : les liens (YouTube, X/Twitter, GitHub, articles de blog…) sont postés dans un espace Google Chat. Aucune autre solution n'a jamais été tentée.

**Pourquoi c'est insuffisant** : la recherche de Google Chat retrouve un mot, pas un thème. Un lien posté il y a trois mois est enfoui dans le flux, sans classement ni contexte exploitable. Le seul effet réel d'un post est de susciter de la curiosité sur le moment.

**Ampleur** : 5 à 20 liens par semaine, soit 250 à 1 000 liens par an qui deviennent pratiquement introuvables.

## L'audience

### Utilisateur principal

- **Persona** : le développeur qui doit faire un choix technique (librairie, outil, approche) et veut partir de ce que ses collègues ont déjà repéré plutôt que de zéro.
- **Volume cible** : la majorité des ~50 personnes de l'organisation sont des développeurs ; 5 à 15 d'entre elles postent régulièrement des liens aujourd'hui.
- **Ce qui le bloque aujourd'hui** : il sait que l'information a circulé, mais ne peut pas la retrouver par sujet.

### Utilisateurs secondaires

- **Producteurs** — réflexion produit : références concurrentes, UX, tendances.
- **QA** — outils et pratiques de test.
- **Tous** — lancement de projet (état de l'art d'un domaine), préparation d'un talk ou d'une revue de veille interne, onboarding d'un nouvel arrivant.

### Hors cible explicite

- **L'externe** (clients, partenaires, public) : l'outil est strictement interne.
- **La veille personnelle** : tout ce qui entre est partagé, il n'y a pas de collection privée.

## La proposition de valeur

### Bénéfice utilisateur

Au moment d'un choix technique ou produit, retrouver en quelques minutes ce que l'équipe a repéré sur le sujet, même des mois plus tôt, au lieu de repartir d'une recherche Google ou d'une question à une IA.

### Pourquoi nous, plutôt qu'eux

Google Chat ne sait pas regrouper par thème et ne fait que de la curiosité éphémère. Les moteurs de recherche et les IA ne savent pas ce que l'équipe a jugé utile. L'outil combine les deux attentes, dans un ordre précis : **retrouver par besoin est la raison d'exister ; la curiosité est le moteur de l'usage**. Sans un parcours agréable au quotidien, personne ne contribue ni ne revient, et la base est vide le jour où on en a besoin.

### Unfair advantage

Le contenu lui-même : une sélection faite par des collègues qui travaillent sur la même stack et les mêmes projets, classée par les besoins réels de l'organisation. Aucun outil externe ne peut la reproduire.

## Métriques de succès

### North Star

**Nombre de liens de plus de 30 jours ouverts depuis l'outil, par mois.** C'est la mesure directe de « retrouver des mois après ». Mesure : comptage des clics sortants vers le lien d'origine, croisé avec la date d'ajout du lien.

### Métriques secondaires

- **Acquisition** : visiteurs distincts par mois.
- **Activation** : premier lien ajouté ou premier lien rouvert par une personne.
- **Rétention** : contributeurs distincts par mois.
- **Monétisation** : sans objet (outil interne).

### Seuils

- À 6 mois : 20 liens anciens rouverts par mois (environ un par jour ouvré).
- À 1 an : 40 liens anciens rouverts par mois, et au moins 3 contributeurs non-développeurs (producteurs, QA, management) par mois.
- À 3 ans : non fixé — outil interne, réévaluation à 1 an.

### Signal d'arrêt

Après 3 mois d'usage, l'un de ces constats déclenche un arrêt ou un pivot :

- **Moins de 5 contributeurs distincts par mois** : la contribution s'effondre au lieu de migrer vers l'outil.
- **Plus de la moitié des liens de veille encore postés directement dans Google Chat** sans passer par l'outil : l'ajout est trop coûteux.

## Principes produit

1. **Ajouter un lien ne coûte pas plus qu'un post Chat** — coller l'URL, choisir une catégorie, moins de 15 secondes. Toute feature qui ajoute un champ obligatoire à l'ajout est refusée. Conséquence : la phrase de contexte du posteur (« pourquoi c'est utile ») est encouragée mais facultative.
2. **Une carte se lit sans clic** — aperçu visuel, type de source, posteur, date : on décide si le lien vaut le clic sans quitter l'outil. C'est la traduction vérifiable de « esthétique et intéressant ». Une source qui ne fournit pas d'aperçu a quand même une carte de repli soignée.
3. **Les catégories sont des besoins, pas des rubriques** — on classe par problème à résoudre (« tester une app mobile ») plutôt que par taxonomie de bibliothèque (« Mobile > QA »).
4. **L'outil est la porte d'entrée, Google Chat est le haut-parleur** — un lien entre uniquement par l'outil, où il est classé ; l'outil l'annonce ensuite dans Google Chat, où la discussion continue. Un lien entre soit par un membre qui le classe, soit par une source suivie (flux choisi par un admin) qui le classe d'office dans ses catégories : choisir une source est une forme de sélection collective, et la carte signale qu'il est arrivé automatiquement.
5. **Le classement est collectif** — le posteur choisit une ou deux catégories en un clic ; n'importe qui peut ensuite reclasser ou compléter. Pas de curateur unique.

## Anti-objectifs

Ce qu'on **refuse explicitement** de faire, et pourquoi :

- **Ouvrir l'outil à l'externe** — interne uniquement : pas de modération, d'image publique ni de droits à gérer.
- **Des signets privés façon read-later** — l'outil se fragmenterait en 50 collections personnelles.
- **Un lieu de discussion** — le débat autour d'un lien reste dans Google Chat ; pas de fil de commentaires.
- **Un outil de reporting** — le management ne s'en sert pas pour mesurer qui fait de la veille.
- **Importer tout l'historique Chat** — des liens bruts, non classés, recréeraient le problème dans l'outil.

## Hypothèses critiques

| # | Hypothèse | Comment l'invalider | Statut |
|---|-----------|---------------------|--------|
| 1 | Le besoin « retrouver par besoin » est partagé par l'équipe, pas seulement par le porteur du projet. | Interroger 5 personnes (2 devs, 1 producteur, 1 QA, 1 manager) sur leur dernier « on avait vu un truc là-dessus… ». | À tester |
| 2 | Les contributeurs actuels migrent de Chat vers l'outil si l'ajout prend 15 secondes ou moins. | Mesurer la part de liens de veille postés directement dans Chat un mois après le lancement. | À tester |
| 3 | Des catégories pensées comme des besoins restent cohérentes malgré le reclassement collectif. | Compter les catégories doublons ou quasi-doublons après l'amorçage, puis à 3 mois. | À tester |

## Risques externes

- **Webhooks Google Chat bloqués par l'admin Workspace** : l'annonce de chaque ajout suppose des webhooks entrants autorisés sur l'espace. Mitigation : vérifier auprès de l'administrateur avant tout développement.
- **Sources fermées aux aperçus** : X/Twitter (et d'autres plateformes) restreignent l'accès anonyme aux métadonnées, ce qui dégrade le principe « carte lisible sans clic ». Mitigation : carte de repli soignée pour toute source sans aperçu.

## Horizons

### 3-6 mois

- Amorçage avant ouverture : les contributeurs habituels reprennent à la main les 50 à 100 meilleurs liens de l'historique Chat, classés — la première recherche par besoin trouve déjà quelque chose, et les catégories sont éprouvées sur du vrai contenu.
- Ouverture à toute l'organisation, chaque ajout annoncé dans Google Chat.
- Seuil de 20 liens anciens rouverts par mois atteint à 6 mois.

### 1 an

- 40 liens anciens rouverts par mois.
- La contribution dépasse les développeurs : au moins 3 contributeurs non-développeurs par mois.

### 3 ans

Pas d'horizon fixé. Extension possible si la vision tient à 1 an : une mémoire collective de ce que l'équipe a adopté ou écarté, et pourquoi (type radar techno).

**Refusé à court terme** :

- Classement ou résumé automatique par IA, tant que le classement humain n'a pas été validé.
- Radar techno (statut adopté / écarté) avant le seuil à 1 an.

## Notes pour les features à venir

Pointeurs bruts pour `/feature-pitch` — ne pas concevoir ici :

- Ajout d'un lien en moins de 15 secondes (coller l'URL + une catégorie, contexte facultatif).
- Cartes enrichies par type de source : dépôt GitHub, vidéo YouTube, compte ou post X/Twitter, image, article de blog — avec carte de repli.
- Notification Google Chat à chaque ajout.
- Exploration par catégorie-besoin et recherche.
- Reclassement collectif des liens.
- Parcours de découverte des nouveautés (levier de curiosité).
- Mesure des clics sortants (North Star).
- Outillage de l'amorçage sélectif depuis l'historique Chat.

# Être averti qu'un lien existe déjà dans Radar

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/004-f-doublon-lien/`
> **Amont** : aucun

Quand un membre ajoute un lien déjà présent, Radar refuse le doublon, le lui dit et lui montre la carte existante, accessible par sa propre page. Un même lien n'existe qu'une fois, classé à un seul endroit.

## Contexte

Depuis `002-f-ajout-lien`, rien n'empêche deux membres d'ajouter la même vidéo ou le même article. Chaque copie porte ses propres catégories : le lien se retrouve classé à deux endroits différents, et la recherche par besoin rend des résultats contradictoires.

Le risque culmine pendant l'amorçage : plusieurs contributeurs reprendront les mêmes « meilleurs liens » de l'historique Chat (note du backlog : cette story doit précéder `amorcage-historique`).

## Alignement vision

- **Problème adressé** : garder un classement par besoin cohérent, condition pour retrouver un lien.
- **Audience servie** : tous les contributeurs, et en priorité ceux de l'amorçage.
- **Principes respectés** : principe 3 (une catégorie-besoin par lien, pas des copies divergentes) ; principe 1 (aucun champ ajouté à l'ajout).
- **Hypothèse testée** : hypothèse 3 — les catégories restent cohérentes ; un doublon est la première source d'incohérence.
- **Impact North Star** : indirect — un lien unique concentre les ouvertures au lieu de les disperser.

## Utilisateurs concernés

- **Membre qui ajoute un lien** — est averti si le lien existe déjà et atteint sa carte en un clic.
- **Tout membre** — peut ouvrir la page d'une carte (adresse partageable).

## User Stories

- En tant que **membre**, je veux être averti quand le lien que j'ajoute existe déjà afin de ne pas créer une seconde carte classée autrement.
- En tant que **membre**, je veux voir la carte existante et l'ouvrir en un clic afin de vérifier comment le lien est classé.
- En tant que **membre**, je veux que deux adresses qui pointent vers la même page (avec ou sans `www`, paramètres de suivi, lien court YouTube…) soient reconnues comme un même lien.
- En tant que **membre**, je veux une page propre à chaque carte afin de pouvoir la désigner à un collègue.

## Règles métier

1. Un même lien n'existe qu'une fois dans Radar (R6).
2. Deux adresses désignent le même lien quand elles ne diffèrent que par : `http`/`https`, majuscules du domaine, préfixe `www.`, barre oblique finale, ancre (`#…`), paramètres de suivi (`utm_*`, `fbclid`, `gclid`, `mc_cid`, `mc_eid`, `si`), ou la forme courte `youtu.be/<id>` d'une vidéo YouTube.
3. Tout autre paramètre d'adresse (`?page=2`, `?v=…`) distingue deux liens.
4. Un ajout en doublon est refusé ; rien n'est enregistré, aucune catégorie n'est créée.
5. Le message de refus nomme le problème et montre la carte existante avec un accès à sa page.
6. La page d'une carte est réservée aux membres connectés (R1).

## Critères d'acceptation

- [x] Ajouter une adresse déjà présente affiche « Ce lien est déjà dans Radar » et la carte existante, sans créer de second lien.
- [x] Les variantes `http`/`https`, `www`, barre finale, ancre, `utm_source` et `youtu.be` d'une adresse existante sont reconnues comme doublons.
- [x] Deux adresses qui ne diffèrent que par un paramètre significatif (`?page=2`) sont acceptées comme deux liens.
- [x] Depuis l'avertissement, un clic mène à la page de la carte existante.
- [x] La page d'une carte affiche la carte ; une carte inexistante répond « page introuvable ».
- [x] Une catégorie saisie lors d'un ajout refusé pour doublon n'est pas créée.

## Hors scope

- **Ajouter la catégorie proposée au lien existant** : relève du reclassement (V2 `reclassement-collectif`).
- **Fusionner des doublons déjà présents** : aucun doublon ne peut exister après cette story (aucune donnée en production).
- **Reconnaître deux pages différentes au même contenu** (miroirs, republications) : hors de portée d'une comparaison d'adresses.

## Impacts transverses

- **Traduction / langues** : messages en français.
- **Droits d'accès** : la page de carte suit la règle commune (membres connectés).
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : avertissement et page de carte en clair et sombre.
- **Exposition à des tiers** : non.
- **Emails / notifications** : non.
- **Données existantes** : aucune donnée en production ; les bases locales se réinitialisent.
- **Comportement par défaut** : un ajout sans doublon est inchangé.

## Questions ouvertes

- **Proposer d'ajouter sa catégorie au lien existant ?** : (a) non, (b) oui. → tranché : (a), le reclassement est une story V2.
- **Paramètres de suivi reconnus** : liste fermée de la règle 2. Recommandé : l'enrichir au fil des doublons constatés.

---

## Annexe — Pistes pour le plan

- Une adresse normalisée stockée à côté de l'adresse d'origine, avec un index unique.
- La page de carte réutilise `templates/link/_card.html.twig`.

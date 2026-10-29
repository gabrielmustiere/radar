# Compter chaque ouverture de lien avec son âge

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/009-f-mesure-clics-sortants/`
> **Amont** : aucun

Chaque fois qu'un membre ouvre un lien d'origine depuis Radar, Radar enregistre l'ouverture et l'âge du lien à ce moment-là. C'est la matière première de la North Star : le nombre de liens de plus de 30 jours rouverts chaque mois.

## Contexte

La vision mesure le succès de Radar par une seule chose : retrouver des mois plus tard. Sa North Star — « liens de plus de 30 jours ouverts depuis l'outil, par mois » — exige de compter les ouvertures dès le lancement : une ouverture non enregistrée est perdue pour toujours, et les seuils à 6 mois (20 par mois) seraient invérifiables.

Depuis `006-f-exploration-categories`, la carte ouvre le lien d'origine directement : rien n'est compté.

## Alignement vision

- **Problème adressé** : savoir si Radar résout son problème central.
- **Audience servie** : le porteur du projet (mesure) ; aucun changement visible pour les membres.
- **Principes respectés** : anti-objectif « outil de reporting » respecté — la donnée sert des volumes agrégés (R9), aucune vue par personne n'est livrée.
- **Hypothèse testée** : toutes, via la North Star.
- **Impact North Star** : direct — c'est sa mesure.

## Utilisateurs concernés

- **Membre connecté** — ouvre un lien exactement comme avant (nouvel onglet), sans rien percevoir de la mesure.
- **Porteur du projet** — dispose des données pour la future vue des indicateurs (V2).

## User Stories

- En tant que **porteur du projet**, je veux que chaque ouverture d'un lien d'origine soit enregistrée avec l'âge du lien afin de mesurer la North Star dès le lancement.
- En tant que **membre**, je veux continuer d'ouvrir un lien en un clic, dans un nouvel onglet, et voir où il mène avant de cliquer, afin que la mesure ne me gêne pas.
- En tant que **membre**, je ne veux PAS qu'une vue « qui a ouvert quoi » existe afin que Radar ne devienne pas un outil de surveillance.

## Règles métier

1. Toute ouverture d'un lien d'origine depuis une carte (titre, visuel ou bouton « Ouvrir ») est enregistrée.
2. Une ouverture enregistre : le lien, le moment, le membre (pour compter les visiteurs distincts), et l'âge du lien en jours révolus depuis sa date d'origine (R10).
3. Un lien repris de l'historique compte depuis sa date d'origine : il est ancien dès sa reprise (R10).
4. Chaque clic compte, y compris plusieurs ouvertures du même lien par le même membre.
5. Aucune vue ne présente les ouvertures par personne (R9) ; les indicateurs agrégés sont une story V2.
6. La destination reste visible avant le clic (infobulle du bouton « Ouvrir »).

## Critères d'acceptation

- [x] Ouvrir un lien depuis une carte mène à la source dans un nouvel onglet.
- [x] Chaque ouverture est enregistrée avec le lien, le moment, le membre et l'âge du lien en jours.
- [x] Un lien repris daté de plus de 30 jours est enregistré avec son âge réel (plus de 30 jours).
- [x] Le bouton « Ouvrir » indique la destination au survol.
- [x] Un visiteur non connecté ne peut pas passer par Radar pour ouvrir un lien.
- [x] Aucun écran ne montre les ouvertures d'un membre.

## Hors scope

- **Indicateurs mensuels** : V2 `indicateurs-mensuels`.
- **Ouvertures depuis Google Chat** : l'annonce pointe vers la source et la carte ; seules les ouvertures depuis Radar comptent (définition de la North Star).
- **Dédoublonnage des clics** : règle 4.

## Impacts transverses

- **Traduction / langues** : infobulle en français.
- **Droits d'accès** : ouverture via Radar réservée aux membres connectés.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : inchangée.
- **Exposition à des tiers** : la source ne reçoit pas l'adresse de Radar comme provenance.
- **Emails / notifications** : non.
- **Données existantes** : les ouvertures passées ne sont pas reconstituables.
- **Comportement par défaut** : la mesure est toujours active.

## Questions ouvertes

- **Enregistrer le membre ?** : (a) oui, pour les visiteurs distincts (C6.2), sans vue individuelle, (b) non. → tranché : (a), condition de la métrique d'acquisition ; R9 interdit la vue, pas la donnée.

# Reprendre un lien de l'historique Chat pour amorcer Radar

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/005-f-amorcage-historique/`
> **Amont** : aucun

Avant l'ouverture à toute l'organisation, les contributeurs habituels reprennent à la main les 50 à 100 meilleurs liens de l'historique Google Chat, en conservant leur date et leur posteur d'origine. Ces liens ne sont pas annoncés : ils sont déjà passés dans Chat.

## Contexte

Le jour de l'ouverture, une base vide décevrait : la première recherche par besoin ne trouverait rien, et personne ne reviendrait. La vision prévoit un amorçage sélectif — les meilleurs liens, classés — et refuse l'import en masse de l'historique (anti-objectif).

Le formulaire d'ajout actuel date chaque lien du jour et l'attribue à celui qui le saisit : un lien repris de Chat y perdrait son âge réel (il compterait comme récent pour la North Star) et son auteur.

## Alignement vision

- **Problème adressé** : rendre Radar utile dès le premier jour.
- **Audience servie** : les 5 à 15 contributeurs habituels, pendant la phase d'amorçage.
- **Principes respectés** : principe 3 (chaque lien repris est classé par besoin) ; anti-objectif « importer tout l'historique » respecté (reprise une à une, choisie).
- **Hypothèse testée** : hypothèse 3 — les catégories sont éprouvées sur du vrai contenu avant l'ouverture.
- **Impact North Star** : direct — un lien repris garde sa date d'origine et compte comme ancien quand il est rouvert (R10).

## Utilisateurs concernés

- **Membre contributeur** — reprend un lien de Chat avec sa date et son posteur d'origine.
- **Posteur d'origine** — apparaît comme posteur du lien repris (R3), même s'il ne l'a pas saisi.
- **Tout membre** — voit les liens repris comme les autres, avec une mention discrète « repris de Chat ».

## User Stories

- En tant que **contributeur**, je veux reprendre un lien de l'historique Chat en indiquant sa date et son posteur d'origine afin qu'il garde son âge et son auteur.
- En tant que **contributeur**, je veux être averti si le lien a déjà été repris par un collègue afin de ne pas le doublonner.
- En tant que **membre**, je veux reconnaître un lien repris de Chat afin de comprendre pourquoi sa date est ancienne.
- En tant que **membre de l'espace Chat**, je ne veux PAS voir réannoncés des liens déjà postés afin de ne pas être spammé.

## Règles métier

1. La reprise suit les règles de l'ajout : URL, une ou deux catégories (création à la volée possible), contexte facultatif, doublon refusé (R6).
2. La date d'origine est obligatoire et ne peut pas être dans le futur.
3. Le posteur d'origine est obligatoire et choisi parmi les membres ; le membre connecté est proposé par défaut.
4. Un lien repris n'est jamais annoncé dans Google Chat (R5).
5. Son âge se compte depuis sa date d'origine (R10).
6. Tout membre peut reprendre un lien (R2).

## Critères d'acceptation

- [x] Depuis l'écran d'ajout, un membre atteint l'écran « Reprendre un lien de Chat ».
- [x] Un lien repris avec une date passée et un autre posteur s'affiche avec ce posteur et cette date, et la mention « repris de Chat ».
- [x] Une date d'origine future est refusée avec un message clair.
- [x] Une reprise sans date est refusée avec un message clair.
- [x] Reprendre un lien déjà présent est refusé et montre la carte existante.
- [x] Un lien repris est marqué comme jamais annonçable.

## Hors scope

- **Import automatique de l'historique Chat** : anti-objectif de la vision.
- **Création de comptes pour les posteurs d'origine** : les comptes existent ou sont créés hors Radar.
- **Annonce dans Chat** : story `annonce-google-chat`, qui exclura les liens repris.

## Impacts transverses

- **Traduction / langues** : français.
- **Droits d'accès** : inchangé — tout membre.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : mention « repris de Chat » sur la carte, en clair et sombre.
- **Exposition à des tiers** : non.
- **Emails / notifications** : aucune — c'est la règle 4.
- **Données existantes** : les liens déjà ajoutés ne sont pas des reprises.
- **Comportement par défaut** : l'ajout normal est inchangé.

## Questions ouvertes

- **Posteur d'origine sans compte Radar** : (a) choisir parmi les comptes, (b) saisir un nom libre. → tranché : (a), R3 et R12 s'appuient sur un membre identifié. Recommandé : créer les comptes des posteurs habituels avant l'amorçage.
- **Heure d'origine** : (a) date seule, (b) date et heure. → tranché : (a), l'heure d'un post Chat ancien importe peu.

---

## Annexe — Pistes pour le plan

- Le formulaire de reprise peut étendre celui de l'ajout (date et posteur en plus).
- Un indicateur « repris » sur le lien servira la story d'annonce.

# Annoncer chaque nouveau lien dans Google Chat

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/008-f-annonce-google-chat/`
> **Amont** : aucun

Chaque lien ajouté dans Radar est annoncé une fois dans l'espace Google Chat de l'équipe : titre, catégories, posteur, contexte, et un lien vers sa carte Radar. Radar devient la porte d'entrée de la veille, Google Chat reste le haut-parleur où la discussion continue.

## Contexte

Aujourd'hui, partager un lien, c'est le poster dans Google Chat : tout le monde le voit passer, et la conversation démarre. Si ajouter un lien dans Radar le rendait invisible pour l'équipe, les contributeurs continueraient de poster dans Chat — et Radar resterait vide (signal d'arrêt de la vision : « plus de la moitié des liens encore postés directement dans Chat »).

## Alignement vision

- **Problème adressé** : faire migrer la contribution vers Radar sans perdre la visibilité qu'offre Chat.
- **Audience servie** : tous les membres de l'espace Chat.
- **Principes respectés** : principe 4 (Radar est la porte d'entrée, Chat le haut-parleur) ; anti-objectif « lieu de discussion » respecté (la discussion reste dans Chat).
- **Hypothèse testée** : hypothèse 2 — les contributeurs migrent si rien n'est perdu côté visibilité.
- **Impact North Star** : indirect — l'annonce renvoie vers la carte, où le lien est classé et retrouvable.

## Utilisateurs concernés

- **Membres de l'espace Google Chat** — voient chaque nouvel ajout et peuvent ouvrir sa carte Radar.
- **Posteur** — n'a rien à faire : l'annonce part seule.
- **Responsable de l'outil** — renseigne l'adresse de l'espace Chat lors de l'installation.

## User Stories

- En tant que **membre de l'espace Chat**, je veux voir chaque nouveau lien annoncé avec son titre, ses catégories, son posteur et son contexte afin de découvrir la veille de l'équipe là où je suis déjà.
- En tant que **membre**, je veux ouvrir la carte Radar depuis l'annonce afin de voir le lien classé et de le retrouver plus tard.
- En tant que **membre de l'espace Chat**, je ne veux PAS voir un même lien annoncé deux fois, ni les liens repris de l'historique, afin de ne pas être spammé.

## Règles métier

1. Un lien est annoncé une seule fois, après son ajout (R5).
2. Les liens repris de l'historique Chat ne sont jamais annoncés (R5).
3. Les reclassements et corrections ne sont pas annoncés (R5).
4. L'annonce contient : le titre (ou l'adresse si la source n'en fournit pas), les catégories, le posteur, le contexte s'il existe, le lien d'origine et le lien vers la carte Radar.
5. L'annonce part une fois l'aperçu construit (ou abandonné), pour porter le vrai titre.
6. Tant que l'adresse de l'espace Chat n'est pas configurée, rien n'est envoyé et Radar fonctionne normalement.
7. Un échec d'envoi n'empêche jamais l'ajout du lien.

## Critères d'acceptation

- [x] Un lien ajouté produit exactement une annonce, avec titre, catégories, posteur, contexte, lien d'origine et lien vers sa carte.
- [x] Un lien repris de Chat ne produit aucune annonce.
- [x] Un lien sans aperçu est annoncé avec son adresse comme titre.
- [x] Un traitement rejoué de l'aperçu ne produit pas de seconde annonce.
- [x] Sans adresse d'espace configurée, l'ajout fonctionne et rien n'est envoyé.
- [x] Le lien vers la carte dans l'annonce ouvre la carte Radar.

## Hors scope

- **Discussion ou réactions depuis Radar** : anti-objectif.
- **Plusieurs espaces Chat, choix de l'espace par catégorie** : un seul espace d'équipe.
- **Annonce des reclassements** : R5.

## Impacts transverses

- **Traduction / langues** : annonce en français.
- **Droits d'accès** : l'annonce est visible de tous les membres de l'espace ; la carte Radar reste réservée aux comptes connectés (R1).
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : non.
- **Exposition à des tiers** : titre, catégories, posteur et contexte sont envoyés à Google Chat (espace interne de l'organisation).
- **Emails / notifications** : oui — un message Google Chat par ajout.
- **Données existantes** : les liens déjà présents ne sont pas annoncés rétroactivement.
- **Comportement par défaut** : désactivé tant que l'espace n'est pas configuré.

## Questions ouvertes

- **Webhooks entrants autorisés par l'administrateur Workspace ?** (risque externe de la vision) : non vérifiable depuis le code. Recommandé : vérifier avant la mise en production ; la feature est inactive tant que l'adresse n'est pas renseignée.
- **Annoncer avant ou après l'aperçu ?** : (a) après, (b) immédiatement. → tranché : (a), l'annonce porte le vrai titre ; le délai est de quelques secondes.

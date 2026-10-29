# Ajouter un lien en moins de 15 secondes

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/002-f-ajout-lien/`
> **Amont** : aucun

Un membre colle une URL, choisit une ou deux catégories-besoins (ou en crée une à la volée), ajoute s'il le veut une phrase de contexte, et le lien entre dans Radar. C'est la porte d'entrée de toute la veille : sans elle, les liens restent dans Google Chat.

## Contexte

Aujourd'hui Radar n'a qu'un écran vide « Tous les liens » : aucun lien ne peut y entrer. Les 5 à 20 liens hebdomadaires de l'équipe continuent d'être postés dans Google Chat, où ils disparaissent du flux en quelques jours.

La friction à battre est celle d'un post Chat : coller et envoyer. Si ajouter un lien dans Radar coûte plus cher, les contributeurs habituels ne migreront pas (hypothèse 2 de la vision) et la base restera vide le jour où quelqu'un en aura besoin.

## Alignement vision

- **Problème adressé** : faire entrer la veille dans un lieu où elle sera classée par besoin, au lieu d'un flux Chat éphémère.
- **Audience servie** : tous les contributeurs (développeurs, producteurs, QA, managers), en particulier les 5 à 15 qui postent régulièrement.
- **Principes respectés** : principe 1 (URL + une catégorie suffisent, contexte facultatif), principe 3 (on choisit une catégorie-besoin, pas une rubrique), principe 5 (le posteur classe en un clic ; pas de curateur).
- **Hypothèse testée** : hypothèse 2 — les contributeurs migrent si l'ajout prend 15 secondes ou moins.
- **Impact North Star** : indirect — aucun lien ne peut être rouvert dans 30 jours s'il n'est pas entré aujourd'hui.

## Utilisateurs concernés

- **Membre connecté** (tout compte de l'organisation, mêmes droits pour tous) — peut ajouter un lien, créer une catégorie au passage, et voit le lien apparaître dans « Tous les liens ».
- **Visiteur non connecté** — aucun changement : rien n'est visible sans connexion (R1).

## User Stories

- En tant que **membre**, je veux coller une URL et cocher une ou deux catégories afin de partager une trouvaille aussi vite qu'un post Chat.
- En tant que **membre**, je veux créer une catégorie-besoin depuis le formulaire d'ajout quand aucune ne convient, afin de ne pas abandonner mon ajout.
- En tant que **membre**, je veux ajouter une phrase de contexte facultative afin d'expliquer pourquoi ce lien est utile.
- En tant que **membre**, je veux voir mon lien en tête de « Tous les liens » juste après l'ajout, avec mon nom, la date et ses catégories, afin d'être sûr qu'il est bien entré.
- En tant que **membre**, je ne veux PAS pouvoir valider un lien sans catégorie ou avec plus de deux catégories, afin que tout lien soit classé sans être éparpillé.

## Règles métier

1. Seuls l'URL et au moins une catégorie sont obligatoires (R7). Le contexte est facultatif.
2. Un lien porte **une ou deux** catégories à sa naissance (R4) : catégories existantes cochées + catégorie créée au passage, deux au total au maximum.
3. L'URL doit être une adresse web complète (`http://` ou `https://`), 2 048 caractères au plus.
4. Une catégorie créée au passage porte un nom de 2 à 60 caractères. Si ce nom existe déjà (sans tenir compte de la casse ni des espaces en bord), la catégorie existante est réutilisée : aucun doublon de nom n'est créé.
5. Le contexte tient en 500 caractères au plus.
6. Le posteur est le membre connecté (R3) ; la date d'origine est le moment de l'ajout (R10), affichée à l'heure de Paris (R11).
7. « Tous les liens » liste les liens du plus récent au plus ancien, chacun avec son adresse, son posteur, sa date et ses catégories.
8. Aucun champ obligatoire supplémentaire ne peut être ajouté à ce formulaire (principe 1).

## Critères d'acceptation

- [x] Depuis n'importe quelle page, un membre connecté atteint le formulaire d'ajout en un clic.
- [x] Un membre ajoute un lien avec une URL et une catégorie existante ; il est ramené sur « Tous les liens », un message confirme l'ajout et le lien apparaît en tête avec son posteur, sa date et sa catégorie.
- [x] Un membre ajoute un lien en créant une nouvelle catégorie depuis le formulaire ; la catégorie existe ensuite et est proposée au prochain ajout.
- [x] Saisir le nom d'une catégorie existante (casse différente) réutilise cette catégorie au lieu d'en créer une seconde.
- [x] Un ajout sans catégorie est refusé avec un message clair ; un ajout avec trois catégories est refusé avec un message clair.
- [x] Une URL invalide (texte libre, `ftp://…`) est refusée avec un message clair.
- [x] La phrase de contexte saisie s'affiche avec le lien ; un lien sans contexte s'ajoute sans erreur.
- [x] Les messages d'erreur sont en français.

## Hors scope

- **Aperçu enrichi (titre, image, description)** : story `carte-apercu`.
- **Détection des doublons de lien** : story `doublon-lien`.
- **Annonce dans Google Chat** : story `annonce-google-chat`.
- **Reprise de l'historique Chat (date et posteur d'origine)** : story `amorcage-historique`.
- **Correction ou retrait d'un lien, reclassement** : V2 (`correction-retrait-lien`, `reclassement-collectif`).
- **Description d'une catégorie, renommage, fusion** : V2.
- **Ajout en modale en deux temps (repère `DESIGN.md`)** : un écran dédié suffit pour tenir les 15 secondes ; la modale pourra venir plus tard sans changer les règles.

## Impacts transverses

- **Traduction / langues** : interface et messages d'erreur en français uniquement (R11).
- **Droits d'accès** : inchangé — tout membre connecté peut ajouter et créer une catégorie (R2) ; rien n'est accessible sans connexion (R1).
- **Cloisonnement des données** : non — tout est partagé (R8).
- **Apparence / déclinaisons** : non — un seul thème, clair et sombre selon la préférence système.
- **Exposition à des tiers** : non.
- **Emails / notifications** : non (l'annonce Chat est une story distincte).
- **Données existantes** : aucune — Radar ne contient encore aucun lien.
- **Comportement par défaut** : « Tous les liens » garde son état vide tant qu'aucun lien n'est ajouté.

## Questions ouvertes

- **Formulaire en page dédiée ou en modale ?** : (a) page dédiée, (b) modale en deux temps. → tranché : (a), plus simple et suffisant pour tenir les 15 secondes.
- **Nom affiché du posteur** : les comptes n'ont qu'une adresse email. Options : (a) la partie avant `@`, (b) un nom saisi sur le profil. → tranché : (a), aucun profil n'existe au MVP.

---

## Annexe — Pistes pour le plan

- Deux structures persistantes candidates : le lien et la catégorie, reliées en plusieurs-à-plusieurs.
- La locale par défaut de l'application est `en` : les messages de validation sortiraient en anglais (`config/packages/translation.yaml`).
- Le champ de recherche et la sidebar de `templates/base.html.twig` accueillent naturellement le bouton « Ajouter un lien ».

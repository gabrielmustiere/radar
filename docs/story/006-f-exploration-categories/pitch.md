# Parcourir les liens d'une catégorie-besoin

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/006-f-exploration-categories/`
> **Amont** : aucun

Les catégories-besoins apparaissent dans la barre latérale ; un clic affiche tous les liens de ce besoin, et chaque carte ouvre le lien d'origine. C'est le parcours « on avait vu un truc là-dessus » : partir d'un besoin et retrouver ce que l'équipe a repéré.

## Contexte

Radar classe chaque lien dans une ou deux catégories-besoins depuis `002-f-ajout-lien`, mais rien ne permet encore de parcourir une catégorie : « Tous les liens » mélange tout. Retrouver par besoin — la raison d'exister de Radar selon la vision — est donc impossible.

Par ailleurs, la carte ne permet pas d'ouvrir le lien d'origine : il faut recopier l'adresse.

## Alignement vision

- **Problème adressé** : le problème central — retrouver par sujet ce que l'équipe a repéré, des mois plus tard.
- **Audience servie** : l'utilisateur principal, le développeur face à un choix technique.
- **Principes respectés** : principe 3 (on navigue par besoin) ; principe 2 (la carte reste lisible, l'ouverture n'est qu'un clic de plus).
- **Hypothèse testée** : hypothèse 1 — le besoin « retrouver par besoin » est partagé ; l'usage de cette navigation en sera la mesure.
- **Impact North Star** : direct — c'est le chemin principal vers un lien ancien rouvert.

## Utilisateurs concernés

- **Membre connecté** — voit les catégories et leur nombre de liens dans la barre latérale, parcourt une catégorie, ouvre un lien d'origine.

## User Stories

- En tant que **développeur**, je veux voir la liste des catégories-besoins avec leur nombre de liens afin de repérer d'un coup d'œil les sujets couverts.
- En tant que **développeur**, je veux afficher tous les liens d'une catégorie, du plus récent au plus ancien, afin de partir de ce que l'équipe a repéré sur mon besoin.
- En tant que **membre**, je veux ouvrir le lien d'origine depuis sa carte, dans un nouvel onglet, afin de lire la source sans perdre ma place dans Radar.
- En tant que **membre**, je veux passer d'une carte à une de ses catégories afin de rebondir vers des liens voisins.

## Règles métier

1. La barre latérale liste les catégories qui portent au moins un lien, par ordre alphabétique, avec leur nombre de liens.
2. La page d'une catégorie liste ses liens du plus récent au plus ancien (date d'origine, R10), en cartes complètes (toutes leurs catégories visibles).
3. La catégorie affichée est mise en évidence dans la barre latérale.
4. Le titre et le visuel d'une carte ouvrent le lien d'origine dans un nouvel onglet ; un bouton « Ouvrir » le fait aussi.
5. Les catégories d'une carte mènent à leur page.
6. Une catégorie inconnue répond « page introuvable ».

## Critères d'acceptation

- [x] La barre latérale affiche les catégories avec leur nombre de liens, sur toutes les pages.
- [x] Cliquer une catégorie affiche ses liens, du plus récent au plus ancien, et uniquement les siens.
- [x] Un lien classé dans deux catégories apparaît dans chacune, avec ses deux catégories sur la carte.
- [x] La catégorie courante est mise en évidence dans la barre latérale.
- [x] Depuis une carte, le titre, le visuel et le bouton « Ouvrir » ouvrent le lien d'origine dans un nouvel onglet.
- [x] Depuis une carte, cliquer une catégorie mène à sa page.
- [x] Une catégorie inexistante répond « page introuvable ».

## Hors scope

- **Mesure des ouvertures** : story `mesure-clics-sortants`.
- **Description d'une catégorie** : V2 `description-categorie`.
- **Renommage, fusion** : V2 `fusion-categories`.
- **Filtre par type de source dans une catégorie** : V2 `filtre-type-source`.
- **Pagination** : story `fil-derniers-ajouts` (même besoin, traité une fois).

## Impacts transverses

- **Traduction / langues** : français.
- **Droits d'accès** : inchangé.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : barre latérale et page de catégorie en clair et sombre ; sur mobile, la liste vit dans le tiroir latéral existant.
- **Exposition à des tiers** : ouvrir un lien d'origine ne transmet pas l'adresse de Radar à la source.
- **Emails / notifications** : non.
- **Données existantes** : les catégories existantes apparaissent immédiatement.
- **Comportement par défaut** : « Tous les liens » reste l'accueil.

## Questions ouvertes

- **Afficher les catégories vides ?** : (a) non, (b) oui. → tranché : (a), une catégorie naît avec un lien ; une catégorie vide n'aide personne.

---

## Annexe — Pistes pour le plan

- La barre latérale vit dans `templates/base.html.twig` ; un Twig Component peut y charger les catégories.

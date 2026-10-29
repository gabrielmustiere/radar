# Juger un lien sans cliquer grâce à sa carte

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/003-f-carte-apercu/`
> **Amont** : aucun

Chaque lien de Radar devient une carte : aperçu visuel, titre et description tirés de la source, type de source, posteur, date et catégories. Quand la source ne livre rien, une carte de repli soignée prend le relais. On décide si le lien vaut le clic sans quitter Radar.

## Contexte

Depuis la story `002-f-ajout-lien`, « Tous les liens » affiche des adresses brutes. Une URL ne dit pas ce qu'il y a derrière : il faut l'ouvrir pour savoir si elle vaut le détour, exactement le défaut du flux Google Chat que Radar veut corriger.

Sans carte lisible, la curiosité — moteur de l'usage selon la vision — ne s'enclenche pas : personne ne parcourt une liste d'URL.

## Alignement vision

- **Problème adressé** : rendre la veille exploitable d'un coup d'œil, des mois après l'ajout.
- **Audience servie** : tous les membres, au moment de parcourir ou de retrouver un lien.
- **Principes respectés** : principe 2 (une carte se lit sans clic, carte de repli pour les sources fermées) ; principe 1 (l'aperçu se construit seul, le posteur ne saisit rien de plus).
- **Hypothèse testée** : aucune directement ; condition de l'usage récurrent.
- **Impact North Star** : indirect — une carte lisible rend un lien ancien identifiable, donc rouvrable.

## Utilisateurs concernés

- **Membre connecté** — lit sur chaque carte l'aperçu, le type de source, le posteur, la date et les catégories.
- **Posteur** — n'a rien de plus à saisir : l'aperçu se construit après l'ajout.

## User Stories

- En tant que **membre**, je veux voir le titre, l'image et la description d'un lien afin de décider s'il vaut le clic.
- En tant que **membre**, je veux savoir d'un coup d'œil si un lien est une vidéo YouTube, un dépôt GitHub, un post X, une image ou un article afin de choisir ce que j'ouvre.
- En tant que **membre**, je veux lire sur la carte qui a posté le lien, quand, et dans quelles catégories, afin de juger sa pertinence.
- En tant que **membre**, je veux une carte propre même quand la source ne fournit pas d'aperçu afin que la liste reste lisible.
- En tant que **posteur**, je ne veux PAS attendre l'aperçu pour terminer mon ajout afin que l'ajout reste sous les 15 secondes.

## Règles métier

1. L'aperçu (titre, description, image, nom du site) se construit à partir de ce que la source expose publiquement, après l'ajout, sans bloquer le posteur.
2. Le type de source se déduit de l'adresse : dépôt GitHub, vidéo YouTube, post ou compte X, image (adresse d'un fichier image), article (tout le reste).
3. Une adresse d'image a pour aperçu l'image elle-même.
4. Les liens X utilisent la carte de repli : X ferme ses aperçus aux accès anonymes (risque de la vision).
5. Tant que l'aperçu n'est pas construit, ou s'il n'a pas pu l'être, la carte de repli s'affiche : type de source, domaine, et l'adresse en guise de titre.
6. Une image d'aperçu qui ne se charge pas laisse place au visuel de repli, jamais à une image cassée.
7. Chaque carte montre toujours, sans clic : type de source, domaine, posteur, date d'origine, catégories (R3, C2.4) ; et le contexte du posteur s'il existe.
8. Radar n'interroge que des adresses publiques d'internet, jamais le réseau interne.

## Critères d'acceptation

- [x] Après l'ajout d'un article exposant un aperçu, sa carte montre son titre, sa description et son image.
- [x] Une carte affiche le type de source, le domaine, le posteur, la date et les catégories.
- [x] Un lien GitHub, YouTube, X, image ou autre affiche le type de source correspondant.
- [x] Un lien dont la source ne répond pas, ou ne fournit pas d'aperçu, s'affiche en carte de repli soignée (sans image cassée ni champ vide).
- [x] Un lien X s'affiche en carte de repli.
- [x] L'ajout d'un lien n'attend pas la construction de l'aperçu.
- [x] Une adresse pointant vers le réseau interne n'est pas interrogée.

## Hors scope

- **Cartes spécifiques par source (étoiles d'un dépôt, durée d'une vidéo…)** : V2 `cartes-par-source`.
- **Relance de l'aperçu d'un lien** : V2 `relance-apercu`.
- **Ouverture du lien d'origine depuis la carte avec mesure** : stories `exploration-categories` et `mesure-clics-sortants`.
- **Hébergement des images d'aperçu dans Radar** : l'image est affichée depuis la source.

## Impacts transverses

- **Traduction / langues** : libellés des types de source en français.
- **Droits d'accès** : inchangé.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : la carte suit le langage visuel Markit, en clair et en sombre.
- **Exposition à des tiers** : Radar consulte la page publique du lien pour en lire l'aperçu ; les images d'aperçu sont chargées depuis la source par le navigateur du membre.
- **Emails / notifications** : non.
- **Données existantes** : les liens déjà ajoutés s'affichent en carte de repli tant que leur aperçu n'est pas construit.
- **Comportement par défaut** : toutes les listes de liens passent en cartes.

## Questions ouvertes

- **Aperçu construit pendant l'ajout ou après ?** : (a) après, en arrière-plan, (b) pendant. → tranché : (a), l'ajout ne doit pas attendre une source lente.
- **Tenter quand même X ?** : (a) carte de repli directe, (b) tenter et retomber sur le repli. → tranché : (a), X renvoie une page générique sans intérêt.

---

## Annexe — Pistes pour le plan

- Messenger (transport Doctrine) est déjà configuré et un worker tourne en dev (`.symfony.local.yaml`).
- HttpClient est installé ; `NoPrivateNetworkHttpClient` couvre la règle 8.
- `Dom\HTMLDocument` (PHP 8.4+) est disponible pour lire les balises OpenGraph.

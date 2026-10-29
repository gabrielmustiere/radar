# Consulter le fil des derniers liens ajoutés

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/010-f-fil-derniers-ajouts/`
> **Amont** : aucun

La page d'accueil devient un fil : les liens les plus récents d'abord, regroupés par jour (« Aujourd'hui », « Hier », « lundi 26 octobre »), page par page. On y passe pour rattraper ce que l'équipe a partagé, comme on parcourt un flux — mais un flux qui ne s'enfouit pas.

## Contexte

« Tous les liens » affiche déjà les cartes du plus récent au plus ancien, mais en un bloc sans repère de temps ni limite : avec 250 à 1 000 liens par an, la page deviendra interminable et l'on ne saura plus ce qui est nouveau. Les pages de catégorie et de recherche ont le même défaut de longueur (pagination reportée ici par `006-f-exploration-categories` et `007-f-recherche-mot-cle`).

La vision fait de la curiosité le moteur de l'usage : sans un fil agréable au quotidien, personne ne revient.

## Alignement vision

- **Problème adressé** : faire revenir les membres au quotidien, condition pour qu'ils contribuent et retrouvent.
- **Audience servie** : tous les membres, en visite spontanée (parcours P4).
- **Principes respectés** : principe 2 (cartes lisibles sans clic) ; anti-objectif « lieu de discussion » respecté (le fil ne montre que des liens).
- **Hypothèse testée** : aucune directement.
- **Impact North Star** : indirect — la curiosité entretient l'habitude de revenir, donc de rouvrir.

## Utilisateurs concernés

- **Membre connecté** — parcourt le fil par jour et page par page ; les pages de catégorie et de recherche deviennent paginées.

## User Stories

- En tant que **membre**, je veux voir les derniers liens regroupés par jour afin de repérer d'un coup d'œil ce qui est nouveau depuis ma dernière visite.
- En tant que **membre**, je veux remonter le fil page par page afin de parcourir les liens plus anciens sans charger une page interminable.
- En tant que **membre**, je veux que les pages de catégorie et de recherche soient paginées de la même façon afin de garder des pages courtes.

## Règles métier

1. Le fil liste les liens par date d'origine décroissante (R10) ; un lien repris de Chat se range donc à sa date d'origine, pas en tête.
2. Les liens du fil sont regroupés par jour (heure de Paris, R11) sous un intitulé : « Aujourd'hui », « Hier », puis le jour en toutes lettres (« lundi 26 octobre », avec l'année si elle diffère de l'année en cours).
3. Une page compte 24 liens au plus ; des liens « Plus récents » / « Plus anciens » permettent de naviguer.
4. Le compteur en tête de page donne le nombre total de liens, pas celui de la page.
5. Les pages de catégorie et de recherche suivent la même pagination, sans regroupement par jour.
6. Une page au-delà de la dernière répond « page introuvable ».

## Critères d'acceptation

- [x] Le fil regroupe les liens sous « Aujourd'hui », « Hier » ou le jour en toutes lettres.
- [x] Un lien ajouté aujourd'hui apparaît en tête, sous « Aujourd'hui ».
- [x] Au-delà de 24 liens, la page suivante est accessible par « Plus anciens », et la première par « Plus récents ».
- [x] Le compteur de la page indique le total.
- [x] Une catégorie de plus de 24 liens est paginée.
- [x] Une recherche de plus de 24 résultats est paginée.
- [x] Une page inexistante répond « page introuvable ».

## Hors scope

- **« Depuis ma dernière visite »** : V2 `nouveautes-depuis-derniere-visite`.
- **Défilement infini** : la pagination explicite suffit et reste partageable.
- **Filtre par type de source** : V2.

## Impacts transverses

- **Traduction / langues** : jours et mois en français.
- **Droits d'accès** : inchangé.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : intitulés de jour et navigation en clair et sombre.
- **Exposition à des tiers** : non.
- **Emails / notifications** : non.
- **Données existantes** : tous les liens existants entrent dans le fil.
- **Comportement par défaut** : l'accueil reste « Tous les liens ».

## Questions ouvertes

- **Lien repris : à sa date d'origine ou à sa date de reprise ?** : (a) origine, (b) reprise. → tranché : (a), cohérent avec l'âge des liens (R10) ; une reprise n'est pas une nouveauté.

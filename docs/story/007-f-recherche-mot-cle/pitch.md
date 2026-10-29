# Retrouver un lien par mot

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/007-f-recherche-mot-cle/`
> **Amont** : aucun

Un membre tape quelques mots dans la barre de recherche ; Radar affiche les liens dont le titre, la description, le contexte, l'adresse ou une catégorie contiennent ces mots. C'est l'autre chemin pour retrouver un lien dont on se souvient vaguement.

## Contexte

La barre de recherche de la topbar existe depuis le socle visuel, mais ne mène nulle part. Pour retrouver un lien, il faut deviner sa catégorie ou faire défiler « Tous les liens ». Le développeur qui se souvient d'un mot (« playwright », « maestro », « monorepo ») n'a aucun moyen direct de le retrouver — c'est précisément ce que Google Chat fait mal aujourd'hui, puisqu'il ne cherche que dans les messages bruts.

## Alignement vision

- **Problème adressé** : le problème central — retrouver « le truc qu'on avait vu » des mois plus tard.
- **Audience servie** : l'utilisateur principal, le développeur face à un choix technique.
- **Principes respectés** : principe 2 (les résultats sont des cartes lisibles sans clic) ; principe 3 (les catégories-besoins sont elles-mêmes cherchables).
- **Hypothèse testée** : hypothèse 1 — le besoin de retrouver est partagé.
- **Impact North Star** : direct — chemin vers un lien ancien rouvert.

## Utilisateurs concernés

- **Membre connecté** — cherche depuis n'importe quelle page et lit les résultats en cartes.

## User Stories

- En tant que **développeur**, je veux taper un ou plusieurs mots et voir les liens qui les contiennent afin de retrouver un lien dont je me souviens vaguement.
- En tant que **membre**, je veux que la recherche couvre aussi le contexte écrit par le posteur et les catégories afin de retrouver un lien par ce qu'on en a dit.
- En tant que **membre**, je veux savoir clairement quand rien ne correspond afin de reformuler ou de parcourir une catégorie.

## Règles métier

1. La recherche porte sur le titre, la description, le contexte, l'adresse et les noms de catégorie de chaque lien.
2. Plusieurs mots se cumulent : un lien correspond s'il contient **chacun** des mots, dans n'importe lequel de ces champs.
3. La casse est ignorée pour les lettres sans accent ; les lettres accentuées doivent correspondre exactement, casse comprise (« ecran » ne trouve pas « écran », « écran » ne trouve pas « Écran »).
4. Les mots de moins de 2 caractères sont ignorés ; au plus 5 mots sont pris en compte.
5. Les caractères saisis sont cherchés tels quels (`%` ou `_` ne sont pas des jokers).
6. Les résultats s'affichent en cartes, du plus récent au plus ancien, avec leur nombre.
7. Une recherche sans résultat le dit et propose de parcourir les catégories.
8. La recherche est disponible depuis la topbar sur grand écran et depuis la page de résultats sur tous les écrans.

## Critères d'acceptation

- [x] Depuis la topbar, saisir un mot et valider affiche la page de résultats.
- [x] Un lien est trouvé par un mot de son titre, de sa description, de son contexte, de son adresse ou d'une de ses catégories.
- [x] Deux mots ne trouvent que les liens qui contiennent les deux.
- [x] La recherche ignore la casse.
- [x] Une recherche sans résultat affiche un message clair.
- [x] Chercher `%` ne renvoie pas tous les liens.
- [x] Le mot cherché reste affiché dans le champ de recherche.

## Hors scope

- **Recherche insensible aux accents, tolérance aux fautes, pertinence** : à reconsidérer si l'usage le demande.
- **Filtre par type de source** : V2 `filtre-type-source`.
- **Pagination des résultats** : story `fil-derniers-ajouts`.

## Impacts transverses

- **Traduction / langues** : français.
- **Droits d'accès** : inchangé.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : page de résultats en clair et sombre ; sur mobile, champ de recherche dans la page de résultats.
- **Exposition à des tiers** : non.
- **Emails / notifications** : non.
- **Données existantes** : tous les liens existants sont cherchables.
- **Comportement par défaut** : inchangé.

## Questions ouvertes

- **Insensibilité aux accents** : (a) non au MVP, (b) oui. → tranché : (a), la base SQLite ne compare sans accents qu'avec une fonction ajoutée ; à reconsidérer à l'usage. Recommandé : surveiller les recherches infructueuses rapportées.
- **Chercher dans l'adresse** : ajouté à la capacité C4.2 — les liens X en carte de repli n'ont pas d'autre texte cherchable.

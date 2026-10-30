# Publier automatiquement les articles des sources suivies

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/012-f-sources-automatiques/`
> **Amont** : aucun

Un admin enregistre un flux RSS ou Atom (par exemple le blog Symfony) avec une ou deux catégories. Toutes les heures, Radar publie dans le fil chaque nouvel article de ce flux, classé dans ces catégories, posté par le compte « Radar », marqué « auto » sur sa carte et annoncé dans Google Chat.

## Contexte

Certaines sources sont suivies par toute l'équipe et publient régulièrement des articles utiles : blog Symfony, notes de version, blogs d'outils de la stack. Aujourd'hui, un de leurs articles n'entre dans Radar que si un membre pense à le coller. Les articles de ces sources sont donc ajoutés irrégulièrement, en double dans Chat, ou pas du tout, alors que l'équipe a déjà décidé que la source valait d'être suivie.

Brancher la source une fois, avec ses catégories, fait entrer ces articles dans Radar sans effort, classés et retrouvables comme les autres liens.

## Alignement vision

- **Problème adressé** : retrouver par besoin ce que l'équipe suit. Les articles des sources suivies deviennent retrouvables des mois plus tard au lieu de dépendre de la mémoire d'un posteur.
- **Audience servie** : tous les membres (fil, recherche, catégories) ; les admins gèrent les sources.
- **Principes tendus** :
  - **Principe 4** (« un lien entre uniquement par l'outil, où il est classé ») : un article automatique entre bien par l'outil et il est classé, mais d'office, par les catégories que l'admin a choisies pour la source et non par un posteur. La vision est éditée en ce sens (édition ciblée du 2026-10-30) : choisir une source à suivre est une forme de sélection collective.
  - **Unfair advantage** (« une sélection faite par des collègues ») : la sélection porte sur la source et non sur chaque article. Le marqueur « auto » rend la différence visible sur chaque carte.
  - **R2 — Pas de curateur** : la gestion des sources et de la liste des admins est réservée aux admins. Classer, reclasser et ajouter restent ouverts à tous.
  - **Anti-objectif « liens bruts non classés »** : respecté, chaque article naît avec les catégories de sa source (R4).
- **Hypothèse testée** : aucune directement. Un volume automatique élevé pourrait masquer la contribution manuelle (signal d'arrêt « moins de 5 contributeurs distincts par mois ») : les liens du compte « Radar » ne comptent pas comme contributeur.
- **Impact North Star** : positif à moyen terme (plus de liens anciens à rouvrir), à mesurer.

## Utilisateurs concernés

- **Admin** : ajoute et retire les sources, consulte leur état, gère la liste des emails admin.
- **Membre connecté** : voit les articles automatiques dans le fil, les catégories et la recherche, avec un marqueur « auto ». Peut les reclasser comme tout lien (quand `reclassement-collectif` sera livré).
- **Membres de l'espace Google Chat** : voient chaque article automatique annoncé.
- **Compte « Radar »** : compte système, posteur des articles automatiques. Il ne peut pas se connecter.
- **Exploitant** (celui qui déploie) : désigne l'admin permanent par configuration et fait tourner la relève horaire.

## User Stories

- En tant qu'**admin**, je veux enregistrer un flux RSS ou Atom avec une ou deux catégories afin que ses nouveaux articles entrent dans Radar sans que personne ait à les coller.
- En tant qu'**admin**, je veux voir pour chaque source sa dernière relève réussie et sa dernière erreur afin de repérer un flux cassé.
- En tant qu'**admin**, je veux retirer une source afin d'arrêter de la suivre, sans perdre les liens qu'elle a déjà apportés.
- En tant qu'**admin**, je veux ajouter ou retirer un email de la liste des admins afin de partager la gestion des sources.
- En tant que **membre**, je veux reconnaître d'un coup d'œil qu'un lien est arrivé automatiquement afin de savoir qu'aucun collègue ne l'a choisi individuellement.
- En tant que **membre de l'espace Chat**, je veux voir chaque article automatique annoncé afin de découvrir la veille des sources suivies là où je suis déjà.
- En tant que **membre**, je ne veux PAS voir dans Radar les anciens articles d'une source qu'on vient d'ajouter, ni un même article deux fois.
- En tant qu'**exploitant**, je veux qu'un admin désigné par configuration ne puisse jamais être retiré depuis l'interface afin que Radar ne se retrouve jamais sans admin.

## Règles métier

### Admins

1. Un admin est un membre dont l'email figure dans la liste des admins.
2. Seul un admin consulte et modifie la liste des admins et la page des sources. Un membre qui n'est pas admin ne voit ni l'une ni l'autre, et l'entrée de menu n'apparaît pas pour lui.
3. Un admin permanent est désigné par configuration. Il est admin en toutes circonstances, figure dans la liste comme non retirable, et l'interface ne permet pas de le retirer.
4. On peut ajouter l'email d'une personne qui n'a pas encore de compte : elle est admin dès qu'elle se connecte.

### Sources

5. Une source est l'adresse d'un flux RSS ou Atom et une ou deux catégories existantes (R4). Son nom est celui que le flux annonce.
6. À l'enregistrement, Radar vérifie que l'adresse répond un flux lisible. Sinon, la source est refusée avec un message explicite. Une même adresse de flux ne peut être enregistrée qu'une fois.
7. À l'enregistrement, les articles déjà présents dans le flux sont ignorés : seuls ceux qui apparaissent ensuite sont publiés.
8. Toutes les sources sont relevées toutes les heures.
9. Un article déjà vu pour une source n'est jamais republié, même s'il est modifié ou réordonné dans le flux.
10. Retirer une source arrête sa relève. Les liens qu'elle a apportés restent dans Radar, avec leur marqueur « auto ».

### Publication

11. Un nouvel article devient un lien : l'adresse de l'article, les catégories de la source, le compte « Radar » comme posteur, la date de la relève comme date d'ajout, et aucun contexte.
12. Un article dont l'adresse existe déjà dans Radar (ajout manuel ou autre source) est ignoré, et le lien existant n'est pas modifié (R6).
13. L'aperçu de la carte suit le circuit normal des liens (cartes par source), pas le contenu du flux.
14. La carte d'un lien automatique porte un marqueur « auto ». Il n'y a pas de filtre pour masquer ces liens.
15. Chaque lien automatique est annoncé une seule fois dans Google Chat, après son aperçu, comme un ajout manuel (R5).

### Erreurs

16. Un flux injoignable ou illisible n'empêche jamais la relève des autres sources. La relève suivante réessaie.
17. La page des sources affiche, pour chaque source, la date de la dernière relève réussie et la dernière erreur rencontrée. Aucune notification n'est envoyée en cas d'erreur.

## Critères d'acceptation

- [ ] Un membre qui n'est pas admin n'accède ni à la page des sources ni à la liste des admins, et ne voit pas leur entrée de menu.
- [ ] L'admin permanent configuré accède aux deux pages, et l'interface ne permet pas de le retirer de la liste.
- [ ] Un admin ajoute un email à la liste ; la personne correspondante accède à la page des sources à sa connexion suivante.
- [ ] Un admin enregistre le flux `https://feeds.feedburner.com/symfony/blog` avec une catégorie ; la source apparaît avec le nom annoncé par le flux.
- [ ] L'enregistrement d'une adresse qui n'est pas un flux lisible est refusé avec un message ; l'enregistrement d'une adresse déjà suivie est refusé.
- [ ] Juste après l'enregistrement d'une source, aucun de ses articles existants n'apparaît dans le fil.
- [ ] À la relève suivante, un article apparu dans le flux est publié dans le fil avec les catégories de la source, le posteur « Radar » et le marqueur « auto ».
- [ ] Un article publié est annoncé une fois dans Google Chat ; une relève suivante ne le republie ni ne le réannonce.
- [ ] Un article dont l'adresse existe déjà dans Radar n'est pas publié, et le lien existant est inchangé.
- [ ] Un flux en erreur n'empêche pas la publication des articles d'une autre source ; son erreur est visible sur la page des sources.
- [ ] Retirer une source arrête la publication de ses articles ; ses liens déjà publiés restent visibles avec le marqueur « auto ».
- [ ] Le compte « Radar » ne peut pas se connecter.

## Hors scope

- **Tri manuel avant publication** (file « à trier ») : écarté au profit d'une publication directe, le marqueur « auto » assumant la différence.
- **Filtre « masquer les liens auto »** : écarté explicitement.
- **Reprise de l'historique d'un flux** : seuls les articles postérieurs à l'enregistrement de la source sont publiés.
- **Sources qui ne sont pas des flux RSS/Atom** (newsletters, comptes X, chaînes YouTube sans flux, pages sans flux) : hors périmètre.
- **Fréquence de relève réglable par source** : une seule fréquence, horaire.
- **Modification d'une source** (changer ses catégories ou son adresse) : on la retire et on la recrée.
- **Notification d'un flux en erreur** : l'état se consulte sur la page des sources.
- **Rôle admin pour d'autres usages** (modération, gestion des membres) : l'admin ne sert qu'aux sources et à sa propre liste.

## Impacts transverses

- **i18n / traduction** : interface et messages en français (R11), comme le reste de Radar.
- **Permissions** : nouveau statut admin, déterminé par la liste des emails et l'admin permanent configuré. Il ne concerne que les sources et la liste des admins.
- **Emails / notifications** : une annonce Google Chat par article automatique publié, via l'annonce existante.
- **Exposition à des tiers** : Radar interroge les adresses de flux saisies par les admins ; les mêmes protections que pour les aperçus s'appliquent (adresses publiques uniquement).
- **Migration de données** : création du compte « Radar ». Les liens existants ne sont pas concernés et ne portent pas le marqueur « auto ».
- **Exploitation** : la relève horaire demande un processus qui tourne en continu, en plus des workers actuels.
- **Comportement par défaut** : sans source enregistrée, rien ne change. Sans admin permanent configuré, seuls les emails de la liste sont admins.

## Questions ouvertes

- **Plafond par relève ?** Un flux qui change ses adresses ou qui republie tout son historique ferait publier et annoncer d'un coup 10 à 50 articles. Options : (a) aucun plafond, (b) au plus N articles publiés par source et par relève, les suivants ignorés. Recommandé : (b) avec N = 5, à trancher au plan. → tranché : (a), aucun plafond.
- **Nom de la source sur la carte et dans l'annonce ?** Le marqueur « auto » dit qu'un lien est automatique, pas d'où il vient. Options : (a) marqueur seul, (b) marqueur et nom de la source sur la carte et dans l'annonce (« posté par Radar · Symfony Blog »). Recommandé : (b), sans quoi rien ne relie un lien à sa source une fois celle-ci retirée. → tranché : (b).
- **Retrait d'un lien automatique** : R12 réserve le retrait d'un lien à son posteur ; le compte « Radar » ne peut pas se connecter, donc personne ne pourrait retirer un article automatique non pertinent. À trancher au cadrage de `correction-retrait-lien` (par exemple : un admin peut retirer un lien automatique).
- **Volume d'annonces dans Chat** : une source comme le blog Symfony publie environ 1 à 2 articles par jour, à comparer aux 5 à 20 liens manuels par semaine. À mesurer après lancement ; si les annonces automatiques noient les annonces manuelles, reconsidérer la règle 15.

## Annexe — Pistes pour le plan

Pistes à confirmer, non contractuelles.

**Relève**
- `symfony/scheduler` n'est pas installé. Un `RecurringMessage::every('1 hour', …)` déclenche un message par source (ou un message global qui fan-out), consommé par un worker `messenger:consume scheduler_default` à ajouter dans `.symfony.local.yaml` et au déploiement.
- Aucun parseur de flux installé. Options : `laminas/laminas-feed`, `simplepie/simplepie`, ou un parseur maison RSS 2.0 + Atom sur `Dom\XMLDocument` (PHP 8.4+). À comparer au plan.
- Interroger les flux avec le client sécurisé des aperçus (`app.preview_http_client`, `NoPrivateNetworkHttpClient`, rate-limit par hôte) : l'adresse est saisie par un humain.
- Mémoriser les articles vus par source (identifiant `guid`/`id` ou adresse) ; à l'enregistrement, marquer comme vus tous les articles présents (règle 7).

**Publication**
- Passer par `LinkManager` pour garder le doublon (R6), la normalisation et le dispatch `FetchLinkPreview` ; l'annonce suit déjà l'aperçu via `LinkAnnouncer` (`announcedAt` garde R5). `LinkSubmission` porte aujourd'hui la validation 1 à 2 catégories : une entrée dédiée sera sans doute nécessaire.
- Marqueur « auto » : relation nullable `Link → Source`. Si la source est retirée par suppression réelle, le marqueur se perd ; un retrait logique (source désactivée) le conserve (règle 10).
- Le compte « Radar » : un `User` créé par migration, sans mot de passe utilisable. `getDisplayName()` dérive le nom de la partie locale de l'email : choisir un email dont la partie locale donne « Radar », ou ajuster l'affichage.
- Hors fil : vérifier les écrans qui comptent les contributeurs (mesure C6.x) pour exclure le compte « Radar ».

**Admins**
- Aucun `ROLE_ADMIN` aujourd'hui (tout le monde est `ROLE_USER`). Liste d'emails en base + variable d'env pour l'admin permanent. Une entité ne doit pas injecter de service : déterminer le rôle via un voter, ou au chargement de l'utilisateur, plutôt que dans `User::getRoles()`.
- Fixtures : `admin@example.com` admin permanent en dev et test.

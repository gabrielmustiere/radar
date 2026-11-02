# Ajouter la page en cours à Radar depuis le navigateur, sans quitter la page

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/014-f-extension-navigateur/`
> **Amont** : aucun

Une extension Chrome affiche le formulaire d'ajout de Radar dans une bulle, ouverte depuis son icône ou par clic droit sur la page, avec l'adresse de la page déjà remplie : le membre classe et partage sa trouvaille sans changer d'onglet. L'extension agit au nom du membre grâce à une clé personnelle qui ne sert qu'à l'ajout, expire si elle n'est plus utilisée et se révoque depuis Radar.

## Contexte

Aujourd'hui, partager une trouvaille dans Radar demande de quitter la page lue : copier son adresse, ouvrir ou retrouver l'onglet Radar, ouvrir le formulaire d'ajout, coller l'adresse, classer, valider, puis revenir à la lecture. Poster le même lien dans Google Chat coûte à peu près autant de gestes, et c'est l'habitude installée. L'écart que Radar doit combler pour gagner la contribution se joue donc sur ces quelques secondes et ce changement d'onglet.

Si on ne fait rien, l'ajout reste un détour : une partie des liens continue d'être postée directement dans Chat, non classée, et redevient introuvable en quelques jours — c'est précisément l'un des deux signaux d'arrêt de la vision.

## Alignement vision

- **Problème adressé** : faire entrer la veille dans Radar plutôt que dans Chat, au moment même où le lien est trouvé, pour qu'il soit classé et retrouvable des mois plus tard.
- **Audience servie** : tous les contributeurs (développeurs, producteurs, QA, managers), au moment de partager une trouvaille.
- **Principes respectés** : principe 1 (l'ajout ne coûte pas plus qu'un post Chat — ici moins : pas de copier-coller, pas de changement d'onglet) ; principe 4 (l'outil reste la porte d'entrée : l'extension ajoute dans Radar, qui annonce dans Chat) ; principe 5 (mêmes règles de classement, une à trois catégories). Aucun anti-objectif touché : l'extension sert l'ajout partagé, pas des signets privés.
- **Hypothèse testée** : hypothèse 2 (les contributeurs migrent de Chat vers l'outil si l'ajout prend 15 secondes ou moins), sans mesure dédiée dans l'outil.
- **Impact North Star** : indirect — plus de liens classés dans Radar aujourd'hui, plus de liens anciens rouverts dans plusieurs mois.

## Utilisateurs concernés

- **Membre connecté** (mêmes droits pour tous, R2) — installe l'extension, obtient sa clé personnelle dans Radar, ajoute la page en cours depuis l'icône ou le clic droit ; gère sa clé (régénérer, révoquer).
- **Admin des sources** — aucun changement : sa clé ne lui donne aucun droit d'admin, la gestion des sources et des admins reste dans Radar.
- **Membres de l'espace Google Chat** — aucun changement perçu : un lien ajouté par l'extension est annoncé comme les autres.
- **Visiteur non connecté** — aucun changement (R1) : sans clé valide, l'extension n'enregistre rien et ne montre rien de Radar.

## User Stories

- En tant que **membre**, je veux cliquer sur l'icône de l'extension et retrouver le formulaire d'ajout avec l'adresse de la page déjà remplie, afin de partager ma trouvaille sans quitter ce que je lis.
- En tant que **membre**, je veux faire un clic droit sur la page et choisir « Ajouter cette page à Radar », afin d'atteindre le même formulaire par le geste qui me vient naturellement.
- En tant que **membre**, je veux, dans la bulle, écrire pourquoi c'est utile, choisir ou créer une à trois catégories, et être averti si le lien existe déjà comme dans Radar, afin de ne pas avoir deux façons d'ajouter qui suivent des règles différentes.
- En tant que **membre**, je veux, après l'ajout, voir une confirmation et pouvoir ouvrir la carte dans Radar, afin de vérifier le résultat si je le souhaite.
- En tant que **membre**, je veux générer dans Radar une clé personnelle et la coller dans l'extension, afin de relier l'extension à mon compte sans y taper mon mot de passe.
- En tant que **membre**, je veux régénérer ou révoquer ma clé depuis Radar, afin de couper l'accès d'un poste perdu ou d'une clé exposée.
- En tant que **membre**, je ne veux PAS qu'une personne qui aurait récupéré ma clé puisse lire Radar ou agir au-delà de l'ajout de lien, afin que la fuite d'une clé reste limitée.
- En tant que **membre**, je veux, si ma clé est absente, révoquée ou expirée, que l'extension me le dise et me mène à la page de Radar où en obtenir une, afin de ne pas perdre ma saisie dans une erreur incompréhensible.

## Règles métier

1. **Navigateurs** : Chrome et les navigateurs qui acceptent ses extensions (Edge, Brave, Arc…). Firefox et Safari ne sont pas couverts.
2. **Installation** : l'extension est publiée sur le Chrome Web Store en mode non répertorié ; les membres l'installent depuis un lien partagé, les mises à jour sont automatiques.
3. **Deux points d'entrée, un seul formulaire** : l'icône de l'extension et l'entrée « Ajouter cette page à Radar » du clic droit sur la page ouvrent le même formulaire, prérempli avec l'adresse de la page en cours. Le clic droit n'agit que sur la page en cours (ni lien sous le curseur, ni texte sélectionné).
4. **Parité avec l'ajout de Radar** : le formulaire de la bulle suit toutes les règles de l'ajout (stories 002, 004 et 013) — ordre contexte, catégories, adresse ; contexte facultatif ; une à trois catégories, choisies ou créées à la frappe ; alerte de doublon renvoyant vers la carte existante et proposant d'y ajouter les catégories choisies. L'adresse préremplie reste modifiable ; une adresse refusée par Radar (page interne du navigateur, fichier local…) l'est avec le même message que dans Radar.
5. **Même effet qu'un ajout dans Radar** : le lien a pour posteur le propriétaire de la clé (R3), il est annoncé une fois dans Google Chat (R5) ; un complément de catégories sur doublon n'est pas annoncé.
6. **Après un ajout réussi** : la bulle affiche une confirmation avec un lien vers la carte dans Radar.
7. **Clé personnelle** : chaque membre a au plus une clé. En générer une nouvelle révoque immédiatement l'ancienne. Le membre peut aussi la révoquer sans en générer une autre.
8. **Clé affichée une seule fois** : la page « Extension navigateur », accessible depuis le menu du membre dans Radar, montre la clé uniquement au moment de sa génération, avec un bouton pour la copier. Ensuite, elle n'affiche plus que la date de création et la date de dernière utilisation, signale une clé expirée, et propose « Régénérer » et « Révoquer ». Radar ne peut jamais réafficher une clé existante ; pour équiper un second poste, le membre recolle la même clé ou en régénère une.
9. **Expiration** : une clé qui n'a pas servi pendant 90 jours expire d'elle-même. Chaque usage de l'extension (ouverture de la bulle comprise) repousse cette échéance ; un membre actif ne recolle jamais sa clé.
10. **Portée de la clé** : la clé ne permet que le parcours d'ajout — afficher les catégories pour les choisir, créer une catégorie au fil de la saisie, détecter un doublon et compléter ses catégories, ajouter le lien. Elle ne donne accès ni à la lecture de Radar (cartes, recherche, fil), ni à la gestion des sources ou des admins, même quand son propriétaire est admin.
11. **Indépendance vis-à-vis de la session** : se connecter ou se déconnecter de Radar n'a aucun effet sur la clé, et la clé n'ouvre pas de session dans Radar.
12. **Clé invalide** : sans clé, ou avec une clé révoquée ou expirée, l'extension n'enregistre rien ; elle l'explique et propose d'ouvrir la page « Extension navigateur » de Radar.
13. **Gestion par le seul propriétaire** : seul le membre voit et gère sa clé ; personne d'autre, admin compris, ne la voit ni ne la révoque.

## Critères d'acceptation

- [x] Depuis la page « Extension navigateur » de Radar, un membre génère une clé, la copie, puis ne peut plus la revoir en revenant sur la page ; il y voit sa date de création.
- [ ] Après avoir collé sa clé dans l'extension, un membre clique sur l'icône sur une page web : la bulle affiche le formulaire d'ajout avec l'adresse de la page déjà remplie.
- [ ] Le clic droit sur une page propose « Ajouter cette page à Radar », qui mène au même formulaire prérempli.
- [x] Un membre ajoute un lien depuis la bulle avec un contexte, une catégorie existante et une catégorie créée à la frappe : la carte apparaît dans Radar avec ce membre pour posteur et ces deux catégories, et le lien est annoncé dans Google Chat.
- [x] Après l'ajout, la bulle affiche une confirmation et un lien qui ouvre la carte dans Radar.
- [x] Sur une page déjà présente dans Radar, la bulle affiche l'alerte de doublon avec le lien vers la carte existante et propose d'y ajouter les catégories choisies, comme dans Radar ; ce complément n'est pas annoncé dans Google Chat.
- [x] Une adresse refusée par Radar (par exemple une page interne du navigateur) n'est pas ajoutée et la bulle affiche le même message que Radar.
- [x] Après « Régénérer », l'ancienne clé est refusée par l'extension et la nouvelle fonctionne.
- [x] Après « Révoquer », l'extension n'enregistre plus rien, explique que la clé n'est plus valide et propose d'ouvrir la page « Extension navigateur ».
- [x] Une clé inutilisée depuis plus de 90 jours est refusée avec le même message ; une clé utilisée régulièrement continue de fonctionner au-delà de 90 jours après sa création.
- [x] Après chaque ajout par l'extension, la page « Extension navigateur » affiche la date de dernière utilisation à jour.
- [x] Avec la clé d'un membre, il est impossible de consulter les cartes, la recherche ou le fil de Radar, et impossible de gérer les sources ou les admins, y compris quand ce membre est admin.
- [x] La déconnexion de Radar n'empêche pas l'extension d'ajouter un lien avec une clé valide.
- [ ] L'extension s'installe depuis le lien du Chrome Web Store sur Chrome et sur Edge.
- [ ] Un ajout depuis l'icône (contexte vide, une catégorie existante, adresse préremplie) se fait en moins de 15 secondes.

## Hors scope

- **Firefox et Safari** : seuls Chrome et les navigateurs compatibles sont visés au lancement.
- **Clic droit sur un lien de la page ou sur un texte sélectionné** : seule la page en cours est proposée ; le contexte se tape dans la bulle.
- **Lecture de Radar dans l'extension** (recherche, fil, signal « déjà dans Radar » sur l'icône) : la clé est limitée à l'ajout.
- **Plusieurs clés par membre**, nommées par poste : une seule clé, régénérable.
- **Révocation de la clé d'un autre membre**, y compris par un admin : chacun gère la sienne (R2).
- **Reprise d'un lien de l'historique Chat** (date et posteur d'origine) depuis l'extension : reste dans Radar.
- **Connexion de l'extension par email et mot de passe, ou par autorisation depuis l'écran de connexion de Radar** : écartées au profit de la clé à coller.
- **Mesure de la part des ajouts faits par l'extension** : pas de mesure dédiée ; l'usage se juge par les retours et le nombre de clés générées.
- **Installation imposée par l'administrateur Workspace** : pas au lancement ; possible plus tard à partir de la même publication.

## Impacts transverses

- **Traduction / langues** : nouveaux libellés en français uniquement (R11) — bulle, entrée du clic droit, messages de clé invalide, page « Extension navigateur » ; dates de création et de dernière utilisation à l'heure de Paris.
- **Droits d'accès** : nouveau moyen d'agir au nom d'un membre, limité au parcours d'ajout ; chaque membre ne voit et ne gère que sa clé. Pas de nouveau rôle.
- **Cloisonnement des données** : non — tout est partagé (R8) ; la clé est personnelle et n'est visible que de son propriétaire.
- **Apparence / déclinaisons** : la bulle reprend l'identité visuelle de Radar (noir et blanc, clair et sombre).
- **Exposition à des tiers** : oui — Radar accepte pour la première fois des ajouts venus d'un autre endroit que son interface, l'extension ; l'extension est publiée sur le Chrome Web Store (non répertoriée) et soumise à la revue de Google à chaque version.
- **Emails / notifications** : non — l'annonce Google Chat d'un nouveau lien est inchangée ; aucune notification à la génération ou à la révocation d'une clé.
- **Données existantes** : aucune reprise — aucun membre n'a de clé à la livraison.
- **Comportement par défaut** : rien ne change pour un membre qui n'installe pas l'extension ; l'ajout dans Radar reste identique.

## Questions ouvertes

- **Rattachement au backlog** : la fonctionnalité ne correspond à aucune ligne de `docs/product-backlog.md` ni à aucune capacité (elle relève du domaine D1 Contribution et du parcours P1). Options : (a) ajouter une capacité et une ligne V2 via `/forge:product-backlog` avant `/forge:feature-plan`, (b) au `/forge:sync` de clôture. Recommandé : (a), pour que la story soit rattachée et cochée à sa livraison. → tranché : (b), capacité C1.9 et ligne `extension-navigateur` ajoutées au backlog au `/forge:sync`.
- **Compte développeur du Chrome Web Store** : la publication non répertoriée suppose un compte développeur Google (frais uniques) et un responsable de la publication. Options : (a) compte personnel du porteur du projet, (b) compte rattaché à l'organisation. Pas de recommandation : dépend de la politique de l'organisation.
- **Confidentialité exigée par le Web Store** : la publication demande une déclaration d'usage des données et une page de politique de confidentialité. Options : (a) une page publique minimale décrivant ce que l'extension lit (l'adresse et le titre de la page en cours) et où elle l'envoie, (b) une page servie par Radar. Recommandé : (a), Radar n'étant pas accessible sans connexion (R1), la page ne peut pas y vivre. → tranché : (a), page statique publique `/confidentialite-extension.html`, servie hors de l'application ; l'extension ne lit que l'adresse de la page.
- **Ouverture de la bulle depuis le clic droit** : les navigateurs limitent la possibilité d'ouvrir la bulle de l'extension depuis le menu du clic droit. Options : (a) ouvrir la bulle quand c'est possible, sinon une petite fenêtre au même contenu, (b) toujours une petite fenêtre pour le clic droit. Recommandé : (a), à confirmer au plan selon ce que Chrome permet. → tranché : (a), bulle quand le navigateur l'accepte, sinon petite fenêtre (plan).

---

## Annexe — Pistes pour le plan

- Aujourd'hui, l'authentification repose uniquement sur la session (`form_login`, `config/packages/security.yaml`), sans API ni jeton : la clé implique un mécanisme d'authentification sans session dédié (piste : `access_token` du Security bundle avec un `AccessTokenHandler`, sur un firewall `stateless` séparé, limité à un préfixe de routes d'ajout).
- Clé stockée **hachée** côté serveur (jamais en clair, règle 8), avec date de création et de dernière utilisation ; vérification en temps constant. Une seule clé par `User` (relation un-à-un ou champs sur `User`, à trancher).
- Portée limitée à l'ajout (règle 10) : un rôle ou attribut dédié porté par l'utilisateur authentifié par clé, distinct de `ROLE_USER`, pour que les routes existantes la refusent par construction via `access_control`.
- Parité du formulaire (règle 4) : réutiliser `LinkManager::add()`, `CategoryResolver` et la détection de doublon de `LinkForm` (`getDuplicate()`, `completeDuplicate()`) plutôt que de dupliquer les règles ; la bulle ne peut pas embarquer le Live Component (origine `chrome-extension://`), d'où des points d'entrée JSON.
- CORS : n'autoriser que l'origine de l'extension publiée (identifiant fixe du Web Store), en plus de l'ID de développement.
- Limitation de débit sur les points d'entrée de la clé (le bundle `rate-limiter` est déjà en place) pour freiner l'essai de clés.
- Extension Manifest V3 : `action` (bulle), `contextMenus`, `activeTab` plutôt que `tabs` / `<all_urls>` pour réduire les permissions demandées (et faciliter la revue Google) ; clé conservée dans `chrome.storage.local`. `chrome.action.openPopup()` pour le clic droit, avec une fenêtre de repli (question ouverte).
- Adresse de l'instance Radar dans l'extension : production en dur (`https://radar.mustiere.fr`), surcharge possible en développement (`https://radar.wip`) — à trancher au plan.
- Emplacement du code de l'extension dans le dépôt (dossier dédié hors `src/`), outillage de build et de paquet pour le Web Store, tests E2E (Playwright sait charger une extension non empaquetée dans Chromium).

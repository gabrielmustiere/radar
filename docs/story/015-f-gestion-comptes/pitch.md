# Gérer les comptes et les équipes depuis Radar, et mettre en avant les catégories de son équipe

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/015-f-gestion-comptes/`
> **Amont** : aucun

Un admin crée les comptes depuis Radar (email, équipe, mot de passe provisoire), les modifie, les désactive et les passe admin, sans ligne de commande. Chaque membre appartient à une équipe : Développement, Produit, QA ou Management. Une catégorie prend l'équipe de la personne qui la crée ; un admin peut la corriger. Dans la sidebar, un membre voit d'abord les catégories de son équipe, puis les autres.

## Contexte

Aujourd'hui, un compte ne naît que par la commande `app:user:create`, lancée sur le serveur. Il n'existe ni liste des comptes, ni moyen d'en couper un (départ d'un collègue), ni notion d'équipe. Les admins sont une liste d'emails gérée à part, sans lien avec les comptes.

La vision vise une cinquantaine de personnes de métiers différents. Un producteur ou un QA ouvre Radar sur une sidebar pensée pour les développeurs : les catégories qui le concernent sont noyées dans les autres. Rattacher chaque catégorie à une équipe permet à chacun de trouver d'abord ce qui le concerne, sans rien cacher du reste.

## Alignement vision

- **Problème adressé** : retrouver par besoin. Les besoins d'un producteur ou d'un QA ne sont pas ceux d'un développeur ; les mettre en tête de sidebar raccourcit le chemin.
- **Audience servie** : les utilisateurs secondaires (producteurs, QA, managers), dont la vision attend au moins 3 contributeurs par mois à 1 an ; les admins pour la gestion.
- **Principes tendus** :
  - **R2 — Pas de curateur** : changer l'équipe d'une catégorie est réservé aux admins. Créer, renommer et classer restent ouverts à tous. C'est une deuxième exception à R2, après les sources (R13).
  - **R13 — Admins des sources** : les admins gèrent désormais aussi les comptes et l'équipe des catégories. La liste d'emails admin disparaît au profit d'un statut admin porté par le compte ; l'admin permanent par configuration reste. Édition ciblée de R13 à faire.
  - **R8 — Tout est partagé** : respecté. Toutes les catégories restent visibles par tous ; seul leur ordre change.
  - **R9 — Mesure agrégée** : respecté. L'équipe n'alimente aucun compteur ni aucune vue « qui a contribué combien ».
- **Hypothèse testée** : la mise en avant par équipe aide les non-développeurs à contribuer et à revenir (seuil à 1 an : 3 contributeurs non-développeurs par mois).
- **Impact North Star** : indirect, via l'usage des utilisateurs secondaires.

## Utilisateurs concernés

- **Admin** : crée, modifie, désactive et réactive les comptes ; change l'équipe d'une catégorie.
- **Membre connecté** : voit en tête de sidebar les catégories de son équipe ; ses nouvelles catégories prennent son équipe.
- **Membre désactivé** : ne peut plus se connecter ni ajouter par l'extension ; ses liens restent, à son nom.
- **Exploitant** : désigne toujours l'admin permanent par configuration.

## User Stories

- En tant qu'**admin**, je veux créer un compte avec un email, une équipe et un mot de passe provisoire afin d'ouvrir Radar à un collègue sans passer par le serveur.
- En tant qu'**admin**, je veux voir la liste des comptes avec leur équipe, leur statut admin et leur état (actif ou désactivé) afin de savoir qui a accès à Radar.
- En tant qu'**admin**, je veux changer l'email, l'équipe ou le statut admin d'un compte afin de le tenir à jour.
- En tant qu'**admin**, je veux définir un nouveau mot de passe provisoire pour un compte afin de débloquer un collègue qui a oublié le sien.
- En tant qu'**admin**, je veux désactiver un compte puis, au besoin, le réactiver afin de couper l'accès d'un collègue parti sans perdre ses liens.
- En tant qu'**admin**, je veux voir toutes les catégories avec leur équipe et la changer afin de corriger un classement par équipe erroné ou d'en donner une aux catégories existantes.
- En tant que **membre**, je veux voir d'abord les catégories de mon équipe dans la sidebar afin de trouver vite ce qui concerne mon métier, sans perdre l'accès aux autres.
- En tant que **membre**, je veux savoir à quelle équipe appartient une catégorie afin de comprendre pourquoi elle est mise en avant ou non.
- En tant qu'**exploitant**, je veux que l'admin permanent ne puisse jamais être désactivé ni perdre son statut admin depuis l'interface afin que Radar ne se retrouve jamais sans admin.

## Règles métier

### Équipes

1. Les équipes sont fixes : Développement, Produit, QA, Management. On ne peut ni en ajouter ni en renommer depuis l'interface.
2. Un compte créé depuis le back office a obligatoirement une équipe. Un compte existant n'en a pas tant qu'un admin ne lui en attribue pas.
3. Un membre appartient à une seule équipe.

### Comptes

4. Seul un admin accède à la gestion des comptes et des catégories. Un membre qui n'est pas admin ne voit pas leurs entrées de menu.
5. Créer un compte demande un email (unique, insensible à la casse), une équipe et un mot de passe provisoire. L'admin transmet lui-même le mot de passe ; Radar n'envoie aucun email. Le membre n'est pas obligé d'en changer.
6. Un admin peut modifier l'email, l'équipe et le statut admin d'un compte, et lui définir un nouveau mot de passe provisoire.
7. Un compte désactivé ne peut plus se connecter ; une session déjà ouverte est coupée à sa requête suivante, et sa clé d'extension ne fonctionne plus. Ses liens et ses catégories restent, avec son nom.
8. Un compte réactivé retrouve son accès, son mot de passe et son équipe. Sa clé d'extension reste celle d'avant si elle n'a pas expiré.
9. Un admin ne peut ni se désactiver ni se retirer le statut admin lui-même.
10. L'admin permanent configuré est admin en toutes circonstances : l'interface ne permet ni de le désactiver ni de lui retirer le statut admin.
11. Un compte ne se supprime pas : on le désactive (ses liens portent son nom, R3).
12. Le compte système « Radar » n'apparaît pas dans la liste des comptes.

### Catégories

13. Une catégorie créée par un membre prend l'équipe de ce membre au moment de la création. Si le membre n'a pas d'équipe, la catégorie n'en a pas.
14. Changer plus tard l'équipe d'un membre ne change pas l'équipe des catégories qu'il a déjà créées.
15. Une catégorie a au plus une équipe. Un admin peut la changer ou la retirer.
16. Les catégories existantes avant cette feature n'ont pas d'équipe tant qu'un admin ne leur en attribue pas.

### Sidebar

17. Pour un membre qui a une équipe, la sidebar montre d'abord une section « Mon équipe » avec les catégories de son équipe, puis une section avec toutes les autres (autres équipes et sans équipe).
18. Pour un membre sans équipe, la sidebar est inchangée : une seule liste.
19. Chaque section est triée par ordre alphabétique (casse et accents ignorés, nombres dans l'ordre naturel).
20. Dans la sidebar, la section « Mon équipe » nomme l'équipe du membre dans son titre ; dans la section des autres catégories, chaque catégorie qui a une équipe porte un badge discret avec le nom de cette équipe.

## Critères d'acceptation

- [x] Un membre qui n'est pas admin n'accède ni à la gestion des comptes ni à la gestion des catégories, et ne voit pas leurs entrées de menu.
- [x] Un admin crée un compte avec un email, l'équipe « QA » et un mot de passe ; la personne se connecte avec ce mot de passe.
- [x] La création d'un compte avec un email déjà utilisé, quelle que soit sa casse, est refusée avec un message.
- [x] Un admin change l'équipe d'un compte ; le changement apparaît dans la liste des comptes.
- [x] Un admin définit un nouveau mot de passe ; l'ancien ne fonctionne plus, le nouveau oui.
- [x] Un admin passe un compte admin ; la personne accède à la page des sources à sa requête suivante.
- [x] Un admin désactive un compte : la personne ne peut plus se connecter, sa session ouverte est coupée, sa clé d'extension est refusée, ses liens restent visibles à son nom.
- [x] Un admin réactive le compte : la personne se connecte de nouveau avec son mot de passe.
- [x] Un admin ne peut ni se désactiver ni se retirer le statut admin ; l'admin permanent ne peut être ni désactivé ni privé du statut admin.
- [ ] Les emails de l'ancienne liste admin qui ont un compte sont admins après la mise en production.
- [x] Une catégorie créée par un membre de l'équipe « Produit » apparaît dans la section « Mon équipe » (titrée « Produit ») de la sidebar d'un autre membre de « Produit », et dans la section des autres catégories, avec le badge « Produit », pour un membre de « Développement ».
- [x] Un admin change l'équipe d'une catégorie ; elle change de section dans la sidebar des membres concernés.
- [x] Pour un membre sans équipe, la sidebar présente une seule liste triée.

## Hors scope

- **Changement de mot de passe par le membre lui-même**, y compris à la première connexion : écarté (décision user).
- **Invitation ou réinitialisation par email** : écarté au profit du mot de passe provisoire transmis par l'admin.
- **Suppression de compte** : un compte se désactive (règle 11).
- **Plusieurs équipes par membre ou par catégorie**, et équipes configurables.
- **Filtre « liens de mon équipe »** dans le fil ou la recherche : seule la sidebar change.
- **Suppression ou fusion de catégories par l'admin** : la fusion reste la feature `fusion-categories` du backlog, ouverte à tous.
- **Statistiques par équipe** : exclues (R9).
- **Liste d'admins sans compte** : un admin est désormais un compte ; on ne peut plus désigner à l'avance l'email de quelqu'un qui n'a pas de compte, sauf l'admin permanent par configuration.

## Impacts transverses

- **i18n / traduction** : interface et messages en français (R11).
- **Permissions** : le statut admin passe de la liste d'emails au compte. Les admins gagnent la gestion des comptes et l'équipe des catégories.
- **Emails / notifications** : aucun.
- **Exposition à des tiers** : aucune.
- **Migration de données** :
  - les emails de la liste admin qui correspondent à un compte deviennent des comptes admins ; les autres sont abandonnés (voir questions ouvertes) ;
  - les comptes et les catégories existants n'ont pas d'équipe.
- **Exploitation** : la commande `app:user:create` reste disponible pour créer le premier compte d'une installation vide.
- **Comportement par défaut** : tant qu'aucune équipe n'est attribuée, la sidebar est la même qu'aujourd'hui.

## Questions ouvertes

- **Badge d'équipe sur les cartes ?** La règle 20 place le badge dans la sidebar seulement. Options : (a) sidebar seulement, (b) aussi sur les catégories affichées sur chaque carte. Recommandé : (a), les cartes portent déjà type de source, posteur, date et catégories. → tranché : (a).
- **Emails admin sans compte** : à la migration, un email de la liste admin qui n'a pas de compte perd son statut. Options : (a) abandonné, (b) migration bloquée tant qu'il reste de tels emails, l'exploitant crée les comptes avant. Recommandé : (a), avec la liste des emails abandonnés affichée par la migration. → tranché : (a).
- **Ordre des sections** : « Mon équipe » toujours en tête (règle 17), même vide ? Recommandé : la section n'apparaît pas quand l'équipe n'a aucune catégorie portant un lien. → tranché : masquée.
- **Compte de l'admin permanent** : s'il n'a pas encore de compte, il ne peut pas se connecter. L'exploitant le crée par `app:user:create`, comme aujourd'hui.

## Annexe — Pistes pour le plan

Pistes à confirmer, non contractuelles.

- **Équipe** : backed string enum `src/Enum/Type/Team.php`, colonne nullable sur `User` et sur `Category`.
- **Admin** : booléen `admin` sur `User` ; `AdminManager::isAdmin()` lit le compte plus l'admin permanent ; `AdminVoter` inchangé dans son principe (relecture à chaque décision). Migration de données depuis `admin_email`, puis suppression de l'entité `AdminEmail`, du formulaire et de la page `/administrateurs`.
- **Désactivation** : booléen ou date `disabledAt` sur `User` ; un `UserChecker` refuse la connexion (`checkPreAuth`) et coupe la session (rafraîchissement de l'utilisateur) ; même contrôle sur le pare-feu de l'API de l'extension.
- **Création de catégorie** : `CategoryManager` reçoit le créateur pour recopier son équipe (formulaire d'ajout, extension, alerte de doublon).
- **Sidebar** : `CategoryNav` reçoit l'utilisateur courant et sépare les catégories en deux sections, avec le tri `Collator` existant.
- **Back office** : routes sous `/admin/comptes` et `/admin/categories`, protégées par `AdminVoter::ADMIN` ; formulaires dédiés ; composants UI existants (`Table`, `Badge`, `Toggle`).

# Plan technique — Gérer les comptes et les équipes depuis Radar, et mettre en avant les catégories de son équipe

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/015-f-gestion-comptes/`
> **Amont** : `pitch.md`

## Approche retenue

L'équipe est un **backed string enum `Team`** (`src/Enum/Type/Team.php`), stocké en colonne nullable sur `User` et sur `Category`. Le statut admin et l'état actif deviennent deux **booléens sur `User`** (`admin`, `active`). La liste `AdminEmail` disparaît : une migration de données passe admins les comptes dont l'email y figure, écrit dans la sortie de migration les emails abandonnés, puis supprime la table. `AdminManager::isAdmin()` lit désormais le compte (`$user->isAdmin()`) et l'admin permanent configuré. `AdminVoter` garde son principe : il relit à chaque décision, et l'utilisateur en session est rechargé depuis la base à chaque requête par le `ContextListener`, donc un statut admin accordé ou retiré s'applique à la requête suivante.

La **désactivation** s'appuie sur deux mécanismes natifs de Symfony Security :
- un **`UserChecker`** (`checkPreAuth`) branché sur les pare-feux `main` et `api` : la connexion par formulaire et la clé d'extension d'un compte inactif sont refusées ;
- **`EquatableInterface`** sur `User` : `isEqualTo()` renvoie `false` quand le compte rechargé est inactif, ce qui fait tomber la session ouverte à la requête suivante. La règle d'accès renvoie alors vers la connexion.
- **`ExtensionKeyManager::authenticate()`** refuse aussi un compte inactif, avant d'enregistrer l'usage de la clé : le `UserChecker` passe après la résolution du badge, et chaque appel refusé repousserait sinon l'expiration de la clé.

Le **back office** est fait de deux contrôleurs classiques (formulaires Symfony, redirection après POST), protégés par `#[IsGranted(AdminVoter::ADMIN)]`. On y accède par la section « Administration » du menu utilisateur, réservée aux admins : la sidebar ne porte que la lecture des liens. `AdminUserController` couvre la liste, la création, la modification, le mot de passe (traité par la même action que la modification) et l'activation. `AdminCategoryController` couvre la liste des catégories et leur équipe. Toutes les règles (unicité de l'email, verrous sur soi-même et sur l'admin permanent, hachage) vivent dans `UserManager`, qui lève des exceptions métier que le contrôleur transforme en erreurs de formulaire ou en flash.

L'**équipe d'une catégorie** est recopiée à sa création : `CategoryResolver::resolve()` reçoit le créateur. Ses trois appelants (`CategoryPickerTrait` pour les formulaires de lien et de source, `ExtensionCategoryProcessor` pour l'extension) lui passent l'utilisateur courant. Il n'y a pas de relation vers le créateur : seule l'équipe est conservée (règles 13 et 14).

La **sidebar** (`CategoryNav`) lit l'équipe de l'utilisateur courant. Elle sépare les catégories en deux sections triées par le `Collator` (introduit avec cette story), et masque la section « Mon équipe » quand elle est vide. Le titre « Mon équipe » nomme l'équipe ; le badge d'équipe ne s'affiche que dans les autres catégories. `LinkRepository::countByCategory()` renvoie en plus l'équipe de chaque catégorie, pour le badge.

### Mécanismes mobilisés

- **Doctrine `enumType`** : `#[ORM\Column(enumType: Team::class, nullable: true)]` sur `User::$team` et `Category::$team`.
- **`UserCheckerInterface`** déclaré par `user_checker: App\Security\UserChecker` sur `main` et `api`. Il lève une `CustomUserMessageAccountStatusException('Ce compte est désactivé.')`, affichée sur la page de connexion. Côté API, le `UserCheckerListener` s'applique aussi au passeport de l'`access_token`, ce qui donne un 401.
- **`EquatableInterface::isEqualTo()`** : elle remplace la comparaison par défaut du `ContextListener`, et doit donc la reprendre. Elle vérifie que l'autre utilisateur est bien un `User`, qu'il est actif, qu'il a le même identifiant et le même mot de passe. Le mot de passe est comparé tel quel ou par son empreinte `crc32c`, celle que `__serialize()` met en session. Les rôles ne sont pas comparés : ils valent toujours `ROLE_USER`, puisque le statut admin passe par le voter. Un changement d'email ou de mot de passe par un admin déconnecte donc aussi le membre, comme aujourd'hui.
- **`UserPasswordHasherInterface`** dans `UserManager` pour le mot de passe provisoire, à la création comme à la redéfinition.
- **Formulaires Symfony** avec un modèle dédié (`src/Form/Model/`), comme `SourceSubmission`. `EnumType` pour l'équipe, `CheckboxType` pour le statut admin, `TextType` (affiché en clair, sans autocomplétion) pour le mot de passe provisoire que l'admin doit pouvoir lire pour le transmettre. Contraintes : `NotBlank`, `Email` et `Length(max: 180)` sur l'email ; `NotBlank` et `Length(min: 8, max: 4096)` sur le mot de passe ; `NotNull` sur l'équipe à la création (règle 2).
- **`#[IsCsrfTokenValid]`** sur les POST d'activation, de désactivation et de changement d'équipe d'une catégorie, comme `AdminController::remove()` aujourd'hui.
- **`\Collator('fr_FR')`** avec `NUMERIC_COLLATION`, appliqué à chaque section de `CategoryNav` (le tri SQL de `countByCategory()` est retiré).
- **Migration** générée par `symfony console make:migration`, puis complétée à la main de la reprise de données (voir §Modèle de données).

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| `ROLE_ADMIN` dans `User::$roles` | Changer les rôles fait tomber la session via la comparaison du `ContextListener`, et `is_granted('RADAR_ADMIN')` est déjà utilisé partout. Un booléen lu par le voter garde l'existant. |
| Écouteur `kernel.request` qui appelle `Security::logout()` pour un compte inactif | Code maison pour ce que `EquatableInterface` fait nativement au rechargement de la session. |
| Relation `Category → User` (créateur) | Le pitch ne conserve que l'équipe, figée à la création (règle 14). Une relation n'apporterait rien de plus. |
| Composant Live pour l'équipe d'une catégorie | Un petit formulaire POST par ligne suffit pour une page d'administration peu fréquentée. |
| `disabledAt` (date) plutôt qu'un booléen | Le pitch ne demande pas la date de désactivation. |

## Modèle de données

### Nouvel enum `Team`

`src/Enum/Type/Team.php` : `Development = 'developpement'`, `Product = 'produit'`, `Qa = 'qa'`, `Management = 'management'`, avec `label()` qui renvoie `Développement`, `Produit`, `QA` et `Management`.

### Modification de `User`

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `team` | `string` (`enumType: Team`) | oui | — |
| `admin` | `boolean` | non | défaut `false` |
| `active` | `boolean` | non | défaut `true` |

Méthodes : `getTeam()` / `setTeam(?Team)`, `isAdmin()` / `setAdmin(bool)`, `isActive()` / `activate()` / `deactivate()`, `isEqualTo()` (voir §Mécanismes mobilisés). Aucune règle de décision dans l'entité.

### Modification de `Category`

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `team` | `string` (`enumType: Team`) | oui | — |

Le constructeur prend un `?Team $team = null` en dernier argument (les appels existants restent valides), avec `getTeam()` / `setTeam(?Team)`.

### Suppression de `AdminEmail`

Il faut supprimer l'entité, son dépôt, son formulaire et l'exception `AlreadyAdminException`.

### Migration

Une seule migration, générée par `make:migration` après les changements d'entités, puis complétée à la main dans `up()` dans cet ordre :
1. ajout des colonnes `user.team`, `user.admin` (défaut 0), `user.active` (défaut 1) et `category.team` ;
2. `UPDATE "user" SET admin = 1 WHERE LOWER(email) IN (SELECT email FROM admin_email)` ;
3. `$this->write()` de chaque email de `admin_email` sans compte correspondant (emails abandonnés, décision du pitch) ;
4. `DROP TABLE admin_email`.

Le `down()` recrée `admin_email` et y remet les emails des comptes admins, puis retire les colonnes.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Enum/Type/Team.php` | Les quatre équipes et leur libellé. |
| `migrations/VersionYYYYMMDDHHMMSS.php` | Colonnes, reprise des admins, suppression de `admin_email` (générée puis complétée). |
| `src/Security/UserChecker.php` | Refuse la connexion et la clé d'extension d'un compte inactif. |
| `src/Exception/ProtectedAccountException.php` | Levée quand un admin tente de se désactiver, de se retirer le statut admin, ou de toucher au statut ou à l'état de l'admin permanent. Le message dit pourquoi. |
| `src/Form/Model/UserCreation.php` | Email, équipe, mot de passe provisoire, statut admin, avec leurs contraintes. |
| `src/Form/Model/UserEdition.php` | Email, équipe (nullable : un compte existant peut ne pas en avoir), statut admin. |
| `src/Form/Model/UserPassword.php` | Nouveau mot de passe provisoire. |
| `src/Form/UserCreationType.php`, `src/Form/UserEditionType.php`, `src/Form/UserPasswordType.php` | Formulaires du back office. La case admin est désactivée pour soi-même et pour l'admin permanent (option `admin_locked`). |
| `src/Controller/AdminUserController.php` | `GET /admin/comptes` (liste) ; `GET\|POST /admin/comptes/nouveau` ; `GET\|POST /admin/comptes/{id}` (modification et nouveau mot de passe, deux formulaires distingués par leur nom) ; `POST /admin/comptes/{id}/desactiver` ; `POST /admin/comptes/{id}/reactiver`. 404 sur le compte système. |
| `src/Controller/AdminCategoryController.php` | `GET /admin/categories` (liste avec équipe) ; `POST /admin/categories/{id}/equipe` (`team` vide = aucune, équipe inconnue = 400). |
| `templates/admin_user/index.html.twig` | Liste des comptes en lignes (adaptée au mobile) : email, équipe, badge admin (« admin permanent » pour l'admin configuré), badge désactivé, boutons Désactiver/Réactiver et Modifier ; compteur en en-tête. |
| `templates/admin_user/new.html.twig`, `templates/admin_user/edit.html.twig` | Formulaires de création ; de modification et de mot de passe, en deux sections. |
| `templates/admin_category/index.html.twig` | Liste des catégories en lignes : nom (lien vers la catégorie), nombre de liens, sélecteur d'équipe et bouton Enregistrer par ligne ; compteur en en-tête. |
| `tests/Service/UserManagerTest.php` | Unit : création (email en minuscules, hachage, équipe), unicité insensible à la casse à la création et à la modification, verrous de `ProtectedAccountException`. |
| `tests/Security/UserCheckerTest.php` | Unit : compte inactif refusé, actif accepté. |
| `tests/Entity/UserTest.php` | Unit : `isEqualTo()` (même compte, compte désactivé, mot de passe changé, empreinte `crc32c` en session, email changé). |
| `tests/Controller/AdminUserWebTestCase.php` | Base des tests du back office des comptes (création de comptes de test, connexion par formulaire). |
| `tests/Controller/AdminUserControllerTest.php`, `tests/Controller/AdminUserStatusTest.php` | Functional : voir §Stratégie de test (création, modification et mot de passe ; statut admin, activation et verrous). |
| `tests/Controller/CategoryNavTest.php` | Functional : sections de la sidebar (voir §Stratégie de test). |
| `tests/Controller/AdminCategoryControllerTest.php` | Functional : voir §Stratégie de test. |
| `tests/E2E/AdminUserTest.php` | E2E : un admin crée un compte QA ; la personne se connecte ; l'admin la désactive ; elle est renvoyée vers la connexion avec le message. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Entity/User.php` | Champs `team`, `admin`, `active`, `EquatableInterface`. |
| `src/Entity/Category.php` | Champ `team`. |
| `src/Repository/UserRepository.php` | `findOneByEmailIgnoringCase(string $email): ?User` (`LOWER(u.email) = :email`). `findMembersOrderedByEmail()` existe déjà et sert la liste. |
| `src/Repository/LinkRepository.php` | `countByCategory()` renvoie aussi `team` (`?Team`). |
| `src/Service/UserManager.php` | `create(email, plainPassword, ?Team, bool admin)` avec email en minuscules et unicité insensible à la casse ; `update(User, UserEdition, User $actor)` ; `changePassword(User, string)` ; `deactivate(User, User $actor)` et `activate(User)`. Les verrous lèvent `ProtectedAccountException`. |
| `src/Service/AdminManager.php` | `isAdmin(User)` = `$user->isAdmin()` ou admin permanent ; `isPermanent(User)` devient public. Suppression de `add()`, de `remove()` et de la dépendance à `AdminEmailRepository`. |
| `src/Security/AdminVoter.php` | Appelle `isAdmin($user)` avec le `User`. |
| `src/Command/CreateUserCommand.php` | Appelle `create()` sans équipe et sans statut admin (signature étendue). |
| `src/Service/CategoryManager.php` | `changeTeam(Category, ?Team)`, appelé par `AdminCategoryController`. |
| `src/Service/ExtensionKeyManager.php` | `authenticate()` refuse un compte inactif avant d'enregistrer l'usage de la clé. |
| `src/Service/CategoryResolver.php` | `resolve(string $name, ?User $creator)` : une nouvelle catégorie prend `$creator?->getTeam()`. Une catégorie existante est renvoyée sans changement. |
| `src/Twig/Components/CategoryPickerTrait.php` | `createCategory()` reçoit `#[CurrentUser] User` (ou `Security`) et le passe à `resolve()`. |
| `src/State/ExtensionCategoryProcessor.php` | Passe l'utilisateur de la clé (`Security::getUser()`) à `resolve()`. |
| `src/Twig/Components/CategoryNav.php` | Injecte `Security` ; `getSections()` renvoie `mine` et `others` triées ; `mine` vide quand le membre n'a pas d'équipe. |
| `templates/components/CategoryNav.html.twig` | Section « Mon équipe · <équipe> » (`data-test="category-nav-mine"`) si non vide, puis « Autres catégories » (`category-nav-others`), ou « Catégories » seule ; badge d'équipe dans les autres catégories seulement (`data-test="category-nav-team"`, composant `Badge` variante `gray`) ; nom complet en infobulle. |
| `templates/base.html.twig` | La sidebar ne garde que « Tous les liens » et les catégories. Les entrées « Sources » (`nav-sources`), « Comptes » (`nav-users`) et « Catégories » (`nav-categories`) passent dans une section « Administration » du menu utilisateur (`user-menu-admin`), réservée aux admins ; « Administrateurs » (`nav-admins`) disparaît. L'en-tête du menu affiche l'équipe et le statut admin (`user-menu-profile`). |
| `templates/source/index.html.twig` | Actions d'une source (sélecteur « depuis », Relever, Retirer) alignées sur les styles du back office. |
| `templates/form/theme.html.twig`, `assets/styles/app.css` | Classe `.select` (chevron dessiné, posé à distance du bord) appliquée aux listes déroulantes du thème de formulaire ; aide sous les champs allégée. |
| `config/packages/security.yaml` | `user_checker: App\Security\UserChecker` sur `main` et `api`. |
| `fixtures/AppFixtures.php` | Équipes sur les membres (`admin@example.com` en Développement, `marie@example.com` en Produit, `karim@example.com` en QA) et sur quelques catégories ; compte système sans équipe. |
| `tests/Security/AdminVoterTest.php` | Adapté au statut porté par le compte. |
| `tests/Controller/SourceControllerTest.php`, `tests/E2E/SourceTest.php` | `nav-admins` et `/administrateurs` retirés ; entrées d'administration dans le menu utilisateur et absentes de la sidebar. |
| `tests/Controller/CategoryControllerTest.php` | Tri de la sidebar (casse, accents, nombres). |
| `tests/Service/ExtensionKeyManagerTest.php`, `tests/Api/ExtensionCategoryTest.php`, `tests/Twig/Components/LinkFormTest.php` | Clé d'un compte désactivé ; catégorie créée par l'extension ou le formulaire à l'équipe de son créateur. |
| `tests/Command/CreateUserCommandTest.php`, `tests/Service/CategoryResolverTest.php` | Adaptés aux signatures. |
| `docs/product-backlog.md` | Édition ciblée de R2 et R13, capacité C1.8 réécrite (au `/forge:sync`). |

### Fichiers à supprimer

`src/Entity/AdminEmail.php`, `src/Repository/AdminEmailRepository.php`, `src/Form/AdminEmailType.php`, `src/Exception/AlreadyAdminException.php`, `src/Controller/AdminController.php`, `templates/admin/index.html.twig`, `tests/Controller/AdminControllerTest.php` (ses cas utiles passent dans `AdminUserControllerTest`).

## Hors scope

- **Connexion insensible à la casse de l'email** : le fournisseur compare l'email tel quel. Les emails sont désormais enregistrés en minuscules, mais un compte existant à majuscules garde son comportement actuel.
- **Tri `Collator` des pages d'administration et du sélecteur de catégories** : seule la sidebar l'utilise. Les pages admin trient en SQL, comme aujourd'hui.
- **Révocation automatique de la clé d'extension à la désactivation** : la clé est seulement refusée tant que le compte est inactif (règle 8 du pitch).
- Le reste du hors scope du pitch.

## Impacts transverses

- **Cloisonnement des données** : non. Tout reste visible par tous (R8). Seul l'ordre de la sidebar dépend du membre.
- **Déclinaisons / thèmes** : les pages admin reprennent les composants existants (`Badge`, `Button` en variante `outline`) en listes plutôt qu'en `Table` (qui débordait à largeur mobile), et la charte noir et blanc, en clair et en sombre.
- **Traduction / i18n** : libellés en français en dur (R11).
- **API / exposition externe** : l'API de l'extension refuse un compte inactif (401). Les catégories créées par l'extension prennent l'équipe du propriétaire de la clé.
- **Droits d'accès** : le statut admin passe de `admin_email` à `user.admin`, et l'admin permanent est inchangé. Les nouvelles routes `/admin/*` sont réservées aux admins ; `/administrateurs` disparaît.
- **Emails / notifications** : aucun.
- **Migration de données** : reprise des admins et suppression de `admin_email`. Les emails abandonnés s'affichent dans la sortie de `doctrine:migrations:migrate` au déploiement.
- **Comportement par défaut** : sans équipe attribuée, la sidebar est celle d'aujourd'hui.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `UserManager` | unit | Email en minuscules ; doublon refusé quelle que soit la casse, à la création et à la modification (sauf le compte lui-même) ; mot de passe haché ; `ProtectedAccountException` : se désactiver, se retirer le statut admin, désactiver l'admin permanent ou lui retirer le statut. |
| `User::isEqualTo()` | unit | Vrai pour le même compte actif ; faux si inactif, si l'email ou le mot de passe a changé ; vrai avec l'empreinte `crc32c` de session. |
| `UserChecker` | unit | Exception avec le message pour un compte inactif ; rien pour un compte actif. |
| `AdminUserController` (`AdminUserControllerTest`, `AdminUserStatusTest`) | functional | Non-admin : 403 sur `/admin/*` et aucune entrée de menu ; création (connexion réussie avec le mot de passe, email en double refusé en 422, équipe obligatoire) ; modification de l'équipe visible dans la liste ; nouveau mot de passe (l'ancien refusé, le nouveau accepté) ; statut admin accordé puis `/sources` accessible ; désactivation (connexion refusée avec le message, session ouverte renvoyée vers `/login`, liens toujours affichés à son nom) ; réactivation ; verrous sur soi et sur l'admin permanent ; compte système absent de la liste ; CSRF exigé. |
| `ExtensionAuthTest` (existant, complété) | functional | Clé valide d'un compte désactivé refusée en 401. |
| `ExtensionKeyManager` (existant, complété) | unit | Clé d'un compte désactivé : aucun compte renvoyé, usage non enregistré. |
| `AdminCategoryController` | functional | Liste de toutes les catégories avec leur équipe ; changement et retrait d'équipe ; équipe inconnue refusée ; non-admin en 403. |
| `CategoryResolver` | unit | Nouvelle catégorie à l'équipe du créateur ; sans équipe si le créateur n'en a pas ; catégorie existante inchangée. |
| `CategoryNav` (`CategoryNavTest`, `CategoryControllerTest` pour le tri) | functional | Membre Produit : section « Mon équipe » avec les catégories Produit et badge, autres catégories ensuite ; membre Développement : catégorie Produit dans les autres ; membre sans équipe : une liste ; section masquée si l'équipe n'a aucune catégorie avec un lien ; tri dans chaque section. |
| Migration | manuelle | Sur une copie de `var/data.db` : admins repris, emails abandonnés affichés, `down()` puis `up()` sans perte. |
| Parcours admin | E2E | `tests/E2E/AdminUserTest.php`. |

**Hors scope tests** : rendu visuel du badge (vérifié à la main en clair et sombre).

## Étapes d'exécution

1. **Équipes, statut admin et désactivation** — enum `Team` ; champs sur `User` et `team` sur `Category` (une seule migration) ; migration avec reprise ; `AdminManager` et `AdminVoter` sur le compte ; suppression d'`AdminEmail` ; `UserChecker` et `isEqualTo()` ; `UserManager` étendu. Les tests unitaires et `AdminVoterTest` passent ; la page `/administrateurs` disparaît, et `SourceControllerTest` est adapté.
2. **Back office des comptes** — formulaires, `AdminUserController`, gabarits, entrées de menu ; tests fonctionnels et E2E.
3. **Équipe des catégories** — `CategoryResolver` et ses trois appelants ; `AdminCategoryController` ; tests.
4. **Sidebar par équipe** — `countByCategory()` avec l'équipe, sections et badge dans `CategoryNav` ; fixtures ; tests fonctionnels.

Chaque étape se termine par `make lint`, `make phpunit` et, aux étapes 2 et 4, `make playwright`.

## Critères de sortie

- [ ] Tous les critères d'acceptation du pitch sont couverts par un test.
- [x] `make lint` sans finding, `make phpunit` et `make playwright` verts.
- [x] `symfony console doctrine:schema:validate` vert.
- [ ] Migration jouée et annulée sur une copie de la base de production.
- [x] Pages admin vérifiées à la main en clair et en sombre, et à largeur mobile.

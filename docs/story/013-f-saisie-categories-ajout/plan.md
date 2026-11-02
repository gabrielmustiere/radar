# Plan technique — Créer et choisir jusqu'à trois catégories au fil de la saisie

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/013-f-saisie-categories-ajout/`
> **Amont** : `pitch.md`

## Approche retenue

La création d'une catégorie quitte l'envoi du formulaire pour devenir une **action Live** déclenchée par Entrée. Le composant `LinkForm` gagne un champ de saisie hors formulaire (la propriété Live `categoryQuery`, sans attribut `name`, donc jamais postée). Chaque frappe re-rend les pastilles filtrées. Entrée appelle `createCategory` : la catégorie est résolue ou créée et enregistrée tout de suite par `CategoryResolver`, ajoutée aux cases cochées dans `formValues` s'il reste de la place, puis le champ est vidé. Au re-rendu suivant, les choix de l'`EntityType` relisent la base, si bien que la nouvelle pastille apparaît sans JS maison. L'envoi reste le POST classique vers `LinkController`. Il ne porte plus que des catégories existantes : `newCategory` disparaît de `LinkType` et de `LinkSubmission`, et la règle de validation maison est remplacée par `Assert\Count(min: 1, max: 3)`.

Le comportement est factorisé dans un **trait Live** et un **partial Twig**, réutilisés par un nouveau composant `SourceForm`. Le formulaire des sources devient Live, sur le modèle exact de `LinkForm`, et `SourceController` garde le traitement de l'envoi. Le doublon change de place : l'alerte passe sous le champ URL, désormais dernier. Elle propose de compléter le lien existant avec les catégories cochées qu'il n'a pas : le choix se fait par la sélection du formulaire, sans cases dédiées dans l'alerte. Une seconde action Live, `completeDuplicate`, appelle `LinkManager::complete()`. Ce service applique le plafond (`CategoryLimitException` s'il serait dépassé, l'alerte est alors re-rendue à jour), enregistre, puis on redirige vers la carte avec un message flash, sans annonce Chat.

### Mécanismes mobilisés

- **`#[LiveAction]`** (Live Component, services injectés en arguments de l'action) : `LinkForm` est déjà Live et porte l'état des cases cochées (`formValues`). Créer côté serveur, puis re-rendre, garde une seule source de vérité pour la sélection et le plafond.
- **`#[LiveProp(writable: true)]` avec `debounce`** pour `categoryQuery` : le filtrage est rendu côté serveur (choix utilisateur). Les pastilles non concordantes sont **masquées** (`hidden`), jamais retirées du DOM, pour qu'une catégorie cochée puis filtrée parte toujours avec le formulaire.
- **`data-action="keydown.enter->live#action:prevent"`** sur le champ de saisie : `:prevent` bloque l'envoi natif du formulaire (règle 4 du pitch).
- **Trait PHP partagé** (`CategoryPickerTrait`) entre `LinkForm` et `SourceForm` : deux usages identiques. Le trait inclut `ComponentWithFormTrait` (sans quoi l'analyse statique ne voit pas `formValues`) et suppose le nom du champ `categories`.
- **Redirection depuis une action Live** (`return $this->redirectToRoute(...)`) : avec Turbo présent, Live déclenche une visite de premier niveau, qui sort de la frame de la modale. Le flash passe par la session, comme ailleurs.
- **`ValidatorInterface`** pour le nom saisi : contrainte `Length(min: 2, max: 60)` portée par la propriété `categoryQuery`, validée à l'action ; une saisie vide est ignorée sans message. Aucun contrôle manuel.
- **`CategoryResolver`** reste le point unique de création. Il met la majuscule initiale sur le premier caractère en multioctet (`mb_strtoupper(mb_substr(...))`) aux seules créations : la correspondance avec une catégorie existante, insensible à la casse, est testée avant, et la catégorie existante est renvoyée telle quelle (règle 8 du pitch).
- **`InteractsWithLiveComponents`** pour tester les actions, comme `tests/Twig/Components/PendingLinkCardTest.php`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Contrôleur Stimulus + endpoint JSON `/categories` | Les pastilles insérées par JS seraient écrasées par le re-rendu Live de `LinkForm`, et il faudrait un CSRF et un plafond dupliqués côté client. |
| Filtre des pastilles en Stimulus côté client | Plus réactif, mais écarté par l'utilisateur au profit d'un rendu entièrement serveur. |
| Composant enfant `CategoryPicker` qui émet vers son parent | La synchronisation enfant/parent des `formValues` coûte plus cher que le trait pour deux usages seulement. |
| Route POST `/liens/{id}/categories` pour compléter un doublon | L'alerte est désormais dans le `<form>` (sous l'URL) : il faudrait l'attribut HTML `form="…"`, une route et un contrôleur de plus. |
| Créer la catégorie à l'envoi (comportement actuel étendu à plusieurs noms) | Contredit le choix du pitch : création immédiate et visible par tous. |

## Modèle de données

Aucun impact sur le schéma, aucune migration : `link_category` est déjà en plusieurs-à-plusieurs, et l'unicité `UNIQ_CATEGORY_NAME` ainsi que la longueur de 60 caractères existent déjà.

`src/Entity/Link.php` gagne une méthode `addCategory(Category $category): void`, qui ajoute à la collection si la catégorie est absente. Le plafond reste une règle métier : il est appliqué dans `LinkManager`, pas dans l'entité.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Twig/Components/CategoryPickerTrait.php` | `categoryQuery` (LiveProp modifiable, contraintes de nom), `categoryNotice` (message après création), action `createCategory` (valide, résout ou crée, coche s'il reste de la place, sinon message), `getCheckedCategoryCount()`, `isCategoryVisible(name)`. |
| `templates/components/_category_picker.html.twig` | Champ de saisie (`data-test="category-query"`), pastilles filtrées et verrouillées au plafond, erreur de nom et message de création hors plafond, erreurs du champ `categories`. |
| `src/Twig/Components/SourceForm.php` | Composant Live du formulaire des sources (`CategoryPickerTrait`, qui inclut `ComponentWithFormTrait`), réservé aux admins par `#[IsGranted]`, envoi en POST classique vers `app_source_index`. |
| `templates/components/SourceForm.html.twig` | Champ adresse du flux, puis le partial des catégories, puis le bouton « Suivre ». |
| `src/Exception/CategoryLimitException.php` | Exception de domaine levée par `LinkManager::complete()` quand le lien dépasserait trois catégories. |
| `tests/Twig/Components/LinkFormTestCase.php` | Base des tests Live de `LinkForm` : composant connecté, catégories, pastilles, cases cochées. |
| `tests/Twig/Components/LinkFormTest.php` | Actions Live : création et sélection, réutilisation insensible à la casse, majuscule initiale, nom invalide, création au-delà de 3 non cochée, nom existant tapé au plafond, filtre. |
| `tests/Twig/Components/LinkFormDuplicateTest.php` | Complément de doublon : catégories proposées et ajout, place insuffisante, lien complet sans proposition, lien complété entre-temps re-rendu. |
| `tests/Twig/Components/SourceFormTest.php` | Création à l'Entrée dans le formulaire des sources, actions refusées à un membre non admin. |
| `tests/Service/LinkManagerCompleteTest.php` | `complete()` : ajout des seules absentes sans annonce, dépassement refusé sans rien modifier. |
| `tests/Service/CategoryResolverTest.php` | Majuscule initiale (accentuée, sigles conservés), réutilisation d'un nom existant sans le modifier, enregistrement immédiat. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Form/Model/LinkSubmission.php` | `MAX_CATEGORIES = 3` ; retirer `newCategory`, `newCategoryName()` et le callback ; `Assert\Count(min: 1, max: MAX_CATEGORIES)` avec des messages en français (« au moins une », « au plus trois »). |
| `src/Form/Model/SourceSubmission.php` | Message maximum « Une source porte au plus trois catégories. » |
| `src/Form/LinkType.php` | Ordre contexte → catégories → URL ; `autofocus` déplacé sur le contexte ; retrait de `newCategory` ; libellé « Catégories (une à trois) ». |
| `src/Form/SourceType.php` | Libellé « Catégories (une à trois) ». |
| `src/Service/CategoryResolver.php` | Majuscule initiale à la création ; enregistrement immédiat (persist + flush). |
| `src/Repository/CategoryRepository.php` | Méthodes `save(Category)`, sur le modèle de `LinkRepository::save()`, et `findByIdsOrderedByName()` (catégories cochées proposées sur un doublon). |
| `src/Entity/Link.php` | `addCategory(Category)`. |
| `src/Service/LinkManager.php` | Retrait de la résolution `newCategory` dans `create()` et de la dépendance `CategoryResolver` ; ajout de `complete(Link, list<Category>)` : ajoute les catégories absentes dans la limite de `MAX_CATEGORIES`, lève `CategoryLimitException` si le plafond serait dépassé, enregistre et n'annonce rien. |
| `src/Twig/Components/LinkForm.php` | `use CategoryPickerTrait` ; candidats au complément (catégories cochées absentes du lien existant), place restante ; action `completeDuplicate` → `LinkManager::complete()`, flash « Catégories ajoutées. », redirection `app_link_show`, ou re-rendu avec `duplicateNotice` si le lien a été complété entre-temps ; `getCategoryCount()` et `values()` retirés. |
| `templates/components/LinkForm.html.twig` | Champs dans le nouvel ordre ; le partial des catégories remplace le fieldset ; l'alerte de doublon passe sous l'URL. |
| `templates/link/_duplicate.html.twig` | Bloc de complément : catégories proposées et bouton `data-test="link-duplicate-complete"` si elles tiennent, sinon le nombre à décocher ; mention « votre contexte n'est pas repris » ; message de complément concurrent. Affichable sans proposition (lien complet). |
| `templates/link/new.html.twig` | Sous-titre aligné sur le nouvel ordre et le plafond de 3. |
| `templates/source/index.html.twig` | Le formulaire est remplacé par `<twig:SourceForm :form="form"/>`. |
| `tests/Controller/LinkWebTestCase.php` | Helper de création de catégorie (via `CategoryResolver`) pour remplacer les envois `newCategory`. |
| `tests/Controller/LinkControllerTest.php` | Plafond à 3 (3 acceptées, 4 refusées), aucune refusée, nouvel ordre des champs ; retrait des cas `newCategory` (déplacés dans `CategoryResolverTest` et `LinkFormTest`). |
| `tests/Controller/LinkDuplicateTest.php`, `FeedTest.php`, `CategoryControllerTest.php`, `LinkSearchTest.php` | Remplacer `newCategory` par le helper puis `categories: [id]` ; retrait de l'assertion « catégorie non créée sur doublon » ; catégorie sans lien absente de la navigation. |
| `tests/Controller/LinkSeedTest.php` | Reprise de Chat avec 3 catégories (helper `seed()` réordonné pour accepter des catégories). |
| `tests/Service/LinkManagerTest.php` | Retrait des cas `newCategory` (les cas `complete()` vivent dans `LinkManagerCompleteTest`). |
| `tests/Controller/SourceControllerTest.php` | Plafond à 3 et nouveau message. |
| `tests/E2E/AddLinkTest.php` | Ordre et focus, Entrée qui crée et sélectionne sans soumettre, verrou à 3 (remplace `testThirdCategoryIsLockedOnceTwoAreChecked`), complément sur doublon ; catégorie de test à nom stable, sélecteur `:has-text()`. |
| `tests/E2E/SourceTest.php` | Création d'une catégorie à l'Entrée dans le formulaire des sources (nom stable, sélecteur `:has-text()`). |

## Hors scope

- **Rendre `Category` porteuse de sa règle de nommage** (constructeur qui capitalise) : interdit par la convention projet (pas de logique métier dans l'entité) ; la règle reste dans `CategoryResolver`.
- **Requête dédiée pour la correspondance de nom** (`LOWER(name) = :name`) : la boucle actuelle de `CategoryResolver` sur quelques dizaines de catégories suffit.
- **Gestion de la collision concurrente sur `UNIQ_CATEGORY_NAME`** : risque accepté (voir Risques).
- **Reprise des noms de catégories existants** : choix du pitch.
- **Modale de l'ajout et contrôleur `link_modal`** : inchangés.

## Impacts transverses

- **Cloisonnement des données** : aucun, tout est partagé (R8).
- **Déclinaisons / thèmes** : aucune ; le partial suit les classes clair et sombre existantes des pastilles.
- **Traduction / i18n** : libellés et messages en dur en français, comme le reste du formulaire (convention actuelle, aucune clé `|trans` dans ces templates) ; messages de contraintes en français dans les attributs.
- **API / exposition externe** : aucune.
- **Droits d'accès** : les actions Live de `LinkForm` héritent de l'`access_control` global (`ROLE_USER`). `SourceForm` n'est rendu que sur `/sources`, protégé par `#[IsGranted(AdminVoter::ADMIN)]` ; ses actions passent par la route Live générique. Il faut donc ajouter `#[IsGranted(AdminVoter::ADMIN)]` sur la classe `SourceForm` pour qu'un membre ne puisse pas les appeler directement.
- **Emails / notifications** : aucune ; `complete()` ne passe pas par `LinkAnnouncer` (R5).
- **Migration de données** : aucune.
- **Comportement par défaut** : le nouveau formulaire remplace l'ancien pour tous ; la reprise de Chat (`HistoricalLinkType` hérite de `LinkType`) suit sans modification dédiée.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/CategoryResolver.php` | functional (BDD de test) | « tester une app » → « Tester une app » ; « éco-conception » → « Éco-conception » ; seul le premier caractère change (« iOS » → « IOS », « API REST » inchangé) ; nom existant dans une autre casse → catégorie existante renvoyée et nom inchangé ; catégorie relisible tout de suite (flush). |
| `src/Service/LinkManager.php::complete()` | functional | Ajoute les absentes ; ignore celles déjà présentes ; refuse au-delà de 3 sans rien modifier ; aucun message d'annonce dispatché. |
| `src/Form/Model/LinkSubmission.php` | functional (via `LinkControllerTest`) | 0 catégorie refusée, 3 acceptées, 4 refusées, messages en français. |
| `src/Twig/Components/LinkForm.php` + `CategoryPickerTrait` | Live (`InteractsWithLiveComponents`) | `createCategory` : création et cochage, réutilisation, nom de 1 ou 61 caractères refusé avec message, 4e création non cochée avec message, champ vidé ; filtre : pastille masquée mais case toujours postée ; `completeDuplicate` : catégories proposées, nombre à décocher si elles ne tiennent pas, redirection vers la carte, lien complet sans proposition, lien complété entre-temps re-rendu avec un message. |
| `src/Twig/Components/SourceForm.php` | Live | Création à l'Entrée, action refusée à un membre non admin (plafond à 3 vérifié par `SourceControllerTest`). |
| `src/Controller/LinkController.php` / `SourceController.php` | functional | Envoi classique inchangé avec 1 à 3 catégories ; la reprise de Chat accepte 3 catégories. |
| Formulaire d'ajout dans la modale | E2E | Ordre des champs et focus dans le contexte ; Entrée crée, coche et ne soumet pas ; verrou à 3 ; complément sur doublon jusqu'à la carte. |
| Formulaire des sources | E2E | Création d'une catégorie à l'Entrée, cochée (sans envoi : il exigerait un flux réel). |

**Hors scope tests** :

- Pas de test de la collision concurrente : risque accepté, non reproductible de façon déterministe.
- Pas de E2E sur le filtre : le masquage est vérifié en test Live, seul le comportement clavier (Entrée) requiert un navigateur.
- Pas de test du chronométrage « moins de 15 secondes » : il se valide à la main en recette (critère d'acceptation du pitch).

## Ordre d'exécution

1. [x] **Règles de domaine : plafond, création immédiate, complément**
   - Objectif : `MAX_CATEGORIES = 3` ; `LinkSubmission` sans `newCategory`, avec `Assert\Count` ; `CategoryResolver` avec majuscule initiale et enregistrement immédiat ; `Link::addCategory()` ; `LinkManager::complete()` ; `create()` sans résolution de nom ; message de `SourceSubmission`.
   - Fichiers : `LinkSubmission`, `SourceSubmission`, `CategoryResolver`, `CategoryRepository`, `Link`, `LinkManager`, `LinkType` (retrait de `newCategory` uniquement) ; `LinkWebTestCase` (helper) et migration des tests `newCategory` ; `CategoryResolverTest`, `LinkManagerTest`, `LinkControllerTest`, `SourceControllerTest`.
   - Vérification : `make phpunit` vert ; `make lint` vert.
   - Commitable seule : non (le formulaire n'a plus de moyen de créer une catégorie avant l'étape 2).

2. [x] **Saisie partagée dans le formulaire d'ajout**
   - Objectif : `CategoryPickerTrait` + `_category_picker.html.twig` branchés sur `LinkForm` ; `LinkType` réordonné, focus sur le contexte ; libellés et sous-titre.
   - Fichiers : `CategoryPickerTrait`, `_category_picker.html.twig`, `LinkForm.php`, `LinkForm.html.twig`, `LinkType`, `link/new.html.twig`, `LinkFormTest` (cas de création et de filtre).
   - Vérification : `make phpunit` ; essai manuel dans la modale (`make serve`) : Entrée crée et coche sans soumettre.
   - Commitable seule : oui (avec l'étape 1).

3. [x] **Complément sur doublon**
   - Objectif : alerte sous l'URL, catégories cochées proposées si elles tiennent, action `completeDuplicate`, redirection vers la carte avec flash.
   - Fichiers : `LinkForm.php`, `LinkForm.html.twig`, `link/_duplicate.html.twig`, `LinkFormTest` (cas de doublon), `LinkDuplicateTest` si l'affichage change.
   - Vérification : `make phpunit` ; essai manuel avec un lien existant à 1, 2 puis 3 catégories.
   - Commitable seule : oui.

4. [x] **Formulaire des sources en Live**
   - Objectif : `SourceForm` (trait + partial, `#[IsGranted]` admin) ; `source/index.html.twig` l'utilise ; `SourceType` change de libellé.
   - Fichiers : `SourceForm.php`, `SourceForm.html.twig`, `source/index.html.twig`, `SourceType`, `SourceFormTest`.
   - Vérification : `make phpunit` ; essai manuel sur `/sources`.
   - Commitable seule : oui.

5. [x] **E2E et QA finale**
   - Objectif : parcours clés en navigateur et suite complète verte.
   - Fichiers : `tests/E2E/AddLinkTest.php`, `tests/E2E/SourceTest.php`.
   - Vérification : `make lint`, `make phpunit`, `make playwright` verts.
   - Commitable seule : oui.

## Critères de sortie

- [x] Plus aucune occurrence de `newCategory` dans `src/`, `templates/` et `tests/`.
- [x] `LinkSubmission::MAX_CATEGORIES` vaut 3 et c'est la seule source du plafond (formulaire, sources, complément, verrou des pastilles).
- [x] Aucune migration générée : `symfony console doctrine:schema:validate` reste vert sans nouveau fichier dans `migrations/`.
- [x] Les actions Live de `SourceForm` sont refusées à un membre non admin.
- [x] `LinkManager::complete()` ne dispatche aucun message d'annonce.
- [x] `make phpunit` et `make playwright` verts, sans nouvelle régression.
- [x] `make lint` vert (Mago fmt, lint, analyze), zéro finding.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Deux créations simultanées du même nom → violation de `UNIQ_CATEGORY_NAME`, erreur 500 et EntityManager fermé | faible | Risque accepté (choix utilisateur) : une nouvelle tentative réutilise la catégorie existante ; jamais de doublon en base. |
| Une pastille cochée puis masquée par le filtre n'est plus postée | moyenne | Masquage par `hidden`, jamais `{% if %}` autour de la case ; testé en Live (case postée malgré le filtre). |
| Le re-rendu Live pendant la frappe du filtre relance `getDuplicate()` (requête) à chaque frappe | moyenne | Requête légère sur une colonne indexée ; `debounce` sur `categoryQuery` ; à mesurer en essai manuel, sinon mémoïser le doublon dans le rendu. |
| Les actions Live de `SourceForm` sont appelables par un membre via la route Live générique | moyenne | `#[IsGranted(AdminVoter::ADMIN)]` sur le composant, couvert par `SourceFormTest`. |
| Entrée soumet malgré tout le formulaire dans un navigateur (champ sans `name` mais dans le `<form>`) | faible | Modificateur `:prevent` sur l'action ; couvert en E2E. |
| Remplacer `newCategory` dans les tests existants est volumineux (7 fichiers) | élevée | Un seul helper dans `LinkWebTestCase` ; migration faite à l'étape 1, avant toute UI. |
| Le partial dépend du nom du champ `categories` dans les deux formulaires | faible | Nom identique dans `LinkType` et `SourceType` ; documenté dans le trait. |

## Questions ouvertes

- **Exception de plafond dans `complete()`** : (a) exception de domaine dédiée (`CategoryLimitExceededException`), (b) `\LogicException`, puisque le composant n'envoie jamais plus que la place restante. Recommandé : (b), le plafond est déjà garanti par l'UI et le composant ; un dépassement ne peut venir que d'un appel forgé, et on ne gère pas un scénario impossible côté UX. → tranché : (a), `CategoryLimitException` — la review a montré le cas possible (complément concurrent, [ROBUSTESSE]), attrapé par `completeDuplicate`.
- **Mise à jour de la vision et du backlog** (principe 5, R4, C1.1, C1.6 passent à trois) → tranché : au `/sync` de clôture.

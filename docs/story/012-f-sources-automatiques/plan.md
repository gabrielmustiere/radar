# Plan technique — Publier automatiquement les articles des sources suivies

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/012-f-sources-automatiques/`
> **Amont** : `pitch.md`

## Approche retenue

Trois blocs indépendants, livrés dans cet ordre : les **admins**, les **sources**, la **relève**.

Les admins sont une table d'emails (`admin_email`) plus un admin permanent lu dans `RADAR_ADMIN_EMAIL`. Un voter `RADAR_ADMIN` les consulte à chaque décision, si bien qu'un ajout ou un retrait prend effet immédiatement. Les pages `/sources` et `/administrateurs` et leurs entrées de menu passent par ce voter.

Une source (`Source`) est une adresse de flux unique, un nom lu dans le flux, une ou deux catégories, un état de relève et un retrait logique (`removedAt`). Le retrait est logique pour que les cartes gardent le nom de leur source. Les articles déjà vus sont mémorisés dans `SourceItem` (clé unique par source). À l'enregistrement, le flux est téléchargé avec le client sécurisé des aperçus, analysé par un parseur maison RSS 2.0 et Atom 1.0 sur `Dom\XMLDocument`, puis tous ses articles présents sont marqués vus. Ré-enregistrer l'adresse d'une source retirée la réactive.

La relève s'appuie sur Symfony Scheduler. Toutes les heures, un message `FetchSources` émet un `FetchSource(id)` par source active sur le transport `async` existant. Le handler lit le flux, enregistre l'erreur s'il y en a une (sans lever d'exception, donc sans retry), puis publie chaque article non vu, du plus ancien au plus récent. Un article déjà dans Radar est écarté par `LinkManager::findDuplicate()` avant publication, et seulement marqué vu (R6). La publication passe par `LinkManager`, qui gère la normalisation et `FetchLinkPreview`. Elle utilise les catégories de la source et le compte « radar », que le handler fournit au publisher. L'aperçu et l'annonce Google Chat suivent ensuite le circuit existant ; la garde `announcedAt` assure toujours R5. La carte et l'annonce affichent le nom de la source ; la carte prend l'orange des flux RSS, prioritaire sur la teinte de la plateforme.

Un admin peut aussi lancer la relève d'une source à la demande (bouton « Relever » de `/sources`), avec une antériorité (`FetchWindow` : 1 h, 12 h, 36 h, 72 h). Les articles parus dans la fenêtre, d'après `pubDate`, `published` ou `updated`, sont repris même déjà vus ; un article sans date n'est jamais repris. Le doublon R6 empêche toujours une double publication.

La normalisation des adresses (R6, pour tous les liens) conserve désormais l'ancre, qui désigne un contenu distinct (une actualité du flux PHP.net), et ne retire que la directive de fragment de texte `:~:text=`.

### Mécanismes mobilisés

- **Voter (`RADAR_ADMIN`)** : il porte la règle « liste d'emails + admin permanent » et répond à chaque requête. Un rôle calculé à la connexion laisserait un admin retiré en place jusqu'à sa déconnexion. Le voter ne mémoïse pas : en mode worker FrankenPHP (cible de production), un cache de service survivrait d'une requête à l'autre.
- **`#[IsGranted('RADAR_ADMIN')]`** sur les deux contrôleurs, et `is_granted('RADAR_ADMIN')` dans la sidebar. L'`access_control` global (`ROLE_USER`) reste inchangé.
- **Symfony Scheduler (`#[AsSchedule]`, `RecurringMessage::every('1 hour')`, `stateful`, `processOnlyLastMissedRun`)** : c'est la brique native de planification sur Messenger, et le projet n'a ni cron ni autre planificateur. `stateful` sur `cache.app` rattrape une échéance manquée lors d'un redémarrage du worker au lieu de repartir de zéro, une seule fois quel que soit le nombre d'heures perdues. Le planning vit dans `src/Schedule.php`, emplacement de la recette Flex.
- **Messenger, un message par source sur `async`** : une source lente ou en erreur ne retarde pas les autres. Le worker scheduler se limite à dispatcher.
- **`app.preview_http_client`** (`NoPrivateNetworkHttpClient` + limiteur par hôte) : l'adresse du flux est saisie par un humain, avec le même risque SSRF que les aperçus. En test, `MockHttpClient` + `PreviewMockResponseFactory` sont étendus aux flux.
- **`Dom\XMLDocument`** (PHP 8.4+) : le même choix que `HtmlParser` sur `Dom\HTMLDocument`. Il ne charge pas d'entités externes par défaut, ce qui protège contre XXE.
- **Find-or-create du compte « radar »**, sur le modèle de `CategoryResolver` : le compte naît à la première publication. Une migration ne conviendrait pas, car `make db-reset` purge les utilisateurs.
- **`LinkManager`** : c'est le point d'entrée unique de création d'un lien. Il gagne une méthode de publication depuis une source.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| `laminas/laminas-feed` ou `simplepie/simplepie` | Deux dépendances de plus (laminas-stdlib, laminas-escaper), ou une API ancienne avec un HTTP et un cache internes à neutraliser, pour lire trois champs ; le projet a déjà fait le choix du parseur maison (`HtmlParser`, `embed/embed` écartée en 003). |
| `ROLE_ADMIN` calculé à la connexion | Un admin retiré le resterait jusqu'à sa déconnexion. |
| Rôles dans la colonne `user.roles` | Ne permet pas d'ajouter l'email d'une personne sans compte (règle 4 du pitch). |
| Rattacher les articles vus à une date de dernière publication | Fragile pour un flux réordonné ou modifié, et ne tient pas la règle 9 (jamais republié). |
| Supprimer la ligne `Source` au retrait | Les cartes perdraient le nom de leur source. |
| Un seul handler qui relève toutes les sources à la suite | Séquentiel : un flux lent (jusqu'à 10 s) retarde tous les autres. |
| Commande + cron système | Aucun cron en place ; le Scheduler s'appuie sur les workers Messenger déjà exploités. |

## Modèle de données

Toutes les migrations sont générées par `symfony console make:migration`.

### Nouvelle structure `AdminEmail`

`src/Entity/AdminEmail.php`, table `admin_email` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | entier, auto | non | |
| `email` | string(180) | non | unique `UNIQ_ADMIN_EMAIL`, stocké en minuscules, `Assert\Email` côté formulaire |
| `createdAt` (`created_at`) | datetime_immutable | non | |

L'admin permanent (`RADAR_ADMIN_EMAIL`) n'est pas une ligne de cette table. Il ne peut donc pas en être retiré, par construction.

### Nouvelle structure `Source`

`src/Entity/Source.php`, table `source` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | entier, auto | non | |
| `feedUrl` (`feed_url`) | string(2048) | non | unique `UNIQ_SOURCE_FEED_URL` (une ligne par adresse, réactivée si retirée) |
| `name` | string(255) | non | titre du flux, sinon hôte de l'adresse |
| `categories` | ManyToMany (`Category`), table `source_category` | non | 1 à 2, validé à la soumission |
| `createdAt` (`created_at`) | datetime_immutable | non | |
| `removedAt` (`removed_at`) | datetime_immutable | oui | `null` = active ; index `IDX_SOURCE_REMOVED_AT` |
| `lastFetchedAt` (`last_fetched_at`) | datetime_immutable | oui | dernière relève réussie |
| `lastError` (`last_error`) | string(255) | oui | dernière erreur rencontrée, conservée après une relève réussie |
| `lastErrorAt` (`last_error_at`) | datetime_immutable | oui | |

### Nouvelle structure `SourceItem`

`src/Entity/SourceItem.php`, table `source_item` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `id` | entier, auto | non | |
| `source` | ManyToOne (`Source`), `source_id` | non | `onDelete: CASCADE` |
| `itemKey` (`item_key`) | string(2048) | non | `guid` (RSS) ou `id` (Atom), sinon lien de l'article ; unique `UNIQ_SOURCE_ITEM` (`source_id`, `item_key`) |
| `seenAt` (`seen_at`) | datetime_immutable | non | |

### Modification de `Link`

`src/Entity/Link.php` :

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `source` | ManyToOne (`Source`), `source_id` | oui | `onDelete: SET NULL` ; `null` = lien ajouté par un membre |

Ajout d'un paramètre `?Source $source = null` en fin de constructeur. Les liens existants restent `null` : aucune reprise de données n'est nécessaire.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Entity/AdminEmail.php` | Email admin géré depuis l'interface. |
| `src/Entity/Source.php` | Flux suivi : adresse, nom, catégories, état de relève, retrait logique. |
| `src/Entity/SourceItem.php` | Article déjà vu d'une source (clé unique par source). |
| `src/Repository/AdminEmailRepository.php` | `existsByEmail()`, `findAllOrderedByEmail()`, `save()`, `remove()`. |
| `src/Repository/SourceRepository.php` | `findOneByFeedUrl()`, `findActiveOrderedByName()`, `findActiveIds()`, `save()`. |
| `src/Repository/SourceItemRepository.php` | `findSeenKeys(Source, list<string>)` en une requête, `save()`. |
| `src/Security/AdminVoter.php` | Attribut `RADAR_ADMIN` : email de l'utilisateur = admin permanent ou présent dans `admin_email`. |
| `src/Service/AdminManager.php` | Ajout (minuscules, refus si déjà admin ou admin permanent) et retrait d'un email ; expose l'admin permanent pour l'affichage. |
| `src/Feed/FeedParser.php` | XML → `Feed` (titre + articles), RSS 2.0 et Atom 1.0 ; lève `InvalidFeedException` si le document n'est pas un flux. |
| `src/Feed/Feed.php`, `src/Feed/FeedItem.php` | Valeurs lues : titre du flux ; clé, lien absolu http(s) et date de parution de chaque article, dans l'ordre du document. |
| `src/Feed/XmlNodes.php` | Lecture des nœuds XML commune au parseur (extraite pour la complexité Mago). |
| `src/Feed/FeedReader.php` | Téléchargement via `app.preview_http_client` (types XML acceptés, 2 Mo au plus), puis parsing ; lève `InvalidFeedException` avec un message lisible. |
| `src/Exception/InvalidFeedException.php` | Flux injoignable ou illisible, message affichable. |
| `src/Exception/AlreadyFollowedSourceException.php`, `src/Exception/AlreadyAdminException.php` | Adresse déjà suivie par une source active ; email déjà admin. |
| `src/Enum/Type/FetchWindow.php` | Antériorité d'une relève à la demande (1 h, 12 h, 36 h, 72 h). |
| `src/Service/SourceManager.php` | Enregistrement (lecture du flux, nom, articles présents marqués vus, réactivation d'une source retirée), retrait logique. |
| `src/Service/SourcePublisher.php` | Relève d'une source avec le posteur fourni : lecture, enregistrement de l'erreur, publication des articles non vus (ou parus dans la fenêtre demandée) du plus ancien au plus récent, marquage vu ; renvoie le nombre d'articles publiés. |
| `src/Service/SystemUserProvider.php` | Find-or-create du compte `radar@radar.invalid` (mot de passe inutilisable). |
| `src/Form/SourceType.php`, `src/Form/Model/SourceSubmission.php` | Adresse (`Assert\Url` http/https) + catégories existantes (`Assert\Count` 1 à 2). |
| `src/Form/AdminEmailType.php` | Champ email (`Assert\NotBlank`, `Assert\Email`). |
| `src/Controller/SourceController.php` | `GET/POST /sources` (liste + enregistrement), `POST /sources/{id}/relever` (CSRF, relève à la demande avec antériorité), `POST /sources/{id}/retirer` (CSRF) ; `#[IsGranted('RADAR_ADMIN')]`. |
| `src/Controller/AdminController.php` | `GET/POST /administrateurs` (liste + ajout), `POST /administrateurs/{id}/retirer` (CSRF) ; `#[IsGranted('RADAR_ADMIN')]`. |
| `src/Message/FetchSources.php`, `src/Message/FetchSource.php` | Échéance horaire ; relève d'une source (`sourceId`). |
| `src/MessageHandler/FetchSourcesHandler.php` | Dispatche un `FetchSource` par source active. |
| `src/MessageHandler/FetchSourceHandler.php` | Délègue à `SourcePublisher` avec le compte « radar » ; ignore une source retirée ou introuvable. |
| `src/Schedule.php` | `#[AsSchedule]` : `FetchSources` toutes les heures, `stateful(cache.app)`, `processOnlyLastMissedRun`. |
| `templates/source/index.html.twig` | Formulaire d'ajout + tableau des sources actives (nom, adresse, catégories, dernière relève, dernière erreur et sa date, bouton « Relever » avec antériorité, bouton retirer). |
| `templates/admin/index.html.twig` | Formulaire d'ajout + liste (admin permanent badgé, sans bouton retirer). |
| `migrations/Version20261030141138.php`, `…141413.php`, `…141623.php` | `admin_email`, `source`, `source_category`, `source_item`, `link.source_id` — générées. |
| `tests/Fixtures/feed/symfony-blog.xml` | Extrait réel du flux Symfony (RSS 2.0, 3 articles, `content:encoded` raccourci). |
| `tests/Fixtures/feed/atom.xml` | Flux Atom 1.0 minimal (lien `rel="alternate"`, `id`). |
| `tests/Feed/FeedParserTest.php` | RSS, Atom, ordre, clés, liens relatifs/non http ignorés, XML invalide, document non flux, entité externe non résolue. |
| `tests/Feed/FeedReaderTest.php` | Type de contenu refusé, 404, réponse tronquée au plafond → `InvalidFeedException`. |
| `tests/Security/AdminVoterTest.php` | Admin permanent, email de la liste (casse ignorée), non-admin, permanent non configuré. |
| `tests/ScheduleTest.php` | Le planning contient `FetchSources` toutes les heures. |
| `tests/MessageHandler/FetchSourcesHandlerTest.php` | Un `FetchSource` par source active, aucun pour une source retirée. |
| `tests/Controller/SourceWebTestCase.php` | Base des tests fonctionnels des sources (flux simulés uniques par exécution, `?run=…`). |
| `tests/Controller/SourceControllerTest.php` | 403 non-admin ; ajout valide, flux illisible, adresse déjà suivie ; retrait et réactivation (état de relève remis à zéro) ; rendu de l'état de relève. |
| `tests/Controller/SourceFetchTest.php` | Relève à la demande : nombre d'articles publiés, reprise sur la fenêtre choisie, erreur affichée, source retirée refusée. |
| `tests/Controller/AdminControllerTest.php` | 403 non-admin ; ajout, doublon, email invalide ; retrait ; admin permanent sans bouton retirer ; nouvel admin accède à `/sources`. |
| `tests/Controller/SourceLinkTest.php` | Relève (sync en test) → carte avec marqueur et nom de la source, annonce Chat unique « via <source> » ; source retirée jamais relevée ; le compte « radar » ne peut pas se connecter. |
| `tests/E2E/SourceTest.php` | Admin : menu, enregistrement du flux Symfony réel puis retrait ; non-admin : menu absent ; carte auto des fixtures. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `composer.json`, `composer.lock`, `symfony.lock` | Ajouter `symfony/scheduler` `8.1.*`. |
| `config/packages/messenger.yaml` | Router `App\Message\FetchSource` vers `async` (`FetchSources` reste sur le transport du scheduler). |
| `.symfony.local.yaml` | Ajouter le worker `messenger:consume scheduler_default --silent`. |
| `.env`, `.env.dev`, `.env.test` | `RADAR_ADMIN_EMAIL=` (vide) dans `.env` ; `admin@example.com` en dev et en test. |
| `src/Entity/Link.php` | Relation nullable `source` + paramètre de constructeur + `getSource()`. |
| `src/Repository/UserRepository.php` | `findOneByEmail()` (compte « radar ») ; `findMembersOrderedByEmail()` remplace `findAllOrderedByEmail()` et exclut le compte système. |
| `src/Form/HistoricalLinkType.php` | Le compte « radar » n'est pas proposé comme posteur d'origine. |
| `src/Service/LinkUrlAnalyzer.php` | La normalisation conserve l'ancre et ne retire que `:~:text=`. |
| `src/Service/LinkManager.php` | Méthode de publication depuis une source (catégories de la source, posteur fourni, date de la relève, sans contexte, non `seeded`), réutilisant le chemin `create()` (doublon, normalisation, `FetchLinkPreview`). |
| `src/Repository/LinkRepository.php` | `leftJoin('l.source')` + `addSelect` dans `findLatest()` et `findByCategory()` (pas de N+1 sur le marqueur). |
| `src/Service/LinkAnnouncer.php` | Ligne posteur : `… · posté par radar via <nom de la source>` quand le lien a une source. |
| `templates/link/_card.html.twig` | Badge `data-test="link-auto"` (icône flux, « auto · <nom> ») dans le pied, sur le modèle de « repris de Chat » ; teinte orange des flux RSS, prioritaire sur celle de la plateforme. |
| `templates/base.html.twig` | Entrées de sidebar « Sources » et « Administrateurs » sous `is_granted('RADAR_ADMIN')`. |
| `fixtures/AppFixtures.php` | Source « Martin Fowler » (`https://martinfowler.com/feed.atom`, catégorie Architecture, relevée puis retirée) + 2 liens auto rattachés, avec leurs `SourceItem` ; compte « radar ». |
| `tests/Http/PreviewMockResponseFactory.php` | Réponses de flux : `/feed/symfony` (RSS), `/feed/atom` (Atom), `/feed/broken` (XML invalide), avec `content-type` XML. |
| `tests/Service/LinkAnnouncerTest.php` | Cas d'un lien de source (« via <nom> »). |
| `tests/Service/LinkUrlAnalyzerTest.php`, `tests/Controller/LinkDuplicateTest.php` | Ancres distinctes conservées, fragment de texte retiré. |
| `tests/Controller/LinkSeedTest.php` | Le compte « radar » n'est pas proposé comme posteur d'origine. |

## Hors scope

- **Commande console de relève manuelle** : non demandée ; la relève à la demande passe par le bouton « Relever » de `/sources`.
- **Requêtes conditionnelles (`ETag` / `If-Modified-Since`)** : une optimisation de bande passante, inutile à une relève par heure.
- **Purge de `source_item`** : quelques dizaines de lignes par source et par mois.
- **Mémoïsation du voter** : deux requêtes triviales par page, et un risque de fuite entre requêtes en mode worker.
- **Flux RSS 1.0 (RDF) et RSS 0.9x** : ni le flux cible ni Atom n'en ont besoin ; ils sont refusés comme flux illisibles.
- **Indicateurs C6.2 excluant le compte « radar »** : les indicateurs ne sont pas construits ; la note est dans le backlog (`indicateurs-mensuels`).
- **Mise en place des workers en production** : le déploiement FrankenPHP n'existe pas encore (`docs/stack.md`, « Cible non encore en place ») ; le worker `scheduler_default` sera à y ajouter.

## Impacts transverses

- **Cloisonnement des données** : non, l'application est mono-organisation.
- **Déclinaisons / thèmes** : clair et sombre, pour le badge et les deux pages, avec les classes `dark:` existantes.
- **Traduction / i18n** : libellés en français en dur, comme le reste des templates (aucun `|trans` dans le projet).
- **API / exposition externe** : aucune API. En sortie, Radar interroge les adresses de flux via le client sécurisé (pas de réseau privé, limiteur par hôte, 3 redirections, 10 s).
- **Droits d'accès** : nouveau voter `RADAR_ADMIN` ; `access_control` inchangé.
- **Emails / notifications** : annonce Google Chat existante, avec le nom de la source dans la ligne du posteur.
- **Migration de données** : création de 4 tables et d'une colonne nullable `link.source_id`, sans reprise.
- **Comportement par défaut** : sans `RADAR_ADMIN_EMAIL` ni ligne `admin_email`, personne n'est admin et rien ne change. Sans source active, la relève horaire ne fait rien.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Feed/FeedParser.php` | unit | RSS 2.0 réel (titre « Symfony Blog », 3 articles, clé = `guid`) ; Atom (clé = `id`, lien `alternate`) ; ordre du document ; dates de parution RSS et Atom ; article sans lien http(s) absolu ignoré ; XML invalide et document HTML → `InvalidFeedException` ; entité externe déclarée non résolue. |
| `src/Feed/FeedReader.php` | unit | `content-type` non XML refusé, 404, réponse au-delà de 2 Mo → `InvalidFeedException` avec message. |
| `src/Security/AdminVoter.php` | unit | Admin permanent ; email de la liste en casse différente ; membre non admin ; `RADAR_ADMIN_EMAIL` vide ; attribut non supporté → abstention. |
| `src/Service/SourceManager.php` | functional (`SourceControllerTest`) | Enregistrement : nom du flux (repli hôte), articles présents marqués vus, aucun lien créé ; adresse active déjà suivie refusée ; réactivation d'une source retirée (catégories remplacées, articles du moment marqués vus). |
| `src/Service/SourcePublisher.php` | functional (`SourceLinkTest`, `SourceFetchTest`) | Non vus publiés du plus ancien au plus récent ; vus ignorés ; doublon (R6) marqué vu sans exception ; erreur de flux enregistrée (`lastError`, `lastErrorAt`) sans exception ni publication ; réussite → `lastFetchedAt`. |
| `src/Schedule.php` | unit | `FetchSources` planifié toutes les heures. |
| `src/MessageHandler/FetchSourcesHandler.php` | unit | Un `FetchSource` par source active. |
| `src/Service/LinkManager.php` | functional (`SourceLinkTest`) | Publication depuis une source : catégories de la source, posteur fourni, `source` posée, aperçu et annonce ; doublon écarté par `findDuplicate()`. |
| `src/Service/LinkUrlAnalyzer.php` | unit | Ancre conservée, ancres distinctes non confondues, `:~:text=` retiré. |
| `src/Service/LinkAnnouncer.php` | unit | « posté par radar via <nom> » pour un lien de source ; ligne inchangée sinon. |
| `src/Controller/SourceController.php` | functional | 403 pour un non-admin ; ajout `/feed/symfony` → ligne « Symfony Blog » et aucun lien dans le fil ; `/feed/broken` → message d'erreur ; adresse déjà suivie → message ; retrait → ligne absente, liens conservés. |
| `src/Controller/AdminController.php` | functional | 403 pour un non-admin ; ajout d'un email → la personne accède à `/sources` ; email déjà admin refusé ; admin permanent listé sans bouton retirer ; retrait → 403 pour la personne retirée. |
| Relève de bout en bout (`FetchSourceHandler` en `sync://`) | functional | Un article ajouté au flux simulé est publié avec la catégorie de la source, le posteur « radar », le badge « auto · <nom> » ; une seule annonce Chat ; une seconde relève ne republie rien ; une source en erreur n'empêche pas une autre de publier. |
| Relève à la demande (`SourceController::fetch`) | functional | Nombre d'articles publiés affiché ; articles de la fenêtre repris même déjà vus, sans double publication ; erreur de flux affichée ; source retirée → 404. |
| Compte « radar » | functional | La connexion avec `radar@radar.invalid` échoue, quel que soit le mot de passe ; il n'est pas proposé comme posteur d'origine d'un lien repris de Chat. |
| Menu et pages | E2E | Admin : entrées « Sources » et « Administrateurs », enregistrement de `https://feeds.feedburner.com/symfony/blog` → ligne « Symfony Blog », puis retrait. Non-admin (marie) : pas d'entrée, `/sources` refusé. Carte auto des fixtures (source retirée) : badge et nom de la source. |

**Hors scope tests** :
- Pas de test du déclenchement réel du Scheduler dans le temps : le planning est vérifié unitairement, la relève par son handler.
- Pas de relève réelle en E2E : elle est horaire, donc couverte en fonctionnel avec des flux simulés. L'E2E ne prouve que l'enregistrement d'un vrai flux.
- Pas de test de flux RSS 1.0 ni RSS 0.9x (hors scope).

## Ordre d'exécution

1. [x] **Admins**
   - Objectif : liste d'emails admin gérable par un admin, admin permanent par env, voter, deux entrées de menu (« Sources » pointe vers une page vide à ce stade, ou n'apparaît qu'à l'étape 2).
   - Fichiers : `AdminEmail`, `AdminEmailRepository`, migration, `AdminVoter`, `AdminManager`, `AdminEmailType`, `AdminController`, `templates/admin/index.html.twig`, `base.html.twig` (entrée « Administrateurs »), `.env*`, `services.yaml`, tests du voter et du contrôleur.
   - Vérification : `make lint`, `make phpunit` ; en dev, `admin@example.com` voit « Administrateurs » et marie non.
   - Commitable seule : oui.

2. [x] **Sources : lecture de flux et enregistrement**
   - Objectif : un admin enregistre et retire des sources ; nom lu dans le flux, articles présents marqués vus, réactivation.
   - Fichiers : `Source`, `SourceItem`, repositories, migration, `src/Feed/*`, `InvalidFeedException`, `SourceManager`, `SourceType` + `SourceSubmission`, `SourceController`, `templates/source/index.html.twig`, entrée « Sources » dans `base.html.twig`, `PreviewMockResponseFactory`, fixtures de flux de test, tests parseur, lecteur, manager, contrôleur.
   - Vérification : `make lint`, `make phpunit` ; en dev, enregistrer le flux Symfony affiche « Symfony Blog » et ne publie rien.
   - Commitable seule : oui.

3. [x] **Relève et publication**
   - Objectif : chaque heure, les nouveaux articles des sources actives sont publiés, enrichis et annoncés ; les erreurs sont visibles.
   - Fichiers : `symfony/scheduler`, `SourceSchedule`, `FetchSources`/`FetchSource` + handlers, `messenger.yaml`, `.symfony.local.yaml`, `SourcePublisher`, `SystemUserProvider`, `Link.source` + migration, `LinkManager`, tests publisher, schedule, handlers, `LinkManagerTest`, relève de bout en bout.
   - Vérification : `make lint`, `make phpunit` ; en dev, `symfony console debug:scheduler` liste l'échéance, et un `FetchSource` dispatché à la main sur une source publie un article retiré de `source_item`.
   - Commitable seule : oui.

4. [x] **Rendu, annonce, fixtures et E2E**
   - Objectif : badge « auto · <nom> » sur la carte, nom de la source dans l'annonce, pas de N+1, fixtures de démonstration, E2E.
   - Fichiers : `_card.html.twig`, `LinkAnnouncer` (+ test), `LinkRepository` (joins), `AppFixtures`, `tests/E2E/SourceTest.php`.
   - Vérification : `make db-reset`, `make lint`, `make phpunit`, `make playwright` ; profiler du fil : nombre de requêtes inchangé avec des cartes auto.
   - Commitable seule : oui.

## Critères de sortie

- [x] Les 12 critères d'acceptation du pitch sont couverts par au moins un test (unitaire, fonctionnel ou E2E).
- [x] `symfony console doctrine:schema:validate` vert après les migrations générées.
- [x] `symfony console debug:scheduler` liste `FetchSources` toutes les heures.
- [x] Une relève sur un flux contenant un élément `<!ENTITY … SYSTEM …>` ne lit aucun fichier local (test du parseur).
- [x] Le fil avec des cartes auto ne déclenche aucune requête supplémentaire par carte.
- [x] Sans `RADAR_ADMIN_EMAIL` ni admin en base, aucune page ni entrée de menu n'est accessible, et le reste de Radar est inchangé.
- [x] `make phpunit` et `make playwright` verts, sans nouvelle régression.
- [x] `make lint` (Mago fmt, lint, analyze) sans finding ni baseline.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Afflux d'articles et d'annonces si un flux change ses identifiants (pas de plafond, choix assumé) | faible | Retirer la source arrête la relève ; l'état de relève est visible. À reconsidérer si cela se produit (question ouverte du pitch sur le volume Chat). |
| Worker `scheduler_default` absent en production : aucune relève, sans erreur | moyenne | La « dernière relève réussie » sur `/sources` rend l'arrêt visible ; worker à ajouter au déploiement FrankenPHP et à `docs/stack.md` au sync. |
| SSRF ou XXE via une adresse de flux saisie | faible | Client sécurisé des aperçus ; `Dom\XMLDocument` sans entités externes ; test dédié. |
| Flux volumineux (le flux Symfony fait 250 Ko avec `content:encoded`) | moyenne | Plafond de 2 Mo ; au-delà, erreur enregistrée et non bloquante. |
| E2E dépendant du réseau et de FeedBurner | moyenne | Le test se marque `skipped` si la page affiche « flux injoignable », plutôt que d'échouer. |
| En dev, la relève de la source de fixtures publierait ses articles réels du moment | élevée | La source de fixtures (Martin Fowler) est retirée : jamais relevée, ses liens gardent badge et nom. Le flux PHP.net envisagé publiait 885 entrées. |
| Contention SQLite entre le worker `async`, le worker scheduler et le web | faible | Le worker scheduler ne fait que dispatcher (écritures courtes dans `messenger_messages`) ; la relève tourne dans le worker `async` existant. |
| Deux relèves concurrentes d'une même source (retry, rattrapage `stateful`) | faible | Unicité `(source_id, item_key)` et unicité de `normalized_url` : un second passage échoue sans doublon ; la vérification des articles vus précède la publication. |

## Questions ouvertes

- **Adresse de flux normalisée ?** L'unicité porte sur l'adresse saisie (avec `trim`) : `http://` et `https://`, ou la présence d'un slash final, créent deux sources pour le même flux. Options : (a) adresse saisie telle quelle, (b) adresse finale après redirections. Recommandé : (a), le cas est rare et visible sur la page des sources, avec deux admins tout au plus.
- **Paramètres de suivi dans l'adresse stockée d'un article** (`utm_source=Symfony Blog Feed` dans le flux Symfony) : la normalisation les ignore pour le doublon, mais l'adresse ouverte les garde. Options : (a) garder l'adresse du flux, (b) stocker l'adresse sans paramètres de suivi. Recommandé : (a), c'est le comportement des liens collés par un membre ; à trancher à l'implémentation si l'affichage dérange.
- **Retrait d'un lien automatique** (question ouverte du pitch) : déléguée au cadrage de `correction-retrait-lien`.

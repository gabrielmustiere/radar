# Plan technique — Permettre à un admin de supprimer un lien et de relancer son annonce, dans une carte Google Chat soignée

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/018-f-admin-liens-carte-chat/`
> **Amont** : `pitch.md`

## Approche retenue

**Prérequis : la story `017-t-durcissement-securite` est livrée.** Sa neutralisation des fragments non fiables (sosies `‹ ›` dans les textes, `%3C %3E %7C` dans les URL affichées) est reprise par la carte. Si elle ne l'est pas au démarrage, `/forge:feature-implem` s'arrête et le signale.

Les gestes admin vivent dans un nouveau `AdminLinkController`, protégé par `#[IsGranted(AdminVoter::ADMIN)]`, qui délègue à `LinkManager`. Le bouton « … » de la carte charge dans la modale globale existante (`templates/base.html.twig:139`, frame `link-modal-frame`) un écran servi à la demande. Cet écran sert à la fois de menu et de confirmation : il affiche le titre, la dernière annonce et les deux actions en POST avec CSRF. L'état affiché est donc toujours frais, aucune donnée de confirmation ne s'ajoute à chaque carte du fil et aucun JavaScript n'est créé. Hors modale, la même route rend une page complète, comme `/liens/ajouter`.

La suppression passe par `LinkRepository::remove()`, qui efface les ouvertures puis le lien dans une transaction. **Les clés étrangères SQLite sont désactivées** (`select * from pragma_foreign_keys` renvoie `0`, aucun `EnableForeignKeys` configuré) : le `onDelete: CASCADE` de `LinkOpening::$link` est inerte et ne couvre pas la règle 5. Les lignes de `link_category` sont, elles, retirées par Doctrine, `Link` étant propriétaire de la relation.

`LinkAnnouncer` garde `announce()` pour le chemin automatique, avec son garde. Il gagne `reannounce()`, sans garde, utilisée par `LinkManager::reannounce()`, qui lève `LinkNotReadyException` tant que l'aperçu est en attente (règle 13). Les deux chemins partagent le même envoi : produire le bandeau, construire la carte, envoyer, marquer `announced_at`. La construction de la carte vit dans une classe dédiée, `LinkAnnouncementCard`, qui reprend la neutralisation de la 017 et renvoie les `GoogleChatOptions`.

Le bandeau est produit par `PreviewImageStore` au moment de l'annonce, depuis la copie conservée (`Link::getImagePath()`, 600 × 314) : recadrage centré en 600 × 200 par `ImageCropper::cover()`, aplati sur fond blanc, écrit en JPEG sous `banners/<hmac>.jpg`. Le nom est un HMAC-SHA256 de l'identifiant du lien avec `%kernel.secret%`. Une nouvelle action de `MediaController` le sert sans connexion, avec la seule règle `PUBLIC_ACCESS` ajoutée à `access_control`. Sans copie conservée, ou si l'écriture échoue, `storeBanner()` renvoie `null` et la carte prend sa version compacte. La suppression d'un lien efface son bandeau.

### Mécanismes mobilisés

- **`GoogleChatOptions::cardV2()`** : méthode native du bridge, avec `new GoogleChatOptions(['fallbackText' => …])` car le bridge n'a pas de méthode dédiée. Le transport envoie les options telles quelles et ignore le sujet du `ChatMessage`, qui porte aussi le texte de repli pour les tests et les logs. Les essais du 2026-11-04 ont confirmé l'acceptation par le webhook de l'en-tête, du widget `image`, de `textParagraph` et de `buttonList` (HTTP 200).
- **Widget `image` avec une image réellement au ratio 3:1** : `grid.cropStyle` a été écarté, car Google étire l'image au lieu de la couper (essai du 2026-11-04).
- **Modale Turbo Frame existante** : `link_modal_controller` s'ouvre sur `turbo:before-fetch-request`. Il suffit d'un lien `data-turbo-frame="link-modal-frame"` et d'une réponse portant cette frame. Les formulaires portent `data-turbo-frame="_top"` pour que la redirection finale recharge la page.
- **`#[IsGranted(AdminVoter::ADMIN)]` et `#[IsCsrfTokenValid]`** : même patron que `src/Controller/SourceController.php:98`, avec un token par action (`link-remove`, `link-reannounce`).
- **Exception métier + flash** : `LinkNotReadyException` rejoint `src/Exception/`, sur le modèle de `DuplicateLinkException`. Le contrôleur la transforme en flash d'erreur et tout le reste part en redirection PRG vers `app_page`.
- **`hash_hmac('sha256', …, %kernel.secret%)`** : nom de fichier non énumérable, sans dépendance ni expiration.
- **`EntityManager::wrapInTransaction()`** dans `LinkRepository::remove()`, avec un `DELETE` DQL sur `LinkOpening`.
- **Flysystem** : stockage `media.storage` existant, `read`, `write` et `delete`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Activer les clés étrangères SQLite (middleware `EnableForeignKeys`) | Change le comportement de toute l'application. Les migrations SQLite de Doctrine recréent les tables : un `DROP TABLE link` déclencherait les cascades et effacerait ouvertures et catégories de liens. Sujet d'une story technique dédiée. |
| Bandeau recadré par Google (`grid.cropStyle`) | Essai réel : l'image est étirée, donc déformée. |
| Grande image non recadrée, vignette en colonnes, image repliée | Essais réels : trop haute, vignette refusée par l'utilisateur, aperçu masqué par défaut. |
| Image d'origine du site dans la carte | Dépend d'un tiers au moment de l'affichage, et les images en 404 ou en http ne sont pas filtrées. |
| Adresse signée à durée limitée (`UriSigner`) | Les images des anciennes annonces casseraient si Google les relit après expiration. |
| Nom de fichier en `sha256` de l'URL, comme les aperçus | Partagé entre liens de même image, donc non supprimable sans vérification. Confirme aussi la présence d'une URL à qui la devine. |
| Génération du bandeau à la demande sur la route publique | Un traitement GD sur une route anonyme, et aucune information sur l'échec au moment de construire la carte. |
| Menu déroulant Flowbite sur la carte | Coupé par le `overflow-hidden` de `.link-card` et à réinitialiser après chaque rendu Live Component. |
| Réponse Turbo Stream retirant la carte du fil | Plus de code pour un geste rare, et pas de relayout automatique du masonry. |

## Modèle de données

Aucun impact modèle. `Link::$announcedAt` porte déjà la date de la dernière annonce : la relance le réécrit via `markAnnounced()`. Il reste posé à la mise en file. Aucun autre code que le garde d'`announce()` ne le lit, et poser cette date sur un lien repris ne change donc rien au reste de l'application.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Controller/AdminLinkController.php` | Écran d'actions (GET), suppression et relance (POST), réservés aux admins. |
| `src/Exception/LinkNotReadyException.php` | Relance refusée tant que l'aperçu est en attente. |
| `src/Service/LinkAnnouncementCard.php` | Construit les `GoogleChatOptions` : en-tête, bandeau optionnel, contexte ou description, posteur, boutons, texte de repli, neutralisation des fragments. |
| `templates/admin_link/show.html.twig` | Écran d'actions dans `link-modal-frame` : titre, dernière annonce, relance, suppression. |
| `templates/link/_card_admin_trigger.html.twig` | Bouton « … », inclus dans la ligne de source (ordinateur) et la ligne de type (mobile). |
| `tests/Controller/AdminLinkControllerTest.php` | Droits, CSRF, suppression, relance, contenu de l'écran. |
| `tests/Service/LinkAnnouncementCardTest.php` | Structure de la carte, variantes, fragments hostiles. |
| `tests/E2E/AdminLinkTest.php` | Parcours navigateur dans la modale. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Service/LinkAnnouncer.php` | `reannounce()` ajoutée, envoi partagé, texte remplacé par `LinkAnnouncementCard` et `PreviewImageStore`. |
| `src/Service/LinkManager.php` | `remove()` et `reannounce()` ajoutées, avec `LinkAnnouncer` et `PreviewImageStore` injectés. |
| `src/Repository/LinkRepository.php` | `remove()` : ouvertures puis lien, en transaction, avec un commentaire sur les clés étrangères inactives. |
| `src/Service/Preview/PreviewImageStore.php` | `storeBanner()`, `removeBanner()`, nom HMAC, format JPEG aplati ajouté à `write()`. |
| `src/Controller/MediaController.php` | Action publique `app_announcement_banner` (`/annonces/bandeaux/{name}.jpg`), lecture du flux factorisée. |
| `config/packages/security.yaml` | `PUBLIC_ACCESS` sur `^/annonces/bandeaux/`, avant la règle `^/`. |
| `templates/link/_card.html.twig` | Inclusion du bouton si `(admin_actions ?? true) and is_granted('RADAR_ADMIN')`. |
| `templates/link/_duplicate.html.twig` | Passe `admin_actions: false`, car la carte doublon s'affiche dans la modale. |
| `templates/components/PendingLinkCard.html.twig` | Ne rend rien si le lien a été supprimé pendant le rafraîchissement. |
| `templates/base.html.twig` | `aria-label` de la modale rendu générique, commentaire adapté. |
| `tests/Service/LinkAnnouncerTest.php` | Assertions sur la carte, relance sans garde, garde automatique conservé. |
| `tests/Service/LinkManagerTest.php` | Suppression et relance côté service. |
| `tests/Service/Preview/PreviewImageStoreTest.php` | Bandeau 600 × 200, absence de copie, suppression. |
| `tests/Controller/LinkAnnouncementTest.php` | Lecture de l'annonce dans les options de carte. |
| `tests/Controller/MediaControllerTest.php` | Bandeau anonyme, cache public, 404 et chemin hors motif. |

## Hors scope

- **Activation des clés étrangères SQLite** : à traiter en story technique, migrations comprises.
- **Effacement des aperçus et favicons d'un lien supprimé** : nommés par hash de l'image source, donc partageables, et réservés aux membres.
- **Retour à la page et aux filtres d'origine après une action** : redirection vers `app_page`, comme partout dans Radar.
- **Ratio ou recadrage par type de source** : même bandeau partout, à revoir à l'étape 6 pour GitHub.
- **Nom et avatar du webhook** : réglés dans Google Chat.
- **Purge des bandeaux orphelins** : seule une suppression concurrente à une annonce peut en laisser.

## Impacts transverses

- **Cloisonnement des données** : aucun cloisonnement dans Radar. La route publique ne sert que des bandeaux, sous un nom HMAC de 64 caractères hexadécimaux imposé par `requirements`.
- **Déclinaisons / thèmes** : bouton et écran en classes Tailwind claires et sombres. Le bouton est présent sur mobile, le pied de carte y étant masqué.
- **Traduction / i18n** : libellés en français en dur, comme le reste de l'application.
- **API / exposition externe** : nouvelle route anonyme `GET /annonces/bandeaux/{name}.jpg`, seule exception à R1. Le bandeau dérive d'une image déjà publique sur le site d'origine. API Platform non touchée.
- **Droits d'accès** : `AdminVoter` existant. Aucun nouveau rôle.
- **Emails / notifications** : un `ChatMessage` par relance, via le routage `async` existant. Aucun envoi à la suppression.
- **Migration de données** : aucune. Les bandeaux des liens existants naissent à leur relance.
- **Comportement par défaut** : `GOOGLE_CHAT_DSN=null://null` continue de ne rien envoyer, mais le bandeau est produit pendant l'annonce. Toutes les annonces passent en carte dès le déploiement.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/LinkAnnouncementCard.php` | unit | Carte complète ; sans bandeau ; contexte prioritaire sur la description ; ni l'un ni l'autre ; titre absent ; source suivie ; `<users/all>`, `<a href=…>` et `<https://x|y>` neutralisés dans chaque fragment ; boutons sans couleur ; texte de repli. |
| `src/Service/LinkAnnouncer.php` | unit | `announce()` respecte le garde, y compris au rejeu ; `reannounce()` envoie pour un lien annoncé, jamais annoncé ou repris et réécrit la date ; bandeau absent, carte compacte. |
| `src/Service/LinkManager.php` | functional | `reannounce()` lève `LinkNotReadyException` si l'aperçu est en attente ; `remove()` efface ouvertures, liaisons catégories et bandeau, conserve les catégories, libère l'URL. |
| `src/Service/Preview/PreviewImageStore.php` | unit | Bandeau 600 × 200 en JPEG depuis une copie ; transparence aplatie ; `null` sans copie ; nom stable et différent selon le secret ; suppression idempotente. |
| `src/Controller/AdminLinkController.php` | functional | Membre non admin refusé sur les trois routes ; CSRF invalide refusé ; écran avec titre, date ou « jamais annoncé » ; relance masquée en attente ; flash de mise en file ; lien inconnu en 404. |
| `src/Controller/MediaController.php` | functional | Bandeau lisible sans connexion, cache public ; `/media/` reste redirigé vers la connexion ; bandeau absent en 404. |
| `tests/Controller/LinkCardTest.php` | functional | Bouton visible d'un admin, absent pour un membre et sur la carte doublon. |
| `tests/E2E/AdminLinkTest.php` | E2E | Admin : ouvrir la modale depuis le fil, relancer, supprimer, voir la carte disparaître et le flash. |

**Hors scope tests** :

- Rendu réel dans Google Chat : non automatisable, vérifié à l'étape 6.
- Concurrence entre une suppression et un traitement d'aperçu en cours : fenêtre de quelques secondes, conséquence acceptée (règle 10).
- Comportement des clés étrangères : le test de `remove()` s'exécute sur la vraie base de test, ce qui prouve l'effacement explicite des ouvertures.

## Ordre d'exécution

0. [x] **Vérifier le prérequis**
   - Objectif : confirmer que la story 017 est livrée, sinon s'arrêter.
   - Fichiers : aucun.
   - Vérification : `src/Service/LinkAnnouncer.php` neutralise les fragments et `docs/story/017-t-durcissement-securite/metadata.json` porte `delivery`.
   - Commitable seule : sans objet.

1. [x] **Suppression côté service et contrôleur**
   - Objectif : `LinkRepository::remove()`, `LinkManager::remove()`, POST de suppression réservé aux admins.
   - Fichiers : `src/Repository/LinkRepository.php`, `src/Service/LinkManager.php`, `src/Controller/AdminLinkController.php`, tests associés.
   - Vérification : `make phpunit` ; le test prouve 0 ligne `link_opening` et `link_category` restante, avec les clés étrangères inactives.
   - Commitable seule : oui.

2. [x] **Écran d'actions et bouton sur la carte**
   - Objectif : modale avec suppression, bouton « … » sur le fil, la page détaillée et mobile, carte en attente protégée.
   - Fichiers : `templates/admin_link/show.html.twig`, `templates/link/_card_admin_trigger.html.twig`, `templates/link/_card.html.twig`, `templates/link/_duplicate.html.twig`, `templates/components/PendingLinkCard.html.twig`, `templates/base.html.twig`, tests.
   - Vérification : `make phpunit`, parcours manuel en clair et en sombre, sur ordinateur et téléphone.
   - Commitable seule : oui.

3. [x] **Relance**
   - Objectif : `reannounce()`, exception, POST, section relance de l'écran, avec l'annonce texte encore en place.
   - Fichiers : `src/Service/LinkAnnouncer.php`, `src/Service/LinkManager.php`, `src/Exception/LinkNotReadyException.php`, `src/Controller/AdminLinkController.php`, `templates/admin_link/show.html.twig`, tests.
   - Vérification : `make phpunit`.
   - Commitable seule : oui.

4. [x] **Bandeau et route publique**
   - Objectif : production, service anonyme et effacement du bandeau, non encore utilisé par l'annonce.
   - Fichiers : `src/Service/Preview/PreviewImageStore.php`, `src/Controller/MediaController.php`, `config/packages/security.yaml`, `src/Service/LinkManager.php`, tests.
   - Vérification : `make phpunit` ; `curl` anonyme sur un bandeau de dev.
   - Commitable seule : oui.

5. [x] **Carte d'annonce**
   - Objectif : remplacer le texte par la carte, dans tous les chemins d'annonce.
   - Fichiers : `src/Service/LinkAnnouncementCard.php`, `src/Service/LinkAnnouncer.php`, tests.
   - Vérification : `make phpunit`, `make lint`, `make playwright`.
   - Commitable seule : oui.

6. [ ] **Vérification en production**
   - Objectif : après `./deploy.sh`, publier une vraie annonce avec bandeau et en relire le rendu, images GitHub comprises.
   - Fichiers : aucun, sauf ajustement.
   - Vérification : accord de l'utilisateur avant l'envoi ; bandeau lisible sans connexion ; rendu validé dans Chat, sur ordinateur et mobile.
   - Commitable seule : sans objet.

## Critères de sortie

- [ ] Une suppression ne laisse aucune ligne `link_opening` ni `link_category` pour ce lien, ni aucun fichier de bandeau.
- [ ] `announce()` garde son comportement : une annonce automatique, jamais pour un lien repris, jamais au rejeu.
- [ ] Seule la route `/annonces/bandeaux/` est lisible sans connexion, et `/media/` reste protégée.
- [ ] Aucun fragment non fiable ne peut créer de balise, de lien ou de mention dans la carte.
- [ ] `make phpunit` et `make playwright` sont verts, sans nouvelle régression.
- [ ] `make lint` ne signale aucun finding.
- [ ] Aucune migration créée, `symfony console doctrine:schema:validate` reste vert.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Une annonce refusée par Google part en file d'échec, sans effet sur `announced_at` | faible | Structure testée par quatre essais réels ; l'admin peut relancer ; carte verrouillée par un test unitaire. |
| Le bandeau d'une image GitHub perd son texte au recadrage | moyenne | Essai réel avant la livraison, à l'étape 6 ; sinon, passer les dépôts GitHub en version compacte. |
| `DEFAULT_URI` incorrect dans le worker, donc bandeau et logo injoignables par Google | faible | `https://radar.mustiere.fr` dans `environments/prod/.env.example`, déjà utilisé par le lien de carte ; vérifié à l'étape 6. |
| Un `PendingLinkCard` ouvert ailleurs rafraîchit un lien supprimé | moyenne | Le template ne rend rien sans lien ; à confirmer sur l'hydratation d'un `LiveProp` d'entité disparue. |
| Les entités HTML ou `&` dans un titre s'affichent mal dans la carte | faible | Purement visuel, les sosies suffisent pour la sécurité ; relu à l'étape 6. |
| Un traitement GD alourdit la relance dans le processus web | faible | Une seule image de 600 px, geste admin rare. |

## Questions ouvertes

- **JPEG ou webp pour le bandeau ?** : le quatrième essai a été accepté en webp, mais l'affichage n'a pas été confirmé. Options : (a) JPEG, (b) webp, comme les aperçus. Recommandé : (a), universel, au prix d'un format ajouté à `write()`.
- **Bandeau pour les dépôts GitHub ?** : le rendu du recadrage n'a pas été confirmé. Options : (a) bandeau pour tous, (b) version compacte pour GitHub. Recommandé : (a) jusqu'à l'étape 6, pour ne pas ajouter un cas sans preuve.

# Plan technique — Typer la carte d'annonce Google Chat : icône, couleur et action par type

> **But** : figer le comment de la refonte de la carte d'annonce Google Chat — forme cible, verrou de non-régression, exécution incrémentale.
> **Registre** : technique
> **Story** : `docs/story/020-r-annonce-chat-typee/`
> **Amont** : aucun — spécification de design : `docs/redesign/google-chat/ANNONCE.md` (proposition 4), maquette `docs/redesign/google-chat/maquette-annonce.html`

## Motivation

`LinkAnnouncementCard` (123 lignes) produit un seul rendu pour 6 cas (YouTube, GitHub, X, source suivie, article, image) : le même `logo.png` en en-tête, le même bouton « Ouvrir le lien », aucune couleur. Le texte principal mélange deux sources (`getContext() ?? getDescription()`) et la ligne du posteur est séparée du contexte.

La bannière ajoute un coût pour peu de valeur : chaque annonce d'un lien avec aperçu écrit un fichier dans `media.storage`, et la route `app_announcement_banner` est la seule exception à « rien sans connexion » dans `access_control`.

Si on ne fait rien : la carte Chat reste le seul écran hors du système de teintes par type posé par la story 016, et chaque nouvelle annonce continue de créer un bandeau public.

Ce refacto embarque **des changements visibles, assumés et validés** (comme la story 016) : seul le contenu de la carte change, jamais les décisions d'envoi. Ils sont listés en §Comportement externe à préserver ; les tests qui verrouillaient l'ancienne carte sont réécrits explicitement.

## Approche retenue

La carte garde sa place et ses collaborateurs : `LinkAnnouncer` décide d'envoyer, `LinkAnnouncementCard` construit le message. On remplace le contenu de la carte selon la spec et on retire le paramètre `$bannerName`. Le type d'affichage reprend la logique de `_card.html.twig` : un lien d'une source suivie vaut `rss` quel que soit son type, sinon la valeur de `SourceType`.

La bannière cesse d'être produite, mais reste lisible et supprimable : la route publique, l'exception `access_control`, `path()`, `name()`, `remove()` et `LinkAnnouncer::forget()` restent, car les annonces déjà envoyées pointent vers leurs bandeaux et la suppression d'un lien doit retirer le sien. `store()`, `decode()` et `ImageCropper::onWhite()`, sans appelant après le refacto, disparaissent.

### Forme cible

```
src/Service/
├── LinkAnnouncementCard.php       # table STYLES à 6 entrées, nouveaux widgets, plus de bannière
├── LinkAnnouncer.php              # reannounce() n'écrit plus de bandeau ; forget() inchangé
└── Preview/
    ├── LinkBannerStore.php        # path(), name(), remove() seulement
    └── ImageCropper.php           # cover() seulement
public/chat/icons/                 # youtube, github, x, rss, article, image (PNG 128 × 128)
```

```php
final readonly class LinkAnnouncementCard
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LinkExtension $linkExtension,
    ) {}

    public function message(Link $link): ChatMessage;
}
```

Correspondance appliquée (clé = nom du PNG) :

| Clé | Règle | Couleur du bouton | Couleur du texte | Bouton principal |
|---|---|---|---|---|
| `youtube` | `SourceType::YouTube` | `#E11D48` | `#BE123C` | Regarder la vidéo |
| `github` | `SourceType::GitHub` | `#7C3AED` | `#6D28D9` | Voir le dépôt |
| `x` | `SourceType::X` | `#0369A1` | `#0369A1` | Voir sur X |
| `rss` | `$link->getSource() !== null`, prioritaire sur le type | `#C2410C` | `#C2410C` | Lire l'article |
| `article` | `SourceType::Article` | `#0F766E` | `#0F766E` | Lire l'article |
| `image` | `SourceType::Image` | `#17181C` | `#17181C` | Voir l'image |

Règles de contenu :

- **En-tête** : `imageUrl` = `{url de app_page}chat/icons/{clé}.png`, `imageType: SQUARE`, `imageAltText` = libellé du type, ou « Source suivie » pour `rss` (comme l'app).
- **Titre** : `safeText($this->linkExtension->title($link))`, même repli que l'app.
- **Sous-titre** : `{libellé} · {domaine} · {catégories}`. Le domaine n'existe que pour `SourceType::Article`, y compris venant d'une source suivie. Un segment vide est omis.
- **Description** : `SourceType::GitHub` et `SourceType::Article` seulement, masquée si elle est identique au titre (sans tenir compte de la casse ni des espaces), coupée à 160 caractères multioctets, « … » compris.
- **Le pourquoi** : `<b><font color="{texte}">{posteur} :</font></b> <i>{contexte}</i>` ; sans contexte `<font color="{texte}">Posté par {posteur}</font>` ; source suivie `<font color="#C2410C">Via le flux {source}</font>`. `LinkManager::publish()` ne pose jamais de contexte sur un lien de source.
- **Boutons** : principal avec `color` (RGBA 0–1 de la spec, `alpha: 1`) et `safeUrl($link->getUrl())` ; secondaire « Voir dans Radar » avec `type: BORDERLESS`, sans `color`, car `color` force `FILLED`.
- **`fallbackText`** : `{titre} · {sous-titre} · Posté par {posteur}` ou `… · Via le flux {source}`, sans aucune balise.

### Mécanismes mobilisés

- **Pas de pattern formel : réécriture sur place et retrait de code orphelin.** La classe garde sa responsabilité, seule sa sortie change.
- **Table de constantes privée** : 6 lignes de données pour un seul consommateur, et la clé donne aussi le nom du fichier d'icône.
- **Injection de `LinkExtension`** : réutilise le repli de titre et `host()` sans duplication, comme le fait déjà `AdminLinkController`.

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| Ajouter les styles Chat dans l'enum `SourceType` | `rss` n'est pas un `SourceType`, et la présentation d'un canal n'a rien à faire dans l'enum métier. |
| Enum ou classe de style dédiée à l'annonce | Abstraction pour un seul usage, contraire aux règles du projet. |
| Extraire le repli de titre de `LinkExtension` vers un service | Déplace du code stable et ses tests pour un seul nouvel appelant. |
| Supprimer `LinkBannerStore`, la route et l'exception `access_control` | Les annonces déjà envoyées perdraient leur image, et les fichiers existants resteraient à purger. |
| Garder `store()` | Code mort sans appelant en production. |

## Modèle de données

Aucun impact modèle.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `public/chat/icons/youtube.png` | Icône du type, déplacée depuis `docs/redesign/google-chat/icons/`. |
| `public/chat/icons/github.png` | Idem. |
| `public/chat/icons/x.png` | Idem. |
| `public/chat/icons/rss.png` | Idem. |
| `public/chat/icons/article.png` | Idem. |
| `public/chat/icons/image.png` | Idem. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Service/LinkAnnouncementCard.php` | Réécrire le contenu selon §Forme cible, retirer `$bannerName`, injecter `LinkExtension`, ajuster le docblock. |
| `src/Service/LinkAnnouncer.php` | Retirer l'appel à `store()` dans `reannounce()` ; garder `bannerStore` pour `forget()`. |
| `src/Service/Preview/LinkBannerStore.php` | Retirer `store()` et `decode()`, ajuster le docblock. |
| `src/Service/Preview/ImageCropper.php` | Retirer `onWhite()`. |
| `src/Controller/MediaController.php` | Corriger le commentaire « Une relance le réécrit », devenu faux. |
| `tests/Service/LinkAnnouncementCardTest.php` | Réécrire : un cas par ligne du tableau, description, échappement, cas limites. |
| `tests/Service/LinkAnnouncerTest.php` | Ajouter le test d'échec d'envoi, retirer les deux tests de bannière, adapter le constructeur. |
| `tests/Service/Preview/LinkBannerStoreTest.php` | Écrire le bandeau directement dans `testRemovesBannerAndIgnoresAMissingOne`, retirer les tests de `store()`. |
| `tests/Service/LinkRemovalTest.php` | Écrire le bandeau directement au lieu de passer par `store()`. |
| `tests/MessageHandler/FetchLinkPreviewHandlerTest.php` | Adapter le constructeur de la carte. |
| `tests/Service/LinkManagerTest.php` | Adapter le constructeur de la carte. |
| `tests/Service/LinkManagerReannounceTest.php` | Adapter le constructeur de la carte. |
| `tests/Service/LinkManagerCompleteTest.php` | Adapter le constructeur de la carte. |

### Clients identifiés

- **Vues / templates** — aucun client affecté.
- **`src/MessageHandler/FetchLinkPreviewHandler.php`** — non affecté, `announce(Link)` ne change pas.
- **`src/Service/LinkManager.php`** — non affecté, `reannounce(Link)` et `forget(Link)` ne changent pas.
- **`src/Controller/MediaController.php`** — route inchangée, seul un commentaire change.
- **Tests unitaires** — 6 fichiers construisent `LinkAnnouncementCard` et doivent passer à deux arguments.
- **Tests fonctionnels** — `AdminLinkReannounceTest` et `MediaControllerTest` non affectés ; `LinkRemovalTest` adapté.
- **Tests E2E** — `tests/E2E/AdminLinkTest.php` non affecté, le parcours d'annonce ne lit pas la carte.
- **Configuration** — `security.yaml`, `flysystem.yaml`, `messenger.yaml` et `notifier.yaml` non affectés.

## Hors scope

- **Purge des bandeaux existants** : ils restent servis pour les annonces déjà envoyées et disparaissent à la suppression de leur lien.
- **Retrait de la route et de l'exception `access_control`** : décision à reprendre plus tard, une fois les anciennes annonces sans intérêt.
- **Modification de la story 008** : ses documents livrés ne sont pas réécrits, l'écart sur le titre de repli est consigné ici.
- **Modification de `ANNONCE.md`** : la spec reste la source de design, y compris son chemin d'origine des icônes.
- **Logo `public/logo.png`** : conservé, d'autres usages peuvent en dépendre.

## Impacts transverses

- **Cloisonnement des données** : inchangé.
- **Déclinaisons / thèmes** : Google Chat seulement. Le rendu de `<font color>` en thème sombre n'est pas documenté : à contrôler à l'envoi réel.
- **Traduction / i18n** : inchangé, annonce en français.
- **API / exposition externe** : le format envoyé au webhook change (`color`, `type: BORDERLESS`, `imageAltText`, domaine dans le sous-titre). Aucune nouvelle donnée n'est envoyée à Chat.
- **Droits d'accès** : inchangé, `access_control` n'est pas touché. Les icônes sont des fichiers statiques publics sans donnée, comme `logo.png`.
- **Emails / notifications** : mêmes déclencheurs.
- **Migration de données** : aucune.

## Comportement externe à préserver

- **URLs** : inchangées, y compris `/annonces/bandeaux/{name}.jpg`.
- **Noms de routes** : tous préservés.
- **Signatures publiques** : `LinkAnnouncer::announce()`, `reannounce()` et `forget()` inchangées. `LinkAnnouncementCard::message()` perd `$bannerName`, son seul appelant est dans le périmètre. `LinkBannerStore::store()` disparaît.
- **Droits d'accès** : `app_announcement_banner` reste public, `app_media` reste réservé aux membres connectés.
- **Cloisonnement** : inchangé.
- **Effets de bord** : une seule annonce par lien, aucune pour un lien repris, envoyée après l'aperçu, marquage `announcedAt` au flush de l'appelant, un échec d'envoi ne bloque jamais l'ajout. La suppression d'un lien retire son bandeau. La relance admin reste possible, sans gardes.
- **Vues rendues** : inchangées.
- **Messages utilisateur** : inchangés dans l'app.

**Changements visibles assumés** (carte Chat uniquement) :

- icône et couleur par type, bouton principal libellé selon le type, plus de bannière (y compris à la relance) ;
- le contexte et la description deviennent deux blocs distincts, et la description n'apparaît que pour GitHub et les articles ;
- `Via {source}` devient `Via le flux {source}` ;
- **écart avec la story 008** (règle 4 et critère « annoncé avec son adresse comme titre ») : un lien sans titre affiche le repli lisible de l'app, et non l'adresse ;
- **écart avec la spec** : `imageAltText` vaut « Source suivie » pour `rss` ;
- **point laissé à vérifier par la spec** : `type: BORDERLESS` est retenu sous réserve de l'envoi réel.

## Stratégie de test

### Tests existants utilisés comme filet

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `tests/Service/LinkAnnouncerTest.php` | unit | Annonce unique et marquage, lien repris ignoré, relance avec nouvelle date. |
| `tests/MessageHandler/FetchLinkPreviewHandlerTest.php` | unit | Annonce après l'aperçu, pas de seconde annonce au rejeu, lien supprimé ignoré. |
| `tests/MessageHandler/RefreshLinkPreviewHandlerTest.php` | unit | Un rafraîchissement n'annonce pas. |
| `tests/Service/LinkManagerReannounceTest.php` | unit | Relance refusée tant que l'aperçu est attendu. |
| `tests/Controller/AdminLinkReannounceTest.php` | functional | Relance admin, jeton CSRF, aperçu en attente. |
| `tests/Controller/MediaControllerTest.php` | functional | Bandeau servi sans compte, en-têtes de cache et de type. |
| `tests/Service/LinkRemovalTest.php` | functional | La suppression d'un lien retire son bandeau. |

### Tests de caractérisation

| Test à créer | Comportement à verrouiller | Type |
|---|---|---|
| `tests/Service/LinkAnnouncerTest.php` (nouveau cas) | Un `send()` qui lève une exception la propage et laisse `announcedAt` à `null`. | unit |
| `tests/Service/LinkRemovalTest.php` (réécriture de l'arrangement) | `remove()` retire un bandeau écrit directement, sans dépendre de `store()`. | functional |
| `tests/Service/Preview/LinkBannerStoreTest.php` (réécriture de l'arrangement) | `remove()` retire le fichier et ignore un fichier absent, sans dépendre de `store()`. | unit |

**Règle absolue** : aucun code de production touché tant que ces tests ne sont pas écrits, verts et committés.

Nouveaux tests de la carte, écrits à l'étape 3 (ils décrivent la cible, pas l'existant) :

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `LinkAnnouncementCard`, 6 cas du tableau | unit | Icône, existence du PNG dans `public/chat/icons/`, couleur, libellé du bouton, le pourquoi avec et sans contexte. Source suivie : `Via le flux`, priorité sur le type, `imageAltText` « Source suivie ». |
| `LinkAnnouncementCard`, boutons | unit | `alpha: 1`, `color` absent et `type: BORDERLESS` sur « Voir dans Radar ». |
| `LinkAnnouncementCard`, titre et sous-titre | unit | Repli `@compte sur X`, domaine pour un article seulement, aucun séparateur final sans catégorie. |
| `LinkAnnouncementCard`, description | unit | Coupée à 160 caractères multioctets, absente pour YouTube, X et Image, masquée si identique au titre. |
| `LinkAnnouncementCard`, échappement | unit | `<users/all>` et `<a href=…>` dans le titre, la description, le contexte, le posteur, la source et les catégories : une fois retirées les balises `<b>`, `<i>` et `<font color="#…">` posées par le code, aucun `<` ne reste, ni dans la carte ni dans `fallbackText`. |
| `LinkAnnouncementCard`, URL | unit | `safeUrl()` sur le bouton principal. |
| `LinkAnnouncementCard`, cas limites | unit | Une URL sans hôte et un lien sans catégorie ne lèvent pas d'exception. |

**Hors scope tests** :

- **Rendu réel dans Chat et acceptation de `BORDERLESS`** : pas d'appel réseau en test, contrôle manuel en §Critères de sortie.
- **Routage `ChatMessage` → `async`** : configuration non touchée.
- **Parcours E2E** : aucun template ni parcours UI modifié.

## Ordre d'exécution

Pas de coexistence ancien/nouveau : chaque étape est elle-même un toggle par revert.

1. [ ] **Tests de caractérisation**
   - Objectif : verrouiller l'échec d'envoi et rendre les tests de `remove()` indépendants de `store()`.
   - Fichiers : `tests/Service/LinkAnnouncerTest.php`, `tests/Service/LinkRemovalTest.php`, `tests/Service/Preview/LinkBannerStoreTest.php`.
   - Vérification : `make phpunit` vert, sans code de production touché.
   - Commitable seule : oui.

2. [ ] **Déplacement des icônes**
   - Objectif : rendre les 6 PNG disponibles dans `public/chat/icons/`, encore inutilisés.
   - Fichiers : `public/chat/icons/*.png`.
   - Vérification : 6 fichiers en 128 × 128, `git status` sans changement dans `config/`.
   - Commitable seule : oui.

3. [ ] **Nouvelle carte et `LinkAnnouncer`**
   - Objectif : produire la carte de la spec, sans bannière ni écriture de bandeau.
   - Fichiers : `src/Service/LinkAnnouncementCard.php`, `src/Service/LinkAnnouncer.php`, `tests/Service/LinkAnnouncementCardTest.php`, `tests/Service/LinkAnnouncerTest.php` et les 4 autres fichiers de test qui construisent la carte.
   - Vérification : `make phpunit` et `make lint` verts, tests de caractérisation inchangés.
   - Commitable seule : oui, `store()` subsiste sans appelant jusqu'à l'étape 4.

4. [ ] **Retrait du code orphelin**
   - Objectif : retirer `store()`, `decode()`, `ImageCropper::onWhite()` et leurs tests.
   - Fichiers : `src/Service/Preview/LinkBannerStore.php`, `src/Service/Preview/ImageCropper.php`, `tests/Service/Preview/LinkBannerStoreTest.php`.
   - Vérification : `make phpunit` et `make lint` verts, 0 occurrence résiduelle dans `src/` et `tests/`.
   - Commitable seule : oui.

5. [ ] **Commentaires périmés et QA finale**
   - Objectif : aligner les commentaires sur le comportement livré.
   - Fichiers : `src/Controller/MediaController.php`, `src/Service/Preview/LinkBannerStore.php`.
   - Vérification : `make lint` et `make phpunit` verts.
   - Commitable seule : oui.

## Critères de sortie

- [ ] Tous les tests de caractérisation passent **avant ET après** chaque étape.
- [ ] `make phpunit` vert, sans nouvelle régression.
- [ ] `make lint` vert : zéro finding Mago, sans baseline.
- [ ] Un test par ligne du tableau de correspondance, plus le test d'échappement.
- [ ] 0 occurrence de `bannerName`, `onWhite` et `->store(` sur `LinkBannerStore`, mesurée par recherche dans `src/` et `tests/`.
- [ ] `config/packages/security.yaml` absent du diff.
- [ ] 6 PNG dans `public/chat/icons/`, plus aucun dans `docs/redesign/google-chat/icons/`.
- [ ] Avant déploiement, un envoi réel vers un espace Chat de test réussit, avec « Voir dans Radar » sans bordure ; à défaut, retrait de `type: BORDERLESS`.
- [ ] Le même envoi est relu en thème clair et sombre, notamment le texte `#17181C` du cas Image.
- [ ] Après déploiement, `https://radar.mustiere.fr/chat/icons/github.png` répond 200 en `image/png` sans session.
- [ ] Chaque étape committée est déployable seule (revert atomique possible).

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| `BORDERLESS` refusé par le webhook : annonces en file `failed`, liens ajoutés quand même | faible | Envoi réel avant déploiement, retrait d'une ligne en cas d'échec, reprise par `messenger:failed:retry`. |
| Exception dans la carte, avant le `flush()` de `FetchLinkPreviewHandler` : aperçu perdu jusqu'au retry | faible | Tests de cas limites (URL sans hôte, sans catégorie), aucun appel qui lève dans la construction. |
| Donnée externe placée dans le HTML sans `safeText()` | moyenne | Test d'échappement sur chaque champ, balises ajoutées uniquement par le code. |
| Titre de repli ou domaine issu de l'URL non échappé | faible | `safeText()` appliqué au résultat de `LinkExtension`, couvert par le test d'échappement. |
| Icône redirigée vers la connexion si le fichier n'est pas servi en statique | faible | Même emplacement que `logo.png`, `public/` non exclu de l'image, contrôle sans session après déploiement. `radar.wip` n'a pas pu être testé au cadrage. |
| Texte `#17181C` peu lisible en thème sombre de Chat | moyenne | Contrôle à l'envoi réel ; si illisible, retirer `<font>` pour le cas Image. |
| `alpha` ignoré par Google | élevée | Sans effet, conservé comme le demande la spec. |

## Questions ouvertes

_(aucune)_

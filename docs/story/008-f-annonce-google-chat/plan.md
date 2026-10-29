# Plan technique — Annoncer chaque nouveau lien dans Google Chat

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/008-f-annonce-google-chat/`
> **Amont** : `pitch.md`

## Approche retenue

L'annonce passe par Symfony Notifier et son bridge officiel `symfony/google-chat-notifier` : un transport `chatter` configuré par `GOOGLE_CHAT_DSN` (`null://null` par défaut, donc inactif). `ChatMessage` est déjà routé sur le transport Messenger `async` (`config/packages/messenger.yaml`) : l'envoi ne bloque jamais l'ajout, et un échec part en retry puis en file `failed`.

`LinkAnnouncer::announce(Link)` est appelé par `FetchLinkPreviewHandler` après l'enregistrement de l'aperçu (règle 5). Il ne fait rien pour un lien repris (`seeded`) ou déjà annoncé (`announcedAt` renseigné), construit le texte de l'annonce (titre ou adresse, catégories, posteur, contexte, lien d'origine, URL absolue de la carte via le routeur et `DEFAULT_URI`), l'envoie par `ChatterInterface`, puis pose `announcedAt`. Le marquage dans la même transaction que l'aperçu garantit une seule annonce si le handler est rejoué.

### Mécanismes mobilisés

- **Symfony Notifier + bridge Google Chat** : transport officiel, configurable par DSN, désactivable (`null://`).
- **Messenger (`ChatMessage` → `async`)** : envoi découplé, retry existant.
- **`UrlGeneratorInterface::ABSOLUTE_URL` + `framework.router.default_uri`** : lien vers la carte depuis le worker CLI.
- **Champ `announcedAt`** : garde d'idempotence (R5).

### Alternatives écartées

| Alternative | Pourquoi écartée |
|---|---|
| POST HttpClient direct vers le webhook | Réimplémente ce que le bridge fait ; perd la désactivation par DSN et le routage Messenger déjà en place. |
| Annoncer dans `LinkManager::add()` | Le titre n'est pas encore connu (aperçu asynchrone). |
| Message Messenger dédié `AnnounceLink` | Un message de plus pour un appel qui suit toujours l'aperçu ; `ChatMessage` est déjà asynchrone. |

## Modèle de données

### Modification de `Link`

| Champ | Type | Nullable | Contrainte |
|---|---|---|---|
| `announcedAt` | datetime_immutable (`announced_at`) | oui | posé à l'envoi de l'annonce |

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Service/LinkAnnouncer.php` | Règles d'annonce (repris, déjà annoncé), texte, envoi, marquage. |
| `migrations/VersionXXXX.php` | Colonne `announced_at` (générée). |
| `tests/Service/LinkAnnouncerTest.php` | Texte complet, repli sur l'adresse, repris ignoré, déjà annoncé ignoré. |
| `tests/Controller/LinkAnnouncementTest.php` | Ajout → une annonce envoyée ; reprise → aucune. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `composer.json` / `composer.lock` / `symfony.lock` | `symfony/google-chat-notifier`. |
| `config/packages/notifier.yaml` | `chatter_transports.googlechat: '%env(GOOGLE_CHAT_DSN)%'`. |
| `.env` | `GOOGLE_CHAT_DSN=null://null` documenté (bloc généré par la recette Flex du bridge). |
| `src/Entity/Link.php` | `announcedAt`, `markAnnounced()`. |
| `src/MessageHandler/FetchLinkPreviewHandler.php` | Appel de `LinkAnnouncer` après l'aperçu. |
| `tests/MessageHandler/FetchLinkPreviewHandlerTest.php` | Nouvelle dépendance ; un aperçu construit marque le lien annoncé. |
| `README.md` | Configuration de l'espace Google Chat. |

## Hors scope

- **Format carte (cardsV2) de Google Chat** : un message texte formaté suffit et reste lisible partout.
- **Rejeu des annonces échouées** : la file `failed` de Messenger existe (`messenger:failed:retry`).

## Impacts transverses

- **Cloisonnement des données** : aucun.
- **Déclinaisons / thèmes** : non.
- **Traduction / i18n** : texte d'annonce en français.
- **API / exposition externe** : webhook entrant Google Chat ; DSN en variable d'environnement, jamais commité.
- **Droits d'accès** : inchangé.
- **Emails / notifications** : un message Google Chat par ajout.
- **Migration de données** : colonne nullable, aucune reprise (aucun lien existant n'est annoncé).
- **Comportement par défaut** : `null://null` — aucune annonce.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `LinkAnnouncer` | unit | texte (titre, catégories, posteur, contexte, URL d'origine, URL de carte) ; repli sur l'adresse ; lien repris ignoré ; lien déjà annoncé ignoré ; `announcedAt` posé. |
| Ajout + reprise | functional | ajout → 1 message Notifier ; reprise → 0. |

**Hors scope tests** :

- Pas d'envoi réel vers Google Chat : dépend de l'autorisation des webhooks (question ouverte du pitch), à vérifier en recette.

## Ordre d'exécution

1. [x] **Bridge et configuration** — `composer require`, notifier, `.env`. Vérification : `debug:container` du transport. Commitable seule : oui.
2. [x] **Modèle et service** — `announcedAt`, migration, `LinkAnnouncer`, appel dans le handler. Vérification : `make lint`. Commitable seule : oui.
3. [x] **Tests, documentation et QA finale** — tests, README, `make lint`, `make phpunit`, `make playwright`. Commitable seule : oui.

## Critères de sortie

- [x] `GOOGLE_CHAT_DSN` absent du dépôt (valeur `null://null` par défaut).
- [x] Une seule annonce par lien, aucune pour un lien repris (tests).
- [x] `make lint`, `make phpunit`, `make playwright` verts.

## Risques et mitigations

| Risque | Probabilité | Mitigation |
|---|---|---|
| Webhooks bloqués par l'administrateur Workspace | moyenne | Feature inactive par défaut ; vérifier avant la mise en production. |
| Annonce envoyée mais transaction de l'aperçu en échec | faible | `ChatMessage` est dispatché dans le bus : sous transport Doctrine, il est enregistré dans la même base ; un rejeu ne réannonce pas un lien marqué. |
| Worker Messenger arrêté | moyenne | Annonces différées, pas perdues (file Doctrine). |

## Questions ouvertes

_(aucune)_

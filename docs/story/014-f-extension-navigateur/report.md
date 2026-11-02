# Report — Ajouter la page en cours à Radar depuis le navigateur, sans quitter la page

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/014-f-extension-navigateur/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 9, dont 8 réalignés dans `plan.md`/`pitch.md` et 1 laissé tel quel.
- **Critères** : 11 / 15 atteints, cochés dans `pitch.md` ; 8 / 8 critères de sortie cochés dans `plan.md`.
- **Non livré** : 5 éléments (4 critères d'acceptation et l'étape 7 du plan), tous suspendus à la recette manuelle ou à la publication.
- **Dette** : 4 items, dont 1 critique.

Le principal écart est l'E2E de la bulle. Playwright PHP ne charge pas d'extension, la bulle est donc testée en `file://` avec une API `chrome` simulée. L'icône, le clic droit, l'installation et le temps d'ajout restent à vérifier à la main avant publication.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| E2E de la bulle comme vraie extension (`launchPersistentContext` + `--load-extension`) | Bulle ouverte en `file://`, API `chrome` simulée par `addInitScript`, appels réels à l'API de `radar.wip` | Playwright PHP déclare `launchPersistentContext()` sans l'implémenter, et Chrome renvoie `ERR_BLOCKED_BY_CLIENT` dans ses contextes ; une origine factice par `route()` bloque les navigations suivantes. Choix validé par l'utilisateur (implémentation) | `plan.md` §Mécanismes mobilisés, §Périmètre, §Stratégie de test, §Ordre d'exécution, §Risques |
| Configuration d'API Platform : docs, entrypoint et Swagger coupés | `doctrine.enabled: false` et `enable_scalar: false` en plus | Sans `doctrine.enabled: false`, le cache ne se vide pas (`api-platform/doctrine-orm` absent) ; Scalar est une interface de documentation activée par défaut (implémentation) | `plan.md` §Mécanismes mobilisés |
| Doublon : `{id, url, title, categories, freeSlots, cardUrl}` | `{id, title, categories: [{id, name}], freeSlots, cardUrl}` | Des catégories en ressources sont réduites à leur IRI par API Platform ; `url` retiré à cause du seuil `excessive-parameter-list` de Mago, la bulle connaissant l'adresse (implémentation) | `plan.md` §Mécanismes mobilisés, §Périmètre |
| `Cache-Control: no-store` sur la réponse qui affiche la clé | Marqueur `NO_AUTO_CACHE_CONTROL_HEADER` ajouté | Le listener de session réécrivait l'en-tête en `private, must-revalidate` (implémentation, révélé par le test) | `plan.md` §Mécanismes mobilisés |
| Jeton CSRF invalide → 403 | Redirection vers `/login` | Comportement du projet : une erreur CSRF est une erreur d'authentification (cf. `LinkPlayTest`) | `plan.md` §Mécanismes mobilisés, §Stratégie de test |
| Règle 9 : « chaque ajout » repousse l'expiration | Chaque requête authentifiée, ouverture de la bulle comprise | Choix de l'utilisateur au plan | `pitch.md` §Règles métier n°9 |
| Fenêtre de repli du clic droit sur `popup.html?url=…&title=…` | `popup.html?url=…` | Paramètre `title` jamais lu (review [STYLE] « Paramètre `title` transmis mais jamais lu ») | `plan.md` §Mécanismes mobilisés |
| Tests d'authentification dans `ExtensionAuthTest` | Répartis entre `ExtensionAuthTest` et `ExtensionScopeTest` ; la session est ouverte par le vrai formulaire de connexion | Seuil `too-many-methods` de Mago ; `KernelBrowser::loginUser()` injecte le jeton quel que soit le pare-feu et faussait le test (implémentation) | `plan.md` §Périmètre, §Critères de sortie |
| Messages de longueur d'un nom de catégorie portés par `CategoryPickerTrait` | Recopiés dans `ExtensionCategory` avec `normalizer: 'trim'` | La contrainte du trait est portée par une `LiveProp`, sans réutilisation possible par un DTO API (implémentation) | — |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Critère : clic sur l'icône, bulle avec l'adresse préremplie | Reporté : non couvert par l'E2E (bulle ouverte en `file://`), recette manuelle pas encore faite | Dette n°2 |
| Critère : clic droit « Ajouter cette page à Radar » | Reporté : recette manuelle pas encore faite | Dette n°2 |
| Critère : installation depuis le Chrome Web Store sur Chrome et Edge | Reporté : publication suspendue au choix du compte développeur (question ouverte du pitch) | Dette n°4 |
| Critère : ajout en moins de 15 secondes | Reporté : non mesuré, relève de la recette manuelle | Dette n°2 |
| Étape 7 du plan, « QA finale et recette manuelle » | QA automatique faite (consignée dans `metadata.json`), recette manuelle reportée | Dette n°2 |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| `src/ApiResource/ExtensionCategorySelection.php` | Entrée du complément de doublon, distincte de la sortie `ExtensionDuplicate` | `plan.md` §Périmètre |
| `tests/Api/ExtensionScopeTest.php` | Découpage imposé par Mago (voir écarts volontaires) | `plan.md` §Périmètre |
| Badge « expirée » sur la page de clé | Le membre comprend pourquoi l'extension refuse sa clé ; testé après la review [TEST] « Chemins d'erreur de la bulle non couverts » | `pitch.md` §Règles métier n°8 |
| Avertissement « Ne rechargez pas cette page » sous la clé | Review [UX] « Recharger la page affichant la clé en génère une nouvelle » | — |
| `config/bundles.php`, `config/reference.php` | Enregistrement du bundle et référence régénérée par la recette d'API Platform | `plan.md` §Périmètre |

## Dette technique identifiée

1. **Vérifier `SYMFONY_TRUSTED_PROXIES` dans `environments/prod/.env`** — sans lui, le limiteur voit l'IP de Caddy, et 10 clés refusées bloquent l'extension de tous les membres pendant 15 minutes. Présent dans `.env.example` ; à confirmer sur le serveur. **Critique** au premier déploiement (review, « Hors review »).
2. **Recette manuelle de l'extension non empaquetée** sur Chrome et Edge : icône sur une page web, clic droit (`openPopup()` puis fenêtre de repli), page `chrome://` refusée, mode sombre, ajout en moins de 15 secondes (review, « Hors review » ; plan, étape 7).
3. **Rejouer les E2E ignorés** `AddLinkTest` (3) et `RichCardTest` (3) après `make db-reset` : le catalogue de vrais liens est épuisé dans la base de dev. Hors story (review, « Hors review »).
4. **Publier l'extension sur le Chrome Web Store** en non répertorié : choisir le compte développeur (question ouverte du pitch), déclarer `https://radar.mustiere.fr/confidentialite-extension.html` dans la fiche, partager le lien dans Chat.

## Leçons apprises

- **Playwright PHP ne charge pas d'extension** : pas de contexte persistant, et `context->route()` bloque les navigations suivantes. Une page d'extension se teste en `file://` avec une API `chrome` simulée, à prévoir dès le plan pour toute évolution de `extension/`.
- **`KernelBrowser::loginUser()` contourne l'absence d'état d'un pare-feu** : pour prouver qu'une session n'ouvre pas une zone sans état, se connecter par le vrai formulaire.
- **Le listener de session réécrit `Cache-Control`** sur toute réponse avec session : une réponse qui doit rester `no-store` exige `AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER`.
- **API Platform réduit une ressource imbriquée à son IRI** sans groupes de sérialisation : pour un client simple, renvoyer des tableaux plutôt que des DTO ressources.
- **Le stockage du rate limiter persiste entre deux exécutions de tests** (cache sur disque) : un test qui provoque des refus doit partir d'une IP propre.

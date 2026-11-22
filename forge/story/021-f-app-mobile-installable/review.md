# Review — Installer Radar comme une app sur son téléphone et y partager un lien depuis Android

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `forge/story/021-f-app-mobile-installable/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree, 15 fichiers modifiés (+114/−7) + 20 nouveaux (code, statiques, icônes, tests)

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 0 / 1
- **Statut** : **PRÊT À COMMITER**

`/forge:sync`, puis `/forge:commit`, après la recette sur appareils listée en « Hors review ».

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[TEST] Le parcours partage, puis rendu Live, puis soumission n'est couvert par aucun test automatique** — `tests/E2E/PwaTest.php` — `LinkShareTest` vérifie le préremplissage au premier affichage et la soumission d'un `channel=share` posté à la main, mais rien ne vérifie que le champ caché garde `share` après un nouveau rendu du Live Component (coche d'une catégorie). Vérifié à la main pendant la review (`channel: share` après la coche). Action : un E2E qui ouvre `/liens/ajouter?text=…https://…`, coche une catégorie, vérifie `[data-test="link-channel"]` = `share`, soumet et attend « Lien ajouté. ». Corrigé : `PwaTest::testSharedLinkKeepsItsChannelThroughTheFormUntilAdded`, vert sur une base réinitialisée (ignoré tant que le catalogue de liens E2E de la base de dev est épuisé, comme les autres tests d'ajout).

## Points positifs

- **Fichiers statiques pour le manifeste, le service worker et la page hors ligne** : ce choix évite d'ouvrir une seule exception `PUBLIC_ACCESS` dans `security.yaml`. Le service worker ne touche qu'aux navigations : aucun HTML authentifié n'est mis en cache.
- **Écarts imposés par Mago** (`SharedLinkExtractor`, canal porté par la soumission) : ils ont donné une structure plus simple que le plan. Pas de condition sur `seeded`/`source` dans `LinkManager::create()`, et la règle « reprise et source sans canal » est portée par les types (`HistoricalLinkSubmission::$channel = null`).
- **Valeur de canal falsifiée** : elle est ramenée à `web` par le transformer, sans erreur pour le membre, et c'est testé (`testUnknownChannelIsRecordedAsWeb`).
- **Retour après connexion** : le partage reçu en étant déconnecté est testé de bout en bout, query string comprise, et non supposé à partir de la config.
- **Extraction du lien** : 12 cas, dont la parenthèse qui appartient au lien (Wikipédia) et les schémas non web ignorés.

## Hors review (à vérifier en environnement réel)

- Recette appareils du plan (étape 5) : installation et plein écran sur Android et iPhone, encoche et barre d'accueil, invite native au clic sur « Installer l'app », Radar dans le menu Partager de Chrome et de YouTube, partage puis ajout puis annonce Chat, ouverture d'un lien d'origine depuis l'app installée.
- Clic sur un lien hors ligne dans l'app installée : Turbo charge les pages en `fetch` (non interceptées par le service worker). Après l'échec réseau, Turbo doit recharger la page complète, et c'est cette navigation que le service worker remplace par la page hors ligne. À confirmer en recette (mode avion, puis clic sur une catégorie).
- Production : `manifest.json`, `sw.js` et `offline.html` servis en 200 sans session par Caddy et FrankenPHP (vérifié seulement en local), et migration `Version20261122150027` appliquée par `deploy.sh`.

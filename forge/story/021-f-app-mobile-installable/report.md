# Report — Installer Radar comme une app sur son téléphone et y partager un lien depuis Android

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `forge/story/021-f-app-mobile-installable/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 8 — 5 écarts volontaires et 3 ajouts non prévus, tous réalignés dans `plan.md` et `pitch.md`.
- **Critères** : 8 / 13 atteints, cochés dans `pitch.md` ; 6 / 8 critères de sortie cochés dans `plan.md`.
- **Non livré** : la recette sur appareils réels (étape 5 du plan), et avec elle les 5 critères d'acceptation et les 2 critères de sortie qui en dépendent.
- **Dette** : 3 items, dont 1 critique.

Le code livré suit l'approche du plan. Les écarts viennent des seuils de Mago, qui ont redécoupé l'extraction du lien, le passage du canal et les tests. Il reste à vérifier sur un Android et un iPhone réels, et en production, ce que l'automatisation ne peut pas toucher.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Extraction du lien partagé dans `LinkUrlAnalyzer` | Nouveau service `SharedLinkExtractor` | Mago : complexité de classe trop élevée sur `LinkUrlAnalyzer` (implem) | `plan.md` §Approche retenue, §Périmètre, §Stratégie de test, §Ordre d'exécution |
| `LinkManager::create()` reçoit un `?LinkChannel` | Le canal est porté par la soumission : `LinkSubmission::$channel` nullable, redéclaré à `null` dans `HistoricalLinkSubmission`, posé à `null` par `publish()` | Mago : liste de paramètres trop longue, puis drapeau booléen utilisé en condition (implem) | `plan.md` §Approche retenue, §Modèle de données, §Périmètre |
| Tests du canal ajoutés à `LinkManagerTest` | `LinkManagerChannelTest` dédié | Mago : trop de méthodes dans `LinkManagerTest` (implem) | `plan.md` §Périmètre, §Ordre d'exécution |
| Marges de zone de sécurité haut, gauche et droite sur la topbar | Marge haute seulement | Paysage hors des critères d'acceptation, simplicité (implem) | `plan.md` §Périmètre |
| Message « Aucun lien trouvé dans le partage, colle l'URL » | « … collez l'URL » | Toute l'interface vouvoie (implem) | `pitch.md` §Règles métier, §Critères d'acceptation |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Recette sur un Android et un iPhone réels (plan, étape 5) et critère de sortie « Recette manuelle Android et iPhone consignée » | Reportée : elle demande des appareils réels, et le partage Android une URL HTTPS joignable par le téléphone (déploiement ou tunnel) | Dette n° 1 |
| Critères d'acceptation : installation Android, installation iPhone, encoche et barre d'accueil, invite native sur Android, Radar dans le menu Partager | Reportés : ils ne se vérifient que dans la recette sur appareils | Dette n° 1 |
| Critère de sortie « aucune erreur d'installabilité dans Chrome DevTools » | Reporté : non vérifié dans DevTools. Le service des trois fichiers sans session est, lui, attesté (curl local et E2E `PwaTest`) | Dette n° 1 |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Marge basse de zone de sécurité sur les messages flash (`templates/common/flash-messages.html.twig`) | Les confirmations passeraient sous la barre d'accueil de l'iPhone (implem) | `plan.md` §Périmètre |
| Helper `linkByUrl()` dans `LinkWebTestCase` | Lire le canal du lien créé dans les tests fonctionnels (implem) | `plan.md` §Périmètre |
| E2E du parcours partage, rendu Live, ajout | Correctif de review : [TEST] parcours partage, rendu Live, soumission sans E2E | `plan.md` §Périmètre, §Stratégie de test |

## Dette technique identifiée

1. **Recette sur appareils réels** — critères d'acceptation non cochés de `pitch.md`, étape 5 et critères de sortie restants de `plan.md`. Sur un Android avec Chrome et un iPhone avec Safari :
   - installation, plein écran, icône et nom ;
   - encoche et barre d'accueil ;
   - invite native au clic sur « Installer l'app » ;
   - Radar dans le menu Partager de Chrome et de YouTube, puis ajout et annonce Chat ;
   - ouverture d'un lien d'origine depuis l'app installée ;
   - Chrome DevTools, rubrique Manifest, sans erreur.

   **Critique** avant d'annoncer l'app à l'équipe.
2. **Clic hors ligne dans l'app installée** (« Hors review » de la review) — Turbo charge les pages en `fetch`, que le service worker n'intercepte pas. Vérifier qu'après l'échec réseau Turbo recharge la page complète et que le service worker sert alors la page hors ligne (mode avion, puis clic sur une catégorie).
3. **Production** (« Hors review » de la review) — `manifest.json`, `sw.js` et `offline.html` servis en 200 sans session par Caddy et FrankenPHP, et migration `Version20261122150027` appliquée par `deploy.sh`.

## Leçons apprises

- **Les seuils de Mago redécoupent le code au moment de l'implémentation** : une méthode ajoutée à une classe déjà dense (`LinkUrlAnalyzer`, `LinkManagerTest`) ou un paramètre de plus (`create()`) déclenche complexité, nombre de méthodes ou liste de paramètres. Un plan qui ajoute une responsabilité à une classe existante doit prévoir d'emblée une classe dédiée.
- **Un nouvel E2E qui ajoute un vrai lien est ignoré, pas en échec, quand le catalogue de la base de dev est épuisé** (`E2ETestCase::freeRealLink()`). Le test n'a pu être vérifié qu'en sauvegardant `var/data.db`, en lançant `make db-reset`, puis en restaurant la sauvegarde.
- **`make build` compile `public/assets/` en dev** : le serveur local sert ensuite ces assets figés au lieu des sources, jusqu'à suppression du dossier.

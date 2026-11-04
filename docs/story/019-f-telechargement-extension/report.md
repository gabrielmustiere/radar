# Report — Télécharger et installer l'extension depuis Radar, avec une FAQ sur la même page

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/019-f-telechargement-extension/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 4 — 4 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 9 / 13 atteints, cochés dans `pitch.md` ; 5 / 7 critères de sortie cochés dans `plan.md`.
- **Non livré** : 2 étapes du plan, 4 critères d'acceptation et 2 critères de sortie, tous suspendus à la recette manuelle ou au déploiement.
- **Dette** : 8 items, dont 2 critiques.

Le code et les tests automatiques sont livrés. La recette sur Chrome et Edge, que le plan place avant le commit final, n'est pas consignée ; le parcours réel n'a été prouvé que sur Chromium, par `--load-extension`.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Tests de la page et du téléchargement dans `ExtensionKeyControllerTest` | Classe dédiée `ExtensionDownloadTest` ; le test existant n'est pas modifié | Seuil Mago de méthodes par classe (implem) | `plan.md` §Périmètre, §Ordre d'exécution |
| Tests de l'alerte dans `ExtensionTest` | Classe dédiée `ExtensionUpdateTest`, avec `generateKey()` et `pasteKey()` remontés dans `ExtensionTestCase` | Seuil Mago de méthodes par classe (implem) | `plan.md` §Périmètre, §Ordre d'exécution |
| Recette avec `DEFAULT_URI` dans `.env.local` | Le zip servi par le site local vise déjà `https://radar.wip` | La Symfony CLI fournit `DEFAULT_URI` en https au serveur web (mesuré à l'implem) | `plan.md` §Hors scope, §Ordre d'exécution |
| Absence d'alerte contrôlée par l'état de la ligne sur l'écran de clé invalide | Contrôle qu'aucun appel `/version` ne part avec une clé révoquée | Review [TEST] assertion vide, ligne déjà masquée sur cet écran ; test prouvé par mutation | `plan.md` §Stratégie de test, §Périmètre |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Étape 7 du plan, recette manuelle locale, et ses critères : installation depuis le zip, icône et clic droit, ajout en moins de 15 secondes, mise à jour sans perte de clé, recette consignée | Reporté : à mener par le porteur sur Chrome et Edge, résultats non consignés à cette passe | Dette n°1 |
| Étape 8 du plan, contrôle en production, et critère « le téléchargement répond 200 dans l'image » | Reporté : suppose le déploiement. L'image construite en local contient `ZipArchive` et `extension/` et produit le zip, sans appel HTTP | Dette n°2 |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| `tests/E2E/E2ETestCase.php` : barre de profiler fermée sur l'origine de Radar | `FilterTest::testPhoneUsesTheFloatingAddButton` échouait à environ 9 lancements sur 10 : la barre recouvre le bouton flottant à l'arrivée de `_wdt`. 2/10 sur le code sans la story, 10/10 après correctif | `plan.md` §Périmètre |
| `extension/config.js` : commentaire réécrit | Review [DOC] commentaire devenu faux | `plan.md` §Périmètre |

## Dette technique identifiée

1. **Recette manuelle sur Chrome et Edge** — installation par « Charger l'extension non empaquetée » avec la seule procédure de la page, icône, clic droit, ajout en moins de 15 secondes, mise à jour par remplacement du contenu du dossier puis « Recharger », puis chargement depuis un autre dossier (`review.md`, « Hors review »). Reprend la dette n°2 de la story 014. **Critique** avant toute annonce aux membres.
2. **Contrôle en production** — confirmer `SYMFONY_TRUSTED_PROXIES` sur le serveur (dette n°1 de la story 014 : 10 clés refusées bloqueraient tous les membres pendant 15 minutes), télécharger le zip depuis `https://radar.mustiere.fr` et ajouter un lien. **Critique** au déploiement.
3. **Libellés de la procédure** — vérifier « Charger l'extension décompressée » et « Mode développeur » sur Edge, et « Recharger » sur les deux navigateurs (`templates/extension_key/_install.html.twig`, `_faq.html.twig`).
4. **Brave et Arc** — annoncés compatibles par la page, mais la procédure ne cite que `chrome://extensions` et `edge://extensions`, et la recette ne les couvre pas.
5. **Clé privée** — ranger `~/radar-extension-private.pem` dans le gestionnaire de mots de passe, puis la supprimer du poste.
6. **Publication sur le Chrome Web Store** — reportée à une story ultérieure, avec le choix du compte développeur (dette n°4 de la story 014). `make extension` produit le paquet, sans `key`.
7. **Page de confidentialité** — `public/confidentialite-extension.html` dit que l'adresse n'est envoyée qu'à l'ajout, alors que la bulle l'envoie à l'ouverture pour détecter un doublon. Préexistant.
8. **Worker `async` du serveur local** — absent de `symfony server:status` jusqu'au redémarrage, cause non établie : les cartes restent alors en `pending`. Préexistant.

## Leçons apprises

- **Un script d'initialisation Playwright s'exécute dans tout le contexte** : popups comprises. Toucher à `localStorage` hors de l'origine testée a empêché l'ouverture des popups de `CardOpenTest` ; à limiter à `location.origin`.
- **Une reproduction doit suivre la séquence exacte du test** : le banc Node donnait 0/20 puis 60 %, le test PHP 1/12, car le pilote plus lent perd une course d'environ 20 ms. Un correctif validé au banc se revalide dans la suite réelle.
- **La barre de profiler compte dans les E2E** : les suites tournent sur le serveur de dev, où elle occupe 72 px en bas d'un écran de 390 px une fois `_wdt` chargé.
- **`symfony server:start` dans une copie du dépôt reprend le domaine du proxy** : `.symfony.local.yaml` suit la copie. À rétablir par `symfony proxy:domain:attach radar`. `git archive`, lui, écarte `tests/` par `export-ignore` ; un `git clone` conserve tout.
- **`make db-reset` remplace le fichier de base** : un worker en cours garde l'ancien fichier supprimé, il faut redémarrer le serveur. Le catalogue E2E s'épuise à chaque lancement et les tests concernés passent en ignorés sans échouer.

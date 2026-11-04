# Review — Télécharger et installer l'extension depuis Radar, avec une FAQ sur la même page

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/019-f-telechargement-extension/`
> **Amont** : `plan.md`, `pitch.md`
> **Diff examiné** : working tree, 14 fichiers modifiés + 9 nouveaux, hors `CLAUDE.md` (modification antérieure, sans lien avec la story)

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 2
- **Mineurs restants** : 0 / 1
- **Statut** : **PRÊT À COMMITER SOUS RÉSERVE** de la recette manuelle sur Chrome et Edge, que le plan place avant le commit final (étape 7).

Mener la recette, puis `/forge:sync` et `/forge:commit`.

## Bloquants

_(aucun)_

## Importants

- [x] **[UX] La procédure de mise à jour échoue quand le nouveau dossier ne porte pas le même nom** — `templates/extension_key/_faq.html.twig:32` — La FAQ disait de remplacer l'ancien dossier par le nouveau, or un second téléchargement devient `radar-extension (1).zip`, qui se décompresse dans un dossier `radar-extension (1)`. Le membre qui supprimait l'ancien dossier puis cliquait sur « Recharger » pointait vers un chemin disparu, et c'est le parcours de la règle 13 du pitch. Corrigé : la FAQ dit de remplacer le **contenu** du dossier habituel, un geste qui ne dépend plus du nom.
- [x] **[DOC] Le commentaire de `config.js` est devenu faux** — `extension/config.js:1` — Il disait que `make extension` met l'adresse de production « pour la version distribuée », alors que la version distribuée vient désormais de `ExtensionPackage`, avec `DEFAULT_URI`. Corrigé : le commentaire cite les deux paquets.

## Mineurs

- [x] **[TEST] L'absence d'alerte sur l'écran de clé invalide était vérifiée à vide** — `tests/E2E/ExtensionTest.php:118` — La ligne d'alerte vit dans la section du formulaire, masquée sur cet écran : `toBeHidden()` passait quoi qu'il arrive. Corrigé : le test vérifie qu'aucun appel `/version` ne part, et il échoue bien si la vérification précède la preuve de la clé (mutation).

## Points positifs

- **Fichier hors de `public/`** : le zip passe par une route du pare-feu `main`, et le test couvre l'accès direct par un visiteur non connecté (R1).
- **`key` injectée uniquement dans le paquet** : le manifest du dépôt reste sans `key`, et un test le vérifie, ce qui protège l'extension locale et le futur paquet du Web Store.
- **Alerte isolée du parcours d'ajout** : `checkVersion` n'est pas attendue et avale ses erreurs, ce qui respecte les règles 9 et 10 du pitch, avec un E2E sur l'appel en échec.
- **Garde sur `config.js`** : le paquet refuse d'être produit si l'adresse locale n'y figure plus, au lieu de servir un zip visant le mauvais serveur.
- **Preuves en conditions réelles** : zip chargé dans Chromium et image Docker construite, au-delà des tests avec l'API `chrome` simulée.

## Hors review (à vérifier en environnement réel)

- **Recette manuelle sur Chrome et Edge** (étape 7 du plan, condition de livraison) : installation par « Charger l'extension non empaquetée », icône, clic droit, ajout en moins de 15 secondes, mise à jour avec clé conservée, y compris depuis un autre dossier. Seul `--load-extension` a été testé sur Chromium.
- **Libellés de la procédure** : vérifier « Charger l'extension décompressée » et « Mode développeur » sur Edge, et « Recharger » sur les deux navigateurs (`templates/extension_key/_install.html.twig`, `_faq.html.twig`).
- **Brave et Arc** : annoncés compatibles par la page, mais la procédure ne cite que `chrome://extensions` et `edge://extensions`, et la recette ne les couvre pas.
- **Contrôle en production** (étape 8) : valeur de `SYMFONY_TRUSTED_PROXIES` sur le serveur, zip téléchargé depuis `https://radar.mustiere.fr`, ajout d'un lien, avant l'annonce dans Chat.
- **Clé privée** : `~/radar-extension-private.pem` à ranger dans le gestionnaire de mots de passe, puis à supprimer du poste.
- **Préexistant, à traiter à part** : `public/confidentialite-extension.html` dit que l'adresse n'est envoyée qu'à l'ajout, alors que la bulle l'envoie à l'ouverture pour détecter un doublon.
- **Préexistant, à surveiller** : le worker `async` manquait au serveur local avant le redémarrage, sans cause établie. Les cartes restent alors en `pending`.

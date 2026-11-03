# Review — Cartes typées, puis redesign complet (modèle 5 clair)

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/016-r-cartes-typees/`
> **Amont** : `plan.md` (dont §Extension du périmètre)
> **Diff examiné** : working tree, 46 fichiers modifiés (dont 3 supprimés) + 11 nouveaux (hors `docs/redesign/`), ~1 150 lignes ajoutées / 750 retirées

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 0 / 5
- **Statut** : **PRÊT À COMMITER**

Aucun bloquant : comportement externe conforme au plan et à son extension, `data-test` existants tous conservés, lint, PHPUnit (395) et E2E (45) verts. 4 mineurs corrigés pendant la passe, le [DOC] réaligné par `/forge:sync`. Prochaine étape : `/forge:commit`.

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[TEST] Assertion devenue vide sur la sidebar** — `tests/Controller/SourceControllerTest.php:36` — `#radar-sidebar` n'existe plus : l'assertion passe quoi qu'il arrive. Vérifier plutôt que chaque entrée d'administration n'apparaît qu'une fois (`assertSelectorCount(1, …)`), et renommer le test (« …NotInTheSidebar »). Corrigé : `testAdminFindsTheAdministrationOnlyInTheUserMenu`.
- [x] **[STYLE] Pages pleine page sans fond de carte** — `templates/link/new.html.twig`, `templates/link/seed.html.twig` — sans le panneau blanc de l'ancien layout, le formulaire ouvert hors modale repose directement sur `gray-50`. L'envelopper dans une carte blanche `rounded-[20px]`, comme les formulaires admin. Corrigé : carte sur l'élément `turbo-frame` de la page, que la modale ne reprend pas.
- [x] **[TEST] Test en double sur les pastilles de catégorie** — `tests/Controller/LinkCardBadgeTest.php:70` — `testCategoriesAreNotLinks` répète `CategoryControllerTest::testCardOpensOriginalLinkAndCategoriesAreNotLinks`. En garder un. Corrigé : retiré de `LinkCardBadgeTest`.
- [x] **[ARCHI] Correspondance FeedType ↔ SourceType implicite** — `src/Repository/LinkFacetRepository.php:87` — `FeedType::tryFrom($sourceType->value)` suppose des valeurs identiques, alors que `FeedType::sourceType()` porte la correspondance explicite. Ajouter `FeedType::fromSourceType()` (ou parcourir `cases()`) pour une seule source. Corrigé : `FeedType::fromSourceType()`.
- [x] **[DOC] Titre et slug de la story en retard sur le périmètre** — `docs/story/016-r-cartes-typees/plan.md`, `metadata.json` — la story « cartes typées » couvre désormais tout le redesign, filtres et back compris (extension demandée). À réaligner au `/forge:sync` (titre, motivation, registre refacto devenu mixte). Réaligné par `/forge:sync` : titre du plan et de `metadata.json` ; slug du dossier conservé (références existantes).

## Points positifs

- **Verrou de caractérisation respecté puis réutilisé** : `CardOpenTest` vérifié vert sur les anciens et nouveaux templates, avant de retirer les couches de lien.
- **Cascade Tailwind 4 traitée à la racine** : `backwards`, `--tw-shadow`, `z-10` sur le bloc de la façade — chaque piège documenté par un commentaire de pourquoi.
- **Requêtes de filtre confinées aux repositories** : `LinkRepository::filtered()` sert à la fois la liste et les compteurs (`LinkFacetRepository`), une seule définition du filtre.
- **Aucun `data-test` perdu** sur les 11 templates restylés (comparaison avant/après), y compris ceux faits en parallèle.
- **Valeurs de filtre typées** : enums en `MapQueryParameter`, valeur inconnue → 404 testée.

## Hors review (à vérifier en environnement réel)

- Connexion en production : les identifiants de test ne doivent plus s'afficher (`app.environment == 'dev'`).
- Après un `make db-reset`, relancer le serveur (`make serve-d`) : le worker Messenger reste attaché à l'ancien fichier SQLite et les aperçus restent `pending` (préexistant, hors story).

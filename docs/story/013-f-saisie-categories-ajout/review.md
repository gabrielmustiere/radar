# Review — Créer et choisir jusqu'à trois catégories au fil de la saisie

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/013-f-saisie-categories-ajout/`
> **Amont** : `plan.md` + `pitch.md`
> **Diff examiné** : working tree, 24 fichiers modifiés + 10 nouveaux (hors docs), ~620 lignes

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 0 / 4
- **Statut** : **PRÊT À COMMITER**

`/forge:sync`, puis `/forge:commit`.

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[UX] Message « est créée » pour une catégorie qui existait déjà** — `src/Twig/Components/CategoryPickerTrait.php:61` — au plafond, taper le nom d'une catégorie existante non cochée affichait « « X » est créée » alors que rien n'était créé. Corrigé : « « X » est disponible : désélectionnez une catégorie pour la retenir. », vrai dans les deux cas ; test `testExistingNameTypedAtTheCapIsNeitherCreatedNorChecked`.
- [x] **[ROBUSTESSE] Complément concurrent → erreur 500** — `src/Service/LinkManager.php:79` — si un autre membre complétait le même lien entre l'affichage de l'alerte et le clic, `complete()` levait une `\LogicException` (500). Corrigé : exception de domaine `CategoryLimitException`, attrapée par `LinkForm::completeDuplicate()` qui re-rend l'alerte à jour avec un message ; test `testDuplicateCompletedMeanwhileByAnotherMemberIsRenderedAgain`.
- [x] **[TEST] Catégorie vide absente de la navigation non vérifiée** — `tests/Twig/Components/LinkFormTest.php` — critère d'acceptation du pitch (« créée puis désélectionnée… n'apparaît pas dans la navigation ») couvert seulement par le comportement préexistant de `LinkRepository::countByCategory()` ; un test fonctionnel (catégorie créée sans lien → absente de `[data-test="category-nav-item"]`) le verrouillerait. Corrigé : `CategoryControllerTest::testCategoryWithoutLinkIsNotInTheSidebar`.
- [x] **[TEST] Les E2E laissent des catégories dans la base de dev** — `tests/E2E/AddLinkTest.php:89`, `tests/E2E/SourceTest.php:58` — chaque `make playwright` crée deux catégories « E2e … » sans lien, définitivement proposées dans les pastilles du formulaire de dev (aucune suppression de catégorie n'existe) ; préfixer d'un marqueur stable et réutiliser la même catégorie d'un passage à l'autre, ou l'accepter jusqu'à `fusion-categories`. Corrigé : noms stables (« E2e créée à l'entrée », « E2e source à l'entrée »), créés au premier passage puis retrouvés.

## Points positifs

- **Une seule source pour le plafond** : `LinkSubmission::MAX_CATEGORIES` pilote la validation des liens et des sources, le verrou des pastilles, la création au-delà du plafond et `LinkManager::complete()`.
- **Création sans JS maison** : l'action Live crée, coche puis laisse le re-rendu relire les choix de l'`EntityType` ; le filtre masque sans retirer du DOM, testé (case cochée toujours postée).
- **Contrôle d'accès vérifié au bon endroit** : `#[IsGranted(AdminVoter::ADMIN)]` sur `SourceForm`, avec un test qui prouve le refus d'un membre via la route Live générique.
- **`complete()` vérifie avant de muter** : le plafond est contrôlé avant tout `addCategory()`, l'entité gérée n'est jamais laissée dans un état à moitié modifié.
- **Migration de tests propre** : les 7 fichiers qui passaient par `newCategory` utilisent un helper unique (`LinkWebTestCase::createCategory()`), qui crée comme le formulaire.

## Hors review (à vérifier en environnement réel)

- Chronométrer un ajout complet (contexte vide, deux catégories dont une créée, adresse collée) : moins de 15 secondes (critère d'acceptation du pitch).
- Préexistant, hors périmètre : `tests/E2E/SourceTest.php` (`symfonyRow()`) passe l'option `hasText` à `page->locator()`, ignorée par Playwright PHP ; le localisateur vise toutes les lignes de source.
- Rejouer `make playwright` après `make db-reset` : le dernier passage a ignoré 6 tests, faute de liens réels libres dans le catalogue E2E de la base de dev (verts au passage précédent, inchangés depuis).
- `DESIGN.md` décrit encore l'ajout comme « coller l'URL → … catégories » : à réaligner au `/sync`.

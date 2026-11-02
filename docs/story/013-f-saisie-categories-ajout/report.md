# Report — Créer et choisir jusqu'à trois catégories au fil de la saisie

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/013-f-saisie-categories-ajout/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

- **Écarts** : 7 — 6 réalignés dans `plan.md`/`pitch.md`, 1 laissé tel quel.
- **Critères** : 16 / 17 atteints, cochés dans `pitch.md` ; 7 / 7 critères de sortie cochés dans `plan.md`.
- **Non livré** : 1 — le chronométrage « moins de 15 secondes », non mesuré.
- **Dette** : 3 items, aucun critique.

Le complément sur doublon passe par la sélection du formulaire au lieu de cases dédiées (décision utilisateur), et le plafond de `complete()` est porté par une exception de domaine depuis la review. Reste à chronométrer l'ajout et à rejouer les E2E sur une base de dev réinitialisée.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Cases dédiées dans l'alerte de doublon, pré-cochées si elles tiennent, grisées au-delà de la place restante (LiveProp `duplicateCategoryIds`) | Catégories proposées = cases cochées du formulaire absentes du lien ; bouton si elles tiennent, sinon le nombre à décocher | Décision utilisateur à l'implémentation : les cases dédiées dupliquaient l'état de la sélection | `pitch.md` §Règles métier (règle 9) ; `plan.md` §Approche retenue, §Périmètre, §Stratégie de test, §Ordre d'exécution |
| `\LogicException` au-delà du plafond dans `complete()` (question ouverte, recommandation (b)) | `CategoryLimitException`, attrapée par `completeDuplicate`, qui re-rend l'alerte avec un message | Review **[ROBUSTESSE] Complément concurrent → erreur 500** | `pitch.md` §Règles métier (règle 9) ; `plan.md` §Approche retenue, §Périmètre, §Questions ouvertes |
| `CategoryPickerTrait` suppose `ComponentWithFormTrait` | Le trait inclut `ComponentWithFormTrait` | Mago : `formValues` inconnue dans le trait (`non-existent-property`) | `plan.md` §Mécanismes mobilisés, §Périmètre |
| Contraintes `NotBlank` + `Length` sur `categoryQuery`, `#[LiveArg]` | `Length` seule, saisie vide ignorée sans message ; aucun `#[LiveArg]`, services injectés en arguments d'action | Non tracée | `plan.md` §Mécanismes mobilisés |
| Cas `complete()` dans `LinkManagerTest` ; un seul `LinkFormTest` | `LinkManagerCompleteTest` ; `LinkFormTest` + `LinkFormDuplicateTest` + `LinkFormTestCase` | Seuil Mago `too-many-methods` | `plan.md` §Périmètre, §Stratégie de test |
| E2E sources : création à l'Entrée puis « Suivre » | Création à l'Entrée et case cochée, sans envoi | Non tracée (l'envoi exige un flux réel joignable) | `plan.md` §Stratégie de test |
| Message au plafond « « X » est créée » | « « X » est disponible : désélectionnez une catégorie pour la retenir. » | Review **[UX] Message « est créée » pour une catégorie qui existait déjà** | — (le pitch ne fixe pas la formulation) |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Critère « un ajout complet se fait en moins de 15 secondes » | Non mesuré : validation manuelle prévue par le plan (§Hors scope tests), pas encore faite | Chronométrer en recette (dette n°1) |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| `CategoryRepository::findByIdsOrderedByName()` | Charger les catégories cochées proposées sur un doublon | `plan.md` §Périmètre |
| `src/Exception/CategoryLimitException.php` et `LinkForm::$duplicateNotice` | Review **[ROBUSTESSE] Complément concurrent → erreur 500** | `plan.md` §Périmètre |
| Test « catégorie sans lien absente de la navigation » (`CategoryControllerTest`) | Review **[TEST] Catégorie vide absente de la navigation non vérifiée** | `plan.md` §Périmètre |
| Catégories E2E à nom stable | Review **[TEST] Les E2E laissent des catégories dans la base de dev** | `plan.md` §Périmètre |
| `LinkSeedTest` : reprise avec 3 catégories, helper `seed()` réordonné | Vérifier que la reprise de Chat suit le plafond | `plan.md` §Périmètre |
| Retrait de l'assertion « catégorie non créée sur doublon » (`LinkDuplicateTest`) | Devenue fausse : la création est immédiate | `plan.md` §Périmètre |

## Dette technique identifiée

1. **Chronométrer l'ajout** — contexte vide, deux catégories dont une créée, adresse collée : moins de 15 secondes (critère d'acceptation non coché). Point « Hors review ».
2. **Rejouer `make playwright` après `make db-reset`** — le dernier passage a ignoré 6 tests faute de liens réels libres dans le catalogue E2E de la base de dev ; verts au passage précédent, inchangés depuis. Point « Hors review ».
3. **`DESIGN.md` §Écrans à venir** — décrit encore l'ajout comme « coller l'URL → … catégories » ; à aligner sur l'ordre contexte → catégories → adresse (document hors périmètre de `sync`). Point « Hors review ».

Préexistant, hors périmètre, signalé par la review : `tests/E2E/SourceTest.php` (`symfonyRow()`) passe l'option `hasText` à `page->locator()`, ignorée par Playwright PHP.

## Leçons apprises

- **Playwright PHP ignore l'option `hasText` de `page->locator()`** : le localisateur vise alors tous les éléments du sélecteur, et les assertions portent sur le premier. Filtrer par le sélecteur `:has-text("…")`.
- **Les tests Live redémarrent le kernel entre deux requêtes** : une entité chargée avant `call()` n'est plus gérée ensuite ; la relire depuis le repository du conteneur courant avant de la modifier ou de l'asserter.
- **`ComponentWithFormTrait` ré-instancie le formulaire au re-rendu** : modifier `formValues` dans une action suffit, les choix d'un `EntityType` relisent la base sans code de plus.

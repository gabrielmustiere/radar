# Report — Redesign « modèle 5 clair » : cartes typées, filtres en entonnoir, layout sans sidebar, écrans restylés

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/016-r-cartes-typees/`
> **Amont** : `plan.md` · `review.md`

## Synthèse

- **Écarts** : 12 — 9 réalignés dans `plan.md`, 3 laissés tels quels.
- **Critères** : 6 / 8 atteints, cochés dans `plan.md` §Critères de sortie.
- **Non livré** : 4 éléments (2 critères de sortie, 2 points des écrans admin).
- **Dette** : 4 items, aucun critique.
- **Comportement préservé** : tests de caractérisation `CardOpenTest` verts avant et après chaque étape, y compris sur les anciens templates (implem). Les changements de comportement de l'extension du périmètre sont assumés et listés dans `plan.md` §Comportement externe à préserver : la story n'est plus un refacto pur.

Le refacto des cartes (8 étapes) a été livré tel que prévu, puis étendu à la demande de l'utilisateur au reste du redesign (ex-stories 017, 018, 019 et `DESIGN.md`), enchaîné sans validation intermédiaire et sans commit. Restent ouverts : les tuiles radio de l'équipe, le badge « admin permanent », et trois tables de couleurs pleines à unifier.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Refacto des cartes seules ; filtres, écrans secondaires, titres de repli et `DESIGN.md` hors scope (stories 017 à 019) | Tout le redesign livré dans la 016 | Demande de l'utilisateur (« regroupe tout dans la 16, enchaîne ») | `plan.md` titre, §Hors scope, §Extension du périmètre ; `metadata.json` titre |
| Caractérisation par un clic Playwright sur le visuel | Clic en `force: true` à la position du visuel | Le lien étendu recouvre le visuel : Playwright refuse un clic sur un élément recouvert ; vert sur anciens et nouveaux templates (implem) | `plan.md` §Stratégie de test |
| `motion.css` intégré tel quel | Apparition en `backwards`, ombre via `--tw-shadow`, écart de « Ouvrir » sur `.link-card-open` | Dans Tailwind 4, les utilitaires l'emportent sur `@layer components` ; `both` figeait `transform` et bloquait la montée au survol (implem) | `plan.md` §Mécanismes mobilisés |
| `relative z-10` sur le bouton de la façade | `z-10` sur le bloc `link-video`, badges en `z-10 pointer-events-none` | L'aperçu agrandi au survol crée un contexte d'empilement qui enfermait le `z-10` du bouton ; `RichCardTest` l'a détecté (implem) | `plan.md` §Approche retenue (structure d'interaction) |
| Pastilles de filtre avec l'état de chargement Live (spec §3.2) | Pastilles en liens GET (`team`, `type` en paramètres, catégorie par sa route) | URL partageable, catégorie gardée comme route, rien à synchroniser côté client (implem) | `plan.md` §Extension du périmètre |
| Contenu limité à 1 320 px (maquette) | Contenu pleine largeur | `RichCardTest` exige ≥ 5 colonnes à 2 560 px (implem) | `plan.md` §Comportement externe à préserver |
| Repli « sinon le domaine » (spec §6.3) | Repli `propriétaire/dépôt` pour GitHub en plus | Plus lisible que le domaine (implem) | `plan.md` §Extension du périmètre |
| Catégories listées comme dans la sidebar (masquées sans lien) | Catégories sans lien affichées à 40 % | Spec §4.1 : pastille à 0 à 40 % d'opacité (implem) | `plan.md` §Comportement externe à préserver |
| Enum du type de filtre | Nommé `FeedType` | Collision de nom avec le FormType `App\Form\LinkType` (implem) | `plan.md` §Périmètre |
| Compteurs dans `LinkRepository` | Compteurs dans `LinkFacetRepository` | Seuil Mago `too-many-methods` sur `LinkRepository` (implem) | `plan.md` §Périmètre |
| Visuels dépôt et post X conservés à l'identique hors couleurs | Ligne « github.com » du dépôt retirée, post X en `pt-12` | Laisser la place aux badges du haut de l'aperçu (implem) | — |
| Fonds de repli par type dans `_card_visual` | Clé `tint` de la table de thèmes, partagée avec la vignette mobile | Une seule source pour le repli et la vignette (implem) | — |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Critère « captures clair et sombre jointes à la review » | Captures faites en session (bureau et 390 px, clair et sombre), non conservées | Aucune — les teintes distinctes sont dans la table de thèmes ; refaire des captures si la review humaine les demande |
| Critère « chaque étape committée est déployable seule » | L'utilisateur a choisi « aucun commit » avant la fin | Aucune — un commit unique à `/forge:commit` |
| Équipe en tuiles radio sur « Nouveau compte » (spec §4.5) | Reporté : `AdminUserTest` choisit l'équipe dans le select | Dette n°2 |
| Badge « admin permanent » sur « Modifier un compte » (maquette) | Reporté : le contrôleur ne transmet pas l'information au template ; badge « admin » affiché | Dette n°3 |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| `FeedType::fromSourceType()` | Review [ARCHI] Correspondance FeedType ↔ SourceType implicite | — |
| Carte blanche sur les pages d'ajout et de reprise ouvertes hors modale | Review [STYLE] Pages pleine page sans fond de carte | — |
| Test `AdminTabsTest` et assertion « identifiants masqués hors dev » dans `SecurityControllerTest` | Couvrir les écrans restylés en parallèle (implem) | `plan.md` §Extension du périmètre |

## Dette technique identifiée

1. **Trois tables de couleurs pleines par type** — `templates/link/_card.html.twig` (`theme.solid`), `templates/components/LinkList.html.twig` (`swatches`), `templates/components/LinkForm.html.twig` (`detected_colors`) — à extraire dans une source unique (macro Twig ou fonction de l'extension) avant d'ajouter un quatrième usage.
2. **Équipe en tuiles radio sur « Nouveau compte »** — `templates/admin_user/new.html.twig` — rendre le champ en tuiles et adapter `tests/E2E/AdminUserTest.php` (aujourd'hui `selectOption('qa')`).
3. **Badge « admin permanent »** — `templates/admin_user/edit.html.twig` — transmettre depuis `AdminUserController` si le compte est un admin permanent, et l'afficher.
4. **Worker Messenger après `make db-reset`** — `Makefile` — le worker reste attaché à l'ancien fichier SQLite et les aperçus restent `pending` jusqu'à `make serve-d` (préexistant, point « Hors review »).

## Leçons apprises

- **Tailwind 4 : les utilitaires battent `@layer components`** : une classe de composant ne peut pas surcharger un `transition`, un `gap` ou une ombre posés par un utilitaire sur le même élément, et une animation en `fill-mode: both` fige `transform` au point de bloquer un survol ; poser ces propriétés d'un seul côté.
- **Lien étendu et `transform` au survol** : un `transform` crée un contexte d'empilement qui enferme les `z-index` de ses enfants ; ce qui doit passer au-dessus du lien étendu porte son `z-10` sur le bloc transformé lui-même.
- **Playwright et lien étendu** : un élément recouvert par le lien étendu n'est plus cliquable pour Playwright ; une caractérisation de « clic à cet endroit » passe par `force: true`.
- **Seuils Mago** : `too-many-methods` et `excessive-parameter-list` dessinent le code — un repository de compteurs à part, l'injection par constructeur quand un contrôleur gagne des paramètres de requête.

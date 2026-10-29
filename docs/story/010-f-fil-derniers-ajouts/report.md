# Report — Consulter le fil des derniers liens ajoutés

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/010-f-fil-derniers-ajouts/`
> **Amont** : `pitch.md` · `plan.md`

## Synthèse

- **Écarts** : 4 — 4 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 7 / 7 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 0 item.

Le fil et la pagination sont livrés comme prévu. Les écarts tiennent à l'API de pagination de Doctrine ORM 3.7 et à l'absence de regroupement natif dans Twig.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| `Paginator` Doctrine + value object `LinkPage` | `OffsetPaginator` + `WindowPage` de Doctrine, aucun value object maison | `Paginator` déprécié dans Doctrine ORM 3.7 (signalé par Mago) ; `WindowPage` porte déjà total et navigation (implem) | `plan.md` §Approche retenue, §Mécanismes mobilisés, §Alternatives écartées, §Périmètre |
| Regroupement par jour dans le template | Filtre Twig `by_day` | Twig n'a pas de filtre `group_by` (implem) | `plan.md` §Approche retenue, §Périmètre, §Stratégie de test |
| Tri des catégories d'une carte dans chaque requête | `#[ORM\OrderBy(['name' => 'ASC'])]` sur `Link::$categories` | Un tri sur la collection jointe perturbe la sous-requête de pagination (implem) | `plan.md` §Approche retenue, §Périmètre |
| Tests existants inchangés | Sélecteur `FIRST_CARD` dans `LinkWebTestCase` | Sur l'accueil groupé par jour, `[data-test="link-card"]:first-child` visait la première carte de chaque jour (implem) | `plan.md` §Périmètre |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Aucun | — | — |

## Dette technique identifiée

_(aucune)_

## Leçons apprises

- **Doctrine ORM 3.7 déprécie `Paginator`** : utiliser `OffsetPaginator` (sans état, partageable) et son `WindowPage`.

# Report — Être averti qu'un lien existe déjà dans Radar

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/004-f-doublon-lien/`
> **Amont** : `pitch.md` · `plan.md`

## Synthèse

- **Écarts** : 3 — 3 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 6 / 6 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 0 item.

Le comportement prévu est livré. Les écarts portent sur le découpage des services, imposé par le seuil de paramètres de Mago, et sur la migration que DBAL génère pour SQLite.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Nouveau service `UrlNormalizer` | `LinkUrlAnalyzer` réunit la normalisation et le type de source (`SourceTypeResolver` supprimé) | `LinkManager` aurait eu 7 dépendances pour un seuil Mago de 5 ; les deux services lisent une adresse de lien (implem) | `plan.md` §Approche retenue, §Périmètre, §Stratégie de test |
| `LinkManager` résout les catégories et persiste via l'`EntityManager` | `CategoryResolver` extrait, persistance par `LinkRepository::save()` | Même seuil de 5 paramètres (implem) | `plan.md` §Approche retenue, §Périmètre |
| Migration : ajout d'une colonne et d'un index | DBAL reconstruit la table `link` ; la clé étrangère `posted_by_id` passe de `ON DELETE RESTRICT` à `NO ACTION` | Comportement de la plateforme SQLite de DBAL (migration générée, non modifiable) ; effet identique sous SQLite (implem) | `plan.md` §Modèle de données |

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

- **Le seuil `excessive-parameter-list` de Mago est de 5** : un service qui orchestre (repository, horloge, bus, services métier) l'atteint vite ; le plan doit prévoir le découpage plutôt que l'implémentation le découvrir.
- **DBAL reconstruit les tables SQLite pour un index unique ajouté avec une colonne** : la migration échoue si la colonne par défaut n'est pas unique sur des lignes existantes ; vérifier le SQL généré sur une base peuplée.

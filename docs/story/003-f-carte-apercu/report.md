# Report — Juger un lien sans cliquer grâce à sa carte

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/003-f-carte-apercu/`
> **Amont** : `pitch.md` · `plan.md`

## Synthèse

- **Écarts** : 4 — 4 réalignés dans `plan.md`, 0 laissé tel quel.
- **Critères** : 7 / 7 atteints, cochés dans `pitch.md`.
- **Non livré** : aucun.
- **Dette** : 0 item.

L'architecture prévue est livrée telle quelle. Les écarts viennent de l'outillage : simulation HTTP de test resserrée sur le client d'aperçu, et découpages imposés par les seuils de Mago.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| `framework.http_client.mock_response_factory` en test | Le service `app.preview_http_client` devient un `MockHttpClient` en `when@test` | Le mock global interceptait les appels d'UX Icons à Iconify : toutes les pages à icône tombaient en 500 (implem) | `plan.md` §Mécanismes mobilisés, §Périmètre |
| Parser unique `LinkPreviewParser` | Lecture des balises extraite dans `HtmlMetadata` | Seuil `cyclomatic-complexity` de Mago (implem) | `plan.md` §Périmètre |
| Tests de carte dans `LinkControllerTest` | `LinkCardTest` + base `LinkWebTestCase` | Seuil `too-many-methods` de Mago (implem) | `plan.md` §Périmètre, §Stratégie de test |
| `mago.toml` inchangé | `too-many-properties` et `excessive-parameter-list` exclus de `src/Entity/` | `Link` porte son mapping d'aperçu ; même raison que l'exclusion de complexité déjà posée pour les entités (implem) | `plan.md` §Périmètre |

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

- **Un `mock_response_factory` global simule aussi les clients des bundles** : UX Icons passe par `http_client`. Simuler uniquement le service HTTP de la feature.
- **`make quality` ne fait pas échouer sur les findings du linter** (`mago lint --fix` corrige mais ne bloque pas) : relancer `make lint` avant de conclure une sous-tâche.

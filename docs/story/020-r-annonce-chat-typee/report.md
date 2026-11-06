# Report — Typer la carte d'annonce Google Chat : icône, couleur et action par type

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/020-r-annonce-chat-typee/`
> **Amont** : `plan.md` · `review.md`

## Synthèse

- **Écarts** : 8 — 6 réalignés dans `plan.md`, 2 laissés tels quels.
- **Critères** : 7 / 11 atteints, cochés dans `plan.md` §Critères de sortie.
- **Non livré** : 4 contrôles, aucun fonctionnel.
- **Dette** : 4 items, dont 1 critique.
- **Comportement préservé** : tests de caractérisation verts à chaque étape, 467 tests PHPUnit verts (implem, review). Le contenu de la carte change volontairement, voir `plan.md` §Comportement externe à préserver.

Les écarts viennent surtout des seuils Mago et de deux tests fonctionnels absents du plan. Reste à faire l'envoi réel sur un espace de test avant le déploiement.

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| Table `private const STYLES` | Méthode `style()` en `match` | L'analyse Mago signale l'accès par clé comme possiblement indéfini | `plan.md` §Forme cible, §Mécanismes mobilisés, §Alternatives écartées |
| Helpers `button()` et `url()` conservés dans la carte | Retirés, boutons écrits en place | Mago limite une classe à 10 méthodes | — |
| Tests de la carte dans une seule classe | 3 classes de test et une base abstraite | Même limite, sur la classe de test | `plan.md` §Périmètre, §Stratégie de test |
| Retirer les deux tests de bannière de `LinkAnnouncerTest` | Remplacés par `testAnnouncementWritesNoBanner` | Prouver qu'aucun bandeau n'est écrit | `plan.md` §Périmètre, §Stratégie de test |
| Un checkpoint et un commit par étape | Étapes 3 à 5 enchaînées dans le même working tree | Demande de l'utilisateur | — |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Envoi réel avec `BORDERLESS` sur un espace de test | Reporté : dépend de l'espace Chat de l'équipe | Dette n°1 |
| Relecture de l'envoi en thème clair et sombre | Reporté : dépend du même envoi | Dette n°1 |
| Contrôle de `https://radar.mustiere.fr/chat/icons/github.png` sans session | Reporté : dépend du déploiement | Dette n°2 |
| Chaque étape committée déployable seule | Non attesté à ce stade : les étapes 3 à 5 ne sont pas commitées | Aucune, à constater au commit |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| Adaptation de `LinkAnnouncementTest` et `SourceLinkTest` | Oubliés au périmètre du plan, ils attendaient l'ancienne carte | `plan.md` §Périmètre, §Clients identifiés |
| `testEscapesAHostileHostInTheTitleAndTheDomain` | Review [TEST] : échappement du domaine non couvert | `plan.md` §Stratégie de test |
| Retrait des icônes dans `docs/redesign/README.md` | Review [DOC] : README périmé après le déplacement | `plan.md` §Périmètre |

## Dette technique identifiée

1. **Envoi réel sur un espace de test** — payloads préparés pendant l'implémentation — vérifier `BORDERLESS`, la ligne `#17181C` en thème sombre et le rendu de `&lt;users/all&gt;` dans un contexte. **Critique** au déploiement : un refus de Chat enverrait toutes les annonces en file `failed`.
2. **Icônes en production** — `https://radar.mustiere.fr/chat/icons/github.png` — confirmer 200 en `image/png` sans session après le déploiement.
3. **E2E préexistant en échec** — `tests/E2E/CardOpenTest.php:52` — `testClickingTheCardBodyOpensTheLink` échoue aussi sur `6b3e464`, cause non identifiée.
4. **E2E ignorés** — 15 tests sur 53, raison non examinée.

## Leçons apprises

- **Seuils Mago** : 10 méthodes par classe et 5 paramètres par méthode ont dessiné la carte et ses tests. Un plan qui ajoute des méthodes privées à une classe proche du seuil doit prévoir le découpage.
- **Recherche des clients** : `grep -i annonc` a raté `LinkAnnouncementTest`. Chercher aussi par nom de classe et par chaîne assertée, ici « Posté par » et « Via ».
- **Mutation de l'échappement** : retirer chaque `safeText()` un à un a révélé un cas que la relecture et le plan croyaient couvert.

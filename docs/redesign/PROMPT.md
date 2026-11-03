# Prompt pour Claude Code

À coller dans une session Claude Code ouverte à la racine de `radar`.

---

On lance le redesign de Radar. Tout est dans `docs/redesign/` :

- `SPEC.md` : la spécification (intention, tokens, écran par écran, évolutions fonctionnelles, décisions ouvertes) ;
- `motion.css` : les animations à intégrer dans `assets/styles/app.css` ;
- `maquettes/*.html` : les sources des maquettes (format Design Component, non exécutables), à lire pour les valeurs exactes.

Commence par lire `SPEC.md`, `DESIGN.md`, `docs/vision.md`, puis `templates/base.html.twig`, `templates/components/LinkList.html.twig`, `templates/link/_card.html.twig` et la maquette `maquettes/liens.html`.

Ensuite, **avant d'écrire du code** :

1. Pose-moi les 6 questions de la section 7 de `SPEC.md`, une par une, avec ta recommandation pour chacune.
2. Propose un découpage en stories `forge`, par exemple :
   - **refacto** : restylage visuel sans changement de comportement (cartes typées, couleurs, animations, pages admin, connexion, modale d'ajout) ;
   - **feature** : filtres Équipe et Type en entonnoir dans `LinkList`, avec compteurs ;
   - **feature** : détection du type à la saisie et titres de repli lisibles.
3. Pour chaque story, suis le pipeline `forge` (`/forge:refactor-plan` ou `/forge:feature-pitch` → plan → implem), et attends ma validation entre chaque phase, comme le prévoit `CLAUDE.md`.

Contraintes :

- Garde les palettes `gray-*` (stone) existantes : les gris des maquettes ne sont que des approximations.
- Garde les `data-test` existants (tests E2E Playwright) ; ajoute-en pour les nouveaux éléments (filtres, badges).
- Toute la carte doit ouvrir le lien via `app_link_open`, qui compte les clics.
- `make lint`, `make phpunit` et `make playwright` doivent rester verts à chaque étape.
- Mets à jour `DESIGN.md` à la fin (couleurs par type, barre de filtres, abandon du panneau de détail).

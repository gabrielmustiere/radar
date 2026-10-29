---
name: Radar
inspiration: "Markit - Bookmark Manager (Marcos Costa, Dribbble) — https://dribbble.com/shots/27796580-Markit-Bookmark-Manager"
colors:
  primary: "#009689"   # teal-600
  neutral: "#FAFAF9"   # stone-50
  success: "#16A34A"
  warning: "#D97706"
  danger: "#DC2626"
  surface: "#FFFFFF"
  text: "#1C1917"      # stone-900
typography:
  family: "Inter"
  mono: "ui-monospace (police système)"
  weights: "400, 500, 600, 700"
rounded:
  control: 12px        # rounded-xl : champs, boutons, items de navigation
  card: 16px           # rounded-2xl : cartes, menus
  panel: 32px          # rounded-4xl : panneau de contenu, carte de connexion (rounded-3xl)
---

## Vue d'ensemble

Interface calme et lumineuse, très inspirée de Markit : fond blanc cassé chaud, une seule couleur d'accent (sarcelle), coins généreusement arrondis, bordures fines plutôt qu'ombres, et quelques illustrations dessinées à la main pour la personnalité.

Contrainte permanente : **simplicité**. On reprend le langage visuel de Markit, pas ses fonctionnalités (pas de « Read Later » ni de signets privés — anti-objectifs de `docs/vision.md`).

## Tokens

Définis dans `assets/styles/app.css` (`@theme`) :

- **Neutres** : la palette Tailwind `gray-*` pointe sur `stone-*`. Écrire `gray-*` dans les templates donne donc un gris chaud.
- **Accent** : `teal` — `teal-600` (boutons, actif), `teal-700` (texte d'accent), `teal-50/100` (fonds légers). Les tokens Flowbite `brand-*` pointent dessus, donc les composants Twig (`<twig:Button>`…) suivent.
- **Police** : Inter partout (`font-sans`, `font-body`, `font-display`), monospace système pour le code.
- **Mode sombre** : suit la préférence système (`prefers-color-scheme`), aucun bouton de bascule. Chaque écran porte ses variantes `dark:`.

## Layout

- Fond de page `gray-50`, sans bordure entre topbar et sidebar.
- Topbar : logo à gauche, recherche centrée (blanche, `rounded-xl`), avatar à droite.
- Sidebar : items `rounded-xl` ; l'item actif est une pastille blanche, texte `teal-700`, `shadow-xs`.
- Contenu : panneau blanc arrondi en haut à gauche (`md:rounded-tl-4xl`).
- En-tête de page : titre en gras, puce, compteur discret (« Tous les liens · 0 lien »).

## Illustrations

Open Doodles (CC0, https://www.opendoodles.com), dans `assets/icons/doodles/`, rendues via `<twig:ux:icon name="doodles:…">`. Le trait suit `currentColor`, l'accent est `teal-200` / `dark:teal-800`.
Usage réservé aux moments vides ou d'accueil (connexion, état vide) — jamais décoratif dans une liste.

## Écrans à venir (repères tirés de Markit)

- **Carte de lien** : enveloppe Markit (`rounded-2xl`, bordure fine), mais enrichie — aperçu, icône de la source, posteur, date (principe « une carte se lit sans clic »).
- **Ajout** : modale en deux temps — coller l'URL → chargement → titre / contexte facultatif / catégories.
- **Détail** : panneau latéral (aperçu, URL copiable, contexte, catégories, « Ouvrir le lien »).
- **Catégories** : listées dans la sidebar, à la place des « Collections » de Markit.

---
name: Radar
inspiration: "Markit - Bookmark Manager (Marcos Costa, Dribbble) — https://dribbble.com/shots/27796580-Markit-Bookmark-Manager"
colors:
  primary: "#1C1917"   # gray-900 (stone) — noir et blanc, sans accent
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
  panel: 24px          # rounded-3xl : modales, carte de connexion
---

## Vue d'ensemble

Interface calme et lumineuse, très inspirée de Markit : fond blanc cassé chaud, noir et blanc sans couleur dominante (la couleur est réservée aux plateformes des cartes), coins généreusement arrondis, bordures fines plutôt qu'ombres, et quelques illustrations dessinées à la main pour la personnalité.

Contrainte permanente : **simplicité**. On reprend le langage visuel de Markit, pas ses fonctionnalités (pas de « Read Later » ni de signets privés — anti-objectifs de `docs/vision.md`).

## Tokens

Définis dans `assets/styles/app.css` (`@theme`) :

- **Neutres** : la palette Tailwind `gray-*` pointe sur `stone-*`. Écrire `gray-*` dans les templates donne donc un gris chaud.
- **Accent** : aucun — noir et blanc. Bouton principal `gray-900` / texte blanc (inversé en sombre : `white` / `gray-900`), texte actif `gray-900` / `dark:white`, focus `gray-900` / `dark:gray-400`. Les tokens Flowbite `brand-*` pointent sur les gris, donc les composants Twig (`<twig:Button>`…) suivent.
- **Une couleur par type** (redesign « modèle 5 », `docs/redesign/SPEC.md` §3.1), partout où la carte se teinte : liseré, badge de type sur l'aperçu, liseré du contexte, « Ouvrir », fond de l'aperçu de repli et vignette mobile, carré de la pastille du filtre Type, badge du type détecté à l'ajout. Texte coloré en 600/700 sur blanc (contraste ≥ 4,5:1), blanc sur la couleur pleine.

  | Type | Couleur pleine | Liseré | Fond de repli |
  |---|---|---|---|
  | YouTube | `rose-600` | `rose-200` | `gray-900` |
  | GitHub | `violet-600` | `violet-200` | `violet-50` |
  | X | `sky-700` | `sky-200` | `sky-50` |
  | Source suivie (`link.source` non nul, quelle que soit la plateforme) | `orange-700` | `orange-200` | `orange-50` |
  | Article | `teal-700` | `teal-200` | `teal-50` |
  | Image | `gray-900` | `gray-200` | `gray-100` |

  Table de référence : `theme` dans `templates/link/_card.html.twig` (classes écrites en entier). Les pastilles de catégorie restent neutres (`gray-100`).
- **Mouvement** (`app.css`, §3.2 de la spec) : courbe `ease-radar`, ressort `ease-radar-spring` ; apparition décalée des cartes (`.link-card`, `--i`), survol en ressort, point « Nouveau » en `animate-radar-ping`, menu du compte `animate-radar-drop`, pastilles `.filter-chip`. Tout est neutralisé sous `prefers-reduced-motion`.
- **Police** : Inter partout (`font-sans`, `font-body`, `font-display`), monospace système pour le code.
- **Mode sombre** : suit la préférence système (`prefers-color-scheme`), aucun bouton de bascule. Chaque écran porte ses variantes `dark:`.

## Layout

- Fond de page `gray-50` ; topbar blanche avec une bordure basse : logo, recherche centrée (`gray-50`, `rounded-xl`), « Ajouter un lien », avatar. **Pas de sidebar.** Contenu pleine largeur (le fil ajoute des colonnes plutôt que d'étirer les cartes).
- **Barre de filtres** en tête du fil, dans une carte blanche : trois rangées de pastilles en entonnoir, Équipe › Catégorie › Type, chacune avec son compteur (les liens qu'on obtiendrait en la choisissant, les autres filtres restant actifs). Pastille active `gray-900` / blanc ; compteur à 0 : 40 % d'opacité. Les pastilles sont des liens (`team`, `type` en paramètres, catégorie par sa route).
- En-tête du fil : le chemin de l'entonnoir en titre (« Développement › Symfony › Dépôts GitHub », « Tous les liens » sans filtre), puce, compteur, « Réinitialiser » dès qu'un filtre est actif.
- Téléphone : logo et avatar, recherche pleine largeur dessous ; rangées de filtres qui défilent horizontalement (cibles de 36 px) ; cartes compactes (vignette 64 × 64 teintée, type · nouveau, titre et contexte sur 2 lignes, source · date) ; bouton flottant « Ajouter » de 60 px en bas à droite.
- Pages admin : onglets Sources · Comptes · Catégories en tête, contenu centré (`max-w-3xl` listes, `max-w-2xl` formulaires).

## Illustrations

Open Doodles (CC0, https://www.opendoodles.com), dans `assets/icons/doodles/`, rendues via `<twig:ux:icon name="doodles:…">`. Le trait suit `currentColor`, l'aplat est `gray-200` / `dark:gray-800`.
Usage réservé aux moments vides ou d'accueil (connexion, état vide) — jamais décoratif dans une liste.

## Écrans

- **Carte de lien** : enveloppe Markit (`rounded-2xl`, bordure fine), enrichie — aperçu, badge de type, badge « Nouveau » (posté il y a moins de 72 h), source, posteur, date (principe « une carte se lit sans clic »). **Toute la carte ouvre le lien** (lien du titre étendu, via `app_link_open`) ; seuls la façade YouTube et « Ouvrir » passent au-dessus. Sans aperçu, titre de repli lisible (`@compte sur X`, `@chaîne`, `propriétaire/dépôt`, sinon le domaine).
- **Ajout** : modale — adresse d'abord (type détecté affiché à la saisie), catégories (la choisie noire avec une coche), « Pourquoi c'est utile (facultatif) ».
- **Détail** : pas de panneau latéral — on ouvre le lien en un clic.
- **Catégories** : dans la barre de filtres du fil.

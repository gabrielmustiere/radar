# Redesign Radar — spécification (modèle 5, clair)

> Source : canvas de design « Radar — 4 directions de design », onglet **V3 · Modèle 5 clair — toutes les interfaces** (claude.ai, 3 nov. 2026).
> Maquettes de référence : `docs/redesign/maquettes/*.html`. Ce sont des sources de maquette (format Design Component, avec des `{{trous}}` et un bloc `<script type="text/x-dc">`), **pas des pages exécutables**. Lis-les pour les valeurs exactes (tailles, espacements, couleurs, animations) ; la structure HTML n'est pas à copier telle quelle.
> Ce document complète `DESIGN.md` sans le remplacer. Les points marqués **[DÉCISION]** sont à trancher avec Gabriel avant d'implémenter.

## 1. Intention

Le fil doit permettre, sans détour :

1. **voir d'un coup d'œil les derniers liens** — le haut de page montre les plus récents, les nouveautés sont signalées ;
2. **filtrer facilement**, en **entonnoir** : Équipe › Catégorie › Type, plus la recherche ;
3. **ouvrir le lien en un clic** — pas de vue intermédiaire. Toute la carte est cliquable et passe par `app_link_open`, qui compte déjà les ouvertures (story 009).

Les cartes typées par plateforme restent le cœur visuel : la couleur dit tout de suite ce qu'est le lien.

Conséquence sur `DESIGN.md` : le « Détail : panneau latéral » prévu dans « Écrans à venir » est **abandonné**, à retirer de `DESIGN.md`.

## 2. Ce qui ne change pas

- Neutres chauds : la palette `gray-*` pointe sur `stone-*` (`app.css`). Les maquettes utilisent des gris légèrement froids (`#F6F7F8`, `#17181C`, `#E3E5E9`…) : **garder les `gray-*` existants**, ce ne sont que des approximations.
- Inter partout, coins généreux (`rounded-xl` pour les contrôles, `rounded-2xl` pour les cartes), bordures fines plutôt qu'ombres.
- Bouton principal `gray-900` / texte blanc. Composants Twig existants (`<twig:Button>`, `<twig:Modal>`, `<twig:Badge>`…).
- Ajout de lien en **modale** via `link-modal-frame` : la maquette `ajouter.html` est dessinée en page, mais on garde la modale et on applique seulement le contenu et le style.
- Façade vidéo, masonry (`data-controller="masonry"`), pagination (`_pager`), view transitions, `data-test` existants : **ne pas casser les sélecteurs E2E**.

## 3. Tokens

### 3.1 Couleurs par type de carte

Une couleur par type, utilisée **partout où la carte se teinte** : liseré de la carte, badge de type sur l'aperçu, liseré du « pourquoi », bouton « Ouvrir », pastille du filtre Type, fond de l'aperçu de repli.

| Type (`SourceType` / règle) | Couleur pleine (badge, liseré du contexte, « Ouvrir ») | Liseré de carte (`ring`) | Fond de l'aperçu de repli | Changement vs aujourd'hui |
|---|---|---|---|---|
| YouTube | `rose-600` | `rose-200` | aperçu sombre, `stone-900` | inchangé (rose) |
| GitHub | `violet-600` | `violet-200` | `violet-50` | inchangé (violet) |
| X | `sky-700` | `sky-200` | `sky-50` | **était noir** (`gray-800`) **[DÉCISION 1]** |
| Source suivie (`link.source` non nul) | `orange-700` | `orange-200` | `orange-50` | inchangé (orange) |
| Article | `teal-700` | `teal-200` | `teal-50` | **était noir et blanc** **[DÉCISION 1]** |
| Image | neutre (`gray-900`) | `gray-200` | `gray-100` | inchangé |

Le texte en couleur sur fond blanc utilise la teinte **600/700**, pour un contraste d'au moins 4,5:1. Le texte du badge de type est blanc sur la couleur pleine.

> Note : dans les maquettes, la carte « Flux RSS » illustre les URL de flux ajoutées à la main (github.blog/feed, php.net/feed.atom). Dans l'app, **l'orange reste réservé aux liens publiés par une source suivie**, comme dans `_card.html.twig`. Une URL de flux ajoutée à la main reste un Article. Voir « Évolutions » §6.4.

### 3.2 Mouvement

Toutes les animations sont désactivées sous `prefers-reduced-motion: reduce`. Courbe par défaut : `cubic-bezier(.2,.7,.2,1)`. Courbe « ressort » pour les survols : `cubic-bezier(.3,1.3,.5,1)`.

| Élément | Effet | Durée |
|---|---|---|
| Carte (apparition) | fondu et montée de 12 px, décalé de 40 ms entre cartes (max ~10) | 500 ms |
| Carte (survol) | `translateY(-4px)` + ombre `0 18px 36px -20px rgb(23 24 28 / .35)` ; aperçu `scale(1.04)` ; l'espace entre « Ouvrir » et la flèche s'élargit | 280 ms / 600 ms |
| Badge « Nouveau » | point de la couleur du type avec onde (`ping`) | 1,8 s en boucle |
| Pastilles de filtre | `scale(.96)` au clic, bordure plus foncée au survol | 150 ms |
| Menu du compte | `scale(.96)→1` + fondu, origine en haut à droite | 180 ms |
| Modales | fond en fondu, fenêtre `translateY(10px) scale(.97)→1` | 200 / 280 ms |
| Champ URL (ajout) | badge du type détecté qui apparaît avec un léger rebond | 400 ms |
| Changement de filtre | réutiliser l'état de chargement Live existant (`data-loading`) ; pas d'animation supplémentaire | — |

## 4. Écrans

| Écran | Route | Template(s) | Maquette |
|---|---|---|---|
| Fil | `app_page` `/` | `page/index`, `components/LinkList`, `link/_card*.twig`, `base.html.twig` | `liens.html`, `mobile.html` |
| Catégorie | `app_category_show` | idem + `CategoryRenameForm` | `liens.html` (état avec catégorie choisie + renommage) |
| Ajouter un lien | `app_link_new` (modale) | `link/new`, `components/LinkForm`, `_category_picker` | `ajouter.html` |
| Reprendre de Chat | `/liens/reprendre` | `link/seed` | `reprendre.html` |
| Sources | `app_source_index` | `source/index`, `components/SourceForm` | `sources.html` |
| Extension | `app_extension_key` | `extension_key/index` | `extension.html` |
| Comptes | `app_admin_user_index` | `admin_user/index` | `comptes.html` |
| Nouveau compte | `/admin/comptes/nouveau` | `admin_user/new` | `nouveaucompte.html` |
| Modifier un compte | `/admin/comptes/{id}` | `admin_user/edit` | `modifiercompte.html` |
| Catégories (admin) | `app_admin_category_index` | `admin_category/index` | `categories.html` |
| Connexion | `app_login` | `security/login` | `connexion.html` |

### 4.1 Fil et catégorie (`liens.html`)

- **Barre de filtres** en tête de contenu, dans une carte blanche (`rounded-2xl`, bordure fine), trois rangées :
  1. **Équipe** : Toutes · Développement · Produit · QA · Management, avec un compteur par pastille ;
  2. **Catégorie** : Toutes + les catégories ; si une équipe est choisie, seulement les catégories de cette équipe ;
  3. **Type** : Tous · Vidéos · Dépôts GitHub · Posts X · Sources suivies · Articles, chaque pastille portant un carré de la couleur du type.
- Pastille active : fond `gray-900`, texte blanc. Une pastille dont le compteur vaut 0 s'affiche à 40 % d'opacité.
- **Compteurs en entonnoir** : le compteur d'une option compte les liens qui correspondraient si on la choisissait, avec les *autres* filtres actifs.
- **Titre** = le chemin de l'entonnoir (« Développement › Symfony › Dépôts GitHub »), « Tous les liens » sans filtre, suivi du compteur. Bouton « Réinitialiser » dès qu'un filtre ou une recherche est actif. Le crayon de renommage reste à côté du titre quand une catégorie est choisie (admin).
- **Cartes** : l'anatomie de `_card.html.twig` est conservée, avec ces changements :
  - **toute la carte est un lien** vers `app_link_open` (nouvel onglet) ; les liens de catégorie à l'intérieur sont retirés **[DÉCISION 3]** ;
  - **badge de type** sur l'aperçu, en haut à gauche (couleur pleine, texte blanc, capitales 11 px) ;
  - **badge « Nouveau »** en haut à droite **[DÉCISION 5]** ;
  - liseré du contexte et bouton « Ouvrir » dans la couleur du type ; pastilles de catégorie neutres (`gray-100`) ;
  - **titres lisibles quand l'aperçu manque** : « @symfony sur X » plutôt que `https://x.com/symfony`, « SymfonyTV, la chaîne » plutôt que l'URL d'une chaîne (§6.3).
- **Sidebar** : la maquette n'en a plus ; les catégories passent dans la barre de filtres **[DÉCISION 2]**.
- **États vides** : « Aucun lien pour ce filtre. » + bouton « Tout réafficher » ; conserver l'illustration Open Doodles pour le fil vraiment vide.

### 4.2 Mobile (`mobile.html`)

- Barre du haut : logo + avatar, recherche pleine largeur en dessous.
- Filtres : les trois rangées de pastilles défilent horizontalement (sans barre de défilement visible), avec des cibles de 36 px de haut minimum.
- Cartes compactes : vignette 64 × 64 teintée par type, type + « nouveau », titre sur 2 lignes, contexte sur 2 lignes avec liseré, source · date. Toute la carte est cliquable.
- Bouton flottant « Ajouter » (60 px, en bas à droite), qui remplace le bouton du haut.

### 4.3 Ajouter un lien (`ajouter.html`, dans la modale existante)

- **Adresse en premier**, puis catégories, puis « Pourquoi c'est utile (facultatif) ».
- **Type détecté à la saisie** : un badge à droite du champ URL (« Vidéo YouTube », « Dépôt GitHub »…) dans la couleur du type (§6.2).
- Pastilles de catégorie : la catégorie choisie est noire avec une coche ; on garde le champ « Filtrer ou créer… puis Entrée » et le bouton « + ».
- Pied : « Ajouter » (principal), « Annuler », et à droite « Reprendre un lien de Chat → ».

### 4.4 Reprendre un lien de Chat (`reprendre.html`)

Même formulaire, plus « Posté dans Chat le » et « Posté par » côte à côte. Un encart d'information bleu pâle explique : « Le lien garde sa date et son posteur d'origine, et n'est pas réannoncé dans Google Chat. » Le bouton principal « Reprendre » porte l'icône historique.

### 4.5 Pages admin (`sources.html`, `comptes.html`, `nouveaucompte.html`, `modifiercompte.html`, `categories.html`)

- **Onglets d'administration** (contrôle segmenté) en haut de chaque page : Sources · Comptes · Catégories **[DÉCISION 6]**. Le menu du compte garde ses liens.
- Contenu centré, `max-w-3xl` (880 px) pour les listes, `max-w-2xl` (640 px) pour les formulaires ; formulaires dans une carte blanche `rounded-[20px]` ; listes en lignes séparées dans une carte.
- **Sources** : icône RSS orange dans le titre, bouton « Suivre » orange. Chaque ligne affiche le nom, l'URL en monospace, les catégories, l'état « Actif » (point vert qui pulse) et le bouton « Relever ». La maquette montre aussi « Ne plus suivre », qui n'existe pas aujourd'hui (§6.6).
- **Comptes** : avatar à l'initiale, email, badges (équipe, « admin permanent », « Actif »), bouton « Modifier ».
- **Nouveau compte** : l'équipe se choisit par **boutons radio en tuiles** au lieu d'un `select`. La maquette ajoute un bouton « Générer » à côté du mot de passe provisoire (§6.6).
- **Modifier un compte** : en-tête avec avatar et badges, encart d'avertissement ambre (« Changer l'email ou le mot de passe déconnecte la personne… »), puis deux cartes, « Compte » et « Mot de passe ».
- **Catégories** : chaque ligne montre le nom (lien vers la catégorie), une barre de volume proportionnelle au nombre de liens, le `select` d'équipe et « Enregistrer ».

### 4.6 Extension (`extension.html`)

À gauche : titre « Ajoutez la page que vous lisez, sans quitter l'onglet. », le texte existant et une carte « Clé personnelle » (état sans clé + « Générer une clé »). À droite : une petite démo animée, en boucle de 4 s (menu contextuel → « Ajouter à Radar » → message « Ajouté à Radar · Article »). L'état « clé générée » n'est pas dessiné : garder l'existant en le restylant.

### 4.7 Connexion (`connexion.html`)

Carte en deux colonnes : à gauche le slogan et une pile de mini-cartes typées qui flottent (animation `translate` de 6 s, décalée) ; à droite le formulaire existant. **Ne plus afficher les identifiants de test en production** : les conditionner à l'environnement `dev`.

## 5. Mode sombre

**[DÉCISION 4]** Les maquettes sont en clair uniquement. Par défaut : appliquer le redesign en clair, puis décliner les variantes `dark:` en suivant la même logique de teintes (comme le fait déjà `_card.html.twig`), sauf si Gabriel décide de passer l'app en clair seul.

## 6. Évolutions fonctionnelles impliquées

À traiter comme des stories `forge` distinctes, chacune avec ses tests :

1. **Filtres Équipe et Type dans `LinkList`** : ajouter deux `LiveProp(writable: true, url: true, onUpdated: 'resetPage')`, `team` et `type`, à côté de la recherche et de la catégorie. Requêtes dans `LinkRepository` uniquement. Règle métier existante : **un lien appartient à une équipe si l'une de ses catégories a cette équipe**. Les compteurs en entonnoir demandent une requête d'agrégat par dimension.
2. **Détection du type à la saisie** dans `LinkForm` : réutiliser l'analyse d'URL existante (YouTube, GitHub, X) pour renvoyer un `SourceType` en live. Aucune requête réseau.
3. **Titre de repli lisible** quand l'aperçu est `unavailable` (`@handle sur X`, `@chaîne` pour YouTube, sinon le domaine), au lieu de l'URL brute.
4. **(optionnel)** Reconnaître une URL de flux RSS/Atom collée dans « Ajouter un lien » et proposer « Suivre cette source » (réservé aux admins). Ne rien faire tant que ce n'est pas validé.
5. **Carte entièrement cliquable** tout en gardant la façade vidéo et le comptage via `app_link_open` **[DÉCISION 3]**.
6. **Hors périmètre sauf validation** : « Ne plus suivre » une source, « Générer » un mot de passe provisoire.

## 7. Décisions à trancher avec Gabriel avant d'implémenter

1. **Couleurs X et Article** : X passe en bleu ciel et Article en turquoise (au lieu du noir et blanc actuel) ?
2. **Sidebar** : la supprimer au profit de la barre de filtres, ou garder la sidebar (avec « Mon équipe ») *et* la barre ?
3. **Clic sur la carte** : toute la carte ouvre le lien. Pour YouTube, garder la lecture dans la carte (façade, qui compte comme une ouverture) ou ouvrir YouTube ? Les pastilles de catégorie dans la carte doivent-elles rester des liens ?
4. **Mode sombre** : décliner le redesign en `dark:`, ou passer l'app en clair seul ?
5. **« Nouveau »** : selon quelle règle ? Posté depuis moins de 24 h, ou depuis la dernière visite (il faudrait alors la stocker) ?
6. **Onglets d'administration** en haut des pages admin : oui ou non ?

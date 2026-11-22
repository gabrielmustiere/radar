# Installer Radar comme une app sur son téléphone et y partager un lien depuis Android

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `forge/story/021-f-app-mobile-installable/`
> **Amont** : aucun

Un membre peut installer Radar sur l'écran d'accueil de son téléphone, Android ou iPhone, et l'ouvrir comme une app. Sur Android, il partage un lien vers Radar depuis n'importe quelle app (YouTube, X, navigateur…) aussi facilement qu'il le posterait dans Chat.

## Contexte

Radar s'utilise aujourd'hui dans un navigateur. Sur ordinateur, l'extension rend l'ajout plus rapide qu'un post Chat. Sur téléphone, il n'y a rien d'équivalent. Pour consulter, il faut retrouver l'adresse dans le navigateur. Pour ajouter une trouvaille repérée dans l'app YouTube ou X, il faut copier le lien, ouvrir le navigateur, aller sur Radar, ouvrir le formulaire et coller. Dans l'app d'origine, « Partager vers Google Chat » coûte deux gestes.

Une bonne part de la veille se découvre sur téléphone, dans les transports ou entre deux réunions. Tant que partager vers Chat reste plus simple que partager vers Radar depuis un téléphone, ces liens continuent de partir dans Chat, non classés : c'est exactement le problème que Radar existe pour résoudre.

## Alignement vision

- **Problème adressé** : les liens repérés sur téléphone finissent dans Chat, introuvables quelques jours plus tard. Leur donner une porte d'entrée mobile vers Radar les fait entrer classés.
- **Audience servie** : tous les membres (développeurs, producteurs, QA, managers), dans leur usage mobile : capter une trouvaille, parcourir le fil, retrouver un lien.
- **Principes respectés** : principe 1 (« ajouter un lien ne coûte pas plus qu'un post Chat »), appliqué au téléphone. Principe 4 : le lien entre par Radar, où il est classé, puis Radar l'annonce dans Chat. L'ajout par partage garde le choix des catégories, aucun champ obligatoire ne s'ajoute.
- **Hypothèse testée** : hypothèse 2, « les contributeurs migrent de Chat vers l'outil si l'ajout prend 15 secondes ou moins ». Elle est testée sur le canal mobile, grâce au comptage des ajouts par canal.
- **Impact North Star** : indirect. Plus de liens classés aujourd'hui, c'est plus de liens anciens à rouvrir demain. Consulter depuis l'écran d'accueil facilite aussi le parcours « retrouver pour décider » loin d'un ordinateur.

## Utilisateurs concernés

- **Membre sur Android** (connecté, téléphone Android) : installe l'app, la consulte en plein écran, et partage un lien vers Radar depuis n'importe quelle app.
- **Membre sur iPhone** (connecté, Safari sur iPhone) : installe l'app grâce à une page d'aide et la consulte en plein écran. Il ajoute un lien avec le bouton + de l'app. Le partage depuis une autre app arrive dans une story suivante.
- **Membre sur ordinateur** : Chrome ou Edge peuvent lui proposer d'installer Radar, sans aménagement particulier. L'extension reste son canal d'ajout.
- **Admin** : aucun changement propre à son rôle.
- **Porteur du projet** : peut compter, par mois, les ajouts venus du partage mobile, de l'extension et de Radar.

## User Stories

- En tant que **membre**, je veux installer Radar sur l'écran d'accueil de mon téléphone afin de l'ouvrir d'un geste, comme une app, sans chercher l'adresse.
- En tant que **membre**, je veux trouver dans mon menu une entrée « Installer l'app » qui m'explique ou déclenche l'installation afin de savoir que c'est possible et comment faire sur mon téléphone.
- En tant que **membre sur iPhone**, je veux une page qui me montre les gestes d'installation dans Safari afin d'installer Radar alors que l'iPhone ne le propose pas de lui-même.
- En tant que **membre**, je veux que l'app installée s'affiche correctement sous l'encoche et au-dessus de la barre d'accueil afin que la recherche et le bouton d'ajout restent accessibles.
- En tant que **membre sur Android**, je veux voir Radar dans le menu Partager de n'importe quelle app afin d'y envoyer un lien aussi vite que vers Chat.
- En tant que **membre sur Android**, je veux que le partage ouvre le formulaire d'ajout avec le lien déjà rempli afin de n'avoir plus qu'à choisir mes catégories.
- En tant que **membre sur Android**, je veux être prévenu quand ce que j'ai partagé ne contient aucun lien afin de comprendre pourquoi le formulaire est vide.
- En tant que **membre déconnecté**, je veux retrouver mon lien partagé dans le formulaire après m'être connecté afin de ne pas devoir recommencer.
- En tant que **membre**, je veux voir un écran clair quand l'app n'a pas de réseau afin de ne pas tomber sur une page d'erreur du navigateur.
- En tant que **membre**, je **ne veux pas** voir de contenu périmé ni celui d'un autre compte quand l'app est hors ligne, afin de ne jamais lire une donnée fausse.
- En tant que **porteur du projet**, je veux savoir combien de liens entrent chaque mois par le partage mobile, par l'extension et par Radar afin de vérifier que le téléphone devient une vraie porte d'entrée.

## Règles métier

1. **L'app installée est Radar** : mêmes écrans, mêmes droits, même obligation d'être connecté (R1). Rien n'est consultable sans connexion, ni dans l'app installée ni hors ligne.
2. **Un partage n'ajoute rien seul** : il ouvre le formulaire d'ajout prérempli. Le membre choisit une à trois catégories et valide (R4, R7). Aucun champ obligatoire ne s'ajoute (principe 1).
3. **Quel lien est retenu** : le lien partagé s'il est fourni explicitement. Sinon, le premier lien trouvé dans le texte partagé. Le reste du texte est ignoré et ne remplit pas le contexte.
4. **Partage sans lien** : le formulaire s'ouvre vide avec le message « Aucun lien trouvé dans le partage, collez l'URL ».
5. **Lien déjà présent** : la règle de doublon actuelle s'applique telle quelle. Le membre voit la carte existante et peut lui ajouter ses catégories, dans la limite de trois (C1.3, R6).
6. **Partage reçu sans être connecté** : après connexion, le membre arrive sur le formulaire d'ajout prérempli avec le lien partagé.
7. **Après l'ajout** : le membre revient sur le fil avec la confirmation « Lien ajouté », comme pour un ajout depuis Radar. Le lien est annoncé dans Chat comme tout ajout (R5).
8. **Hors ligne** : l'app affiche une page « hors ligne » qui invite à réessayer une fois le réseau revenu. Aucune page de contenu n'est conservée pour être relue hors ligne.
9. **Entrée « Installer l'app »** : elle figure dans le menu utilisateur partout où l'installation est possible (Android, ordinateur avec un navigateur compatible, iPhone ou iPad sous Safari). Elle déclenche l'invite d'installation du système quand il en propose une, et mène sinon à la page d'aide. Elle disparaît quand Radar est ouvert depuis l'app installée.
10. **Canal d'un ajout** : chaque nouvel ajout retient par où il est entré, parmi Radar, l'extension ou le partage mobile. Les liens repris de l'historique Chat et ceux publiés par une source suivie gardent leur propre origine, déjà distinguée. Les liens existants restent sans canal.
11. **Le canal ne se lit qu'en volumes** : il n'apparaît pas sur la carte et ne permet aucune vue par personne (R9).

## Critères d'acceptation

- [ ] Sur un téléphone Android, le navigateur propose d'installer Radar. Une fois installé, Radar s'ouvre depuis l'écran d'accueil, en plein écran, avec son icône et son nom.
- [ ] Sur un iPhone, en suivant la page d'aide, on ajoute Radar à l'écran d'accueil. Il s'ouvre en plein écran avec son icône et son nom.
- [ ] Dans l'app installée sur un iPhone à encoche, la barre de recherche n'est pas masquée par l'encoche et le bouton + n'est pas recouvert par la barre d'accueil.
- [x] Le menu utilisateur affiche « Installer l'app » dans le navigateur d'un téléphone. L'entrée n'apparaît plus quand Radar est ouvert depuis l'app installée.
- [ ] Sur Android, « Installer l'app » déclenche l'invite d'installation du système. Sur iPhone, l'entrée mène à la page d'aide.
- [ ] Sur Android, une fois l'app installée, Radar figure dans le menu Partager d'une autre app (navigateur, YouTube).
- [x] Partager un lien vers Radar ouvre le formulaire d'ajout avec ce lien dans le champ URL. Après le choix d'une catégorie et la validation, le lien est ajouté et annoncé dans Chat.
- [x] Partager un texte qui contient un lien parmi d'autres mots (par exemple « Titre de la vidéo https://… ») préremplit le premier lien du texte.
- [x] Partager un texte sans lien ouvre le formulaire vide avec le message « Aucun lien trouvé dans le partage, collez l'URL ».
- [x] Partager un lien déjà présent dans Radar affiche l'alerte de doublon habituelle et la carte existante.
- [x] Partager un lien alors qu'on est déconnecté mène à la connexion, puis au formulaire prérempli avec ce lien.
- [x] Sans réseau, l'app installée affiche la page « hors ligne » et aucun contenu de Radar.
- [x] Après un ajout par Radar, un ajout par l'extension et un ajout par partage mobile, on peut compter un ajout dans chacun des trois canaux sur le mois, sans que la carte affiche le canal.

## Hors scope

- **Publication sur le Play Store ou l'App Store, app native** : Radar reste un site installable. Pas de compte développeur, pas de distribution par les stores.
- **Partage depuis une autre app sur iPhone** : l'iPhone ne permet pas à un site installé d'apparaître dans le menu Partager. Le contournement par un Raccourci Apple fera l'objet d'une story suivante, dont la distribution est à prototyper.
- **Lecture hors ligne du contenu** : aucun fil ni carte n'est consultable sans réseau.
- **Notifications sur le téléphone** : Google Chat annonce déjà chaque ajout.
- **Bandeau ou invitation proactive à installer** : la découverte passe uniquement par le menu.
- **Écran des indicateurs mensuels** : le comptage par canal est enregistré, l'écran qui l'affiche relève de la story `indicateurs-mensuels` (C6.2).
- **Ajustements propres à l'app installée sur ordinateur** : l'installation y est possible mais ne reçoit ni test ni aménagement dédié.
- **Canal des liens existants** : aucune reprise rétroactive.

## Impacts transverses

- **Traduction / langues** : non. Les nouveaux libellés (« Installer l'app », page d'aide, page hors ligne, message de partage sans lien) sont en français, comme toute l'interface (R11).
- **Droits d'accès** : inchangé. Tout membre connecté peut installer l'app et partager. La connexion reste requise partout (R1).
- **Cloisonnement des données** : la page hors ligne n'affiche aucune donnée, pour qu'un téléphone partagé ou une session expirée ne montre jamais le contenu d'un autre compte.
- **Apparence / déclinaisons** : l'app installée suit la direction visuelle noir et blanc de Radar (icône, couleur de la barre système). Elle s'adapte à l'encoche et à la barre d'accueil.
- **Exposition à des tiers** : non. Le système du téléphone (menu Partager) transmet un lien à Radar, rien ne sort de Radar.
- **Emails / notifications** : non. L'annonce Chat d'un ajout par partage est la même que pour tout ajout.
- **Données existantes** : les liens déjà présents n'ont pas de canal et sont comptés à part. Aucune reprise.
- **Comportement par défaut** : un membre qui n'installe rien continue d'utiliser Radar dans son navigateur exactement comme aujourd'hui. Seule l'entrée « Installer l'app » apparaît dans son menu.

## Questions ouvertes

- **Reconnexion dans l'app iPhone** : sur iPhone, l'app installée ne partage pas la connexion de Safari, il faut se connecter une fois dans l'app. Faut-il le dire sur la page d'aide ? Options : (a) une phrase sur la page d'aide, (b) rien. Recommandé : (a), ça évite la surprise « je suis déjà connecté pourtant ». La case « Se souvenir de moi » limite ensuite les reconnexions. → tranché : (a), au plan.
- **Canal d'un ajout fait depuis le formulaire d'un lien partagé puis modifié** : si le membre remplace l'URL préremplie par une autre avant de valider, l'ajout compte-t-il comme partage mobile ? Options : (a) oui, le canal est celui par lequel le formulaire a été ouvert, (b) non, il compte comme ajout depuis Radar. Recommandé : (a), c'est le partage qui a déclenché l'ajout. → tranché : (a), au plan (le canal suit le formulaire ouvert par le partage).

---

## Annexe — Pistes pour le plan

- Manifeste web (`name`, icônes 192/512 et maskable à partir de `public/logo.png`, `display: standalone`, `theme_color`, `start_url`) et `share_target` en GET vers `/liens/ajouter` (paramètres `url`, `text`, `title`) : beaucoup d'apps Android mettent l'URL dans `text`.
- `LinkController::new` ne lit pas de paramètre de requête aujourd'hui. Le préremplissage doit cohabiter avec le Live Component `LinkForm` et la modale Turbo Frame.
- Service worker servi à la racine (`/sw.js`, fichier `public/` ou route dédiée) alors qu'AssetMapper hashe les noms : il met en cache les assets versionnés et la page hors ligne, jamais le HTML authentifié.
- `templates/base.html.twig` : `viewport-fit=cover`, `env(safe-area-inset-*)` sur la topbar `sticky top-0` et le bouton flottant `bottom-4`, `apple-touch-icon`, `theme-color`. L'icône actuelle est un SVG en data URI.
- Entrée de menu : `beforeinstallprompt` sur Android et ordinateur, détection de `display-mode: standalone` pour la masquer. La page d'aide iOS peut suivre le modèle de `app_extension_key`.
- Canal d'entrée : nouvelle colonne sur `Link` (enum backed string dans `src/Enum/Type/`, migration via `make:migration`). Il faut transporter le canal entre l'ouverture du formulaire par partage et sa soumission. L'API de l'extension (`src/State/`) pose le canal « extension ».
- Redirection après connexion : vérifier que `form_login` garde la query string de `/liens/ajouter?url=…` dans le chemin cible.
- Vérification : audit Lighthouse d'installabilité, test E2E Playwright sur le manifeste et le préremplissage, test unitaire de l'extraction d'URL depuis `text`.

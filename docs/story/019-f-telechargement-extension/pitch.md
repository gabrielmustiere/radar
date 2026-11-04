# Télécharger et installer l'extension depuis Radar, avec une FAQ sur la même page

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/019-f-telechargement-extension/`
> **Amont** : aucun

La page « Extension navigateur » de Radar devient le parcours complet d'équipement : un membre y télécharge l'extension, suit la procédure d'installation, génère sa clé et trouve une FAQ. La bulle de l'extension le prévient quand une nouvelle version est disponible, parce que les mises à jour ne sont plus automatiques hors du Chrome Web Store.

## Contexte

L'extension est livrée depuis la v0.9.0, mais aucun membre ne peut l'installer par lui-même : la publication sur le Chrome Web Store prévue par la story 014 n'a jamais eu lieu, faute de compte développeur. La page « Extension navigateur » permet de générer une clé pour une extension que personne ne peut se procurer, et ne dit rien de l'installation. Le paquet de production n'existe que sur le poste de celui qui le construit.

Si on ne fait rien, l'extension reste inaccessible : l'ajout continue de passer par le détour dans Radar, et une partie des liens reste postée directement dans Chat, ce qui est l'un des deux signaux d'arrêt de la vision.

Le téléchargement a un coût assumé. Sans le Web Store, le navigateur n'accepte pas d'installation en un clic : il faut décompresser le fichier, activer le mode développeur et charger le dossier, et les mises à jour deviennent manuelles. Le porteur confirme que le mode développeur est autorisé sur les postes de l'organisation.

## Alignement vision

- **Problème adressé** : faire entrer la veille dans Radar plutôt que dans Chat, en rendant enfin disponible le moyen d'ajout le plus rapide.
- **Audience servie** : tous les contributeurs, au moment de s'équiper. La FAQ et la procédure pas à pas visent surtout les non-développeurs (producteurs, QA, managers), pour qui le mode développeur n'est pas un geste familier.
- **Principes respectés** : principe 1 (une fois installée, l'extension est le chemin d'ajout le moins coûteux ; l'alerte de version ne ralentit pas l'ajout) ; principe 4 (Radar reste la porte d'entrée). Tension assumée sur le seuil à 1 an, « au moins 3 contributeurs non-développeurs » : l'installation par le mode développeur peut les freiner, d'où la procédure, la FAQ et le maintien du Web Store comme cible.
- **Hypothèse testée** : hypothèse 2 (les contributeurs migrent de Chat vers l'outil si l'ajout prend 15 secondes ou moins), qui ne pouvait pas l'être tant que l'extension n'était pas distribuée.
- **Impact North Star** : indirect — plus de liens classés aujourd'hui, plus de liens anciens rouverts dans plusieurs mois.

## Utilisateurs concernés

- **Membre connecté** (mêmes droits pour tous, R2) — télécharge l'extension, l'installe en suivant la procédure, consulte la FAQ, met à jour son extension quand la bulle le lui signale.
- **Admin** — aucun droit particulier sur la page, ni sur le contenu de la FAQ.
- **Porteur du projet** — annonce la disponibilité de l'extension à la main dans Chat au lancement ; publie les nouvelles versions avec le déploiement de Radar.
- **Visiteur non connecté** — aucun changement (R1) : ni téléchargement, ni procédure, ni FAQ.
- **Membres de l'espace Google Chat** — aucun changement perçu : les annonces de liens sont inchangées.

## User Stories

- En tant que **membre**, je veux télécharger l'extension depuis la page « Extension navigateur » afin de m'équiper sans demander le fichier à quelqu'un.
- En tant que **membre**, je veux suivre une procédure d'installation pas à pas sur cette même page afin d'installer l'extension sans être développeur.
- En tant que **membre**, je veux voir la version disponible au téléchargement afin de savoir si mon poste est à jour.
- En tant que **membre**, je veux trouver sur la page les réponses aux questions courantes sur l'installation, les mises à jour et la clé afin de me débrouiller seul.
- En tant que **membre dont l'extension est en retard**, je veux être prévenu dans la bulle afin de la mettre à jour sans surveiller Radar.
- En tant que **membre dont l'extension est en retard**, je ne veux PAS être empêché de partager ma trouvaille afin que la mise à jour ne casse pas ma lecture.
- En tant que **membre**, je veux mettre à jour l'extension sans perdre ma clé afin de ne pas avoir à la régénérer et à couper mes autres postes.
- En tant que **membre bloqué**, je veux savoir où demander de l'aide afin de ne pas abandonner l'installation.

## Règles métier

1. **Canal d'installation** : pour l'instant, le téléchargement depuis Radar est le seul moyen d'obtenir l'extension. Il remplace provisoirement la règle 2 de la story 014 ; la publication sur le Chrome Web Store reste la cible, dans une story ultérieure.
2. **Une seule page** : tout vit sur la page « Extension navigateur » existante, accessible depuis le menu du membre, dans l'ordre du parcours : télécharger, installer, générer sa clé, FAQ. La gestion de la clé garde ses règles actuelles (règles 7 à 13 de la story 014).
3. **Toujours visible** : le téléchargement, la procédure et la FAQ s'affichent pour tout membre connecté, qu'il ait déjà une clé ou non.
4. **Accès** : comme tout Radar (R1), la page et le fichier ne sont accessibles qu'aux membres connectés.
5. **Version unique** : seule la dernière version est téléchargeable, sans archive des précédentes. Le numéro affiché sur la page est toujours celui du fichier proposé, qui vise l'instance de production de Radar.
6. **Procédure d'installation** : elle décrit chaque geste, de la décompression du fichier au collage de la clé dans l'extension, et précise que le dossier décompressé doit rester en place sur le poste.
7. **Navigateurs** : les mêmes que la story 014, Chrome et les navigateurs qui acceptent ses extensions (Edge, Brave, Arc). Radar ne détecte pas le navigateur : la page indique ceux qui sont compatibles.
8. **Alerte de nouvelle version** : quand une version plus récente que celle du poste est disponible, la bulle affiche une ligne discrète « Nouvelle version disponible » avec un lien vers la page « Extension navigateur ». L'alerte s'affiche à chaque ouverture tant que le poste n'est pas à jour et ne se masque pas.
9. **Alerte non bloquante** : le formulaire d'ajout reste entièrement utilisable, et aucun champ ni clic n'est ajouté au parcours.
10. **Conditions de l'alerte** : elle ne s'affiche qu'avec une clé valide ; l'écran de clé absente, révoquée ou expirée reste inchangé. Si la version disponible ne peut pas être connue, aucune alerte n'apparaît et l'ajout fonctionne normalement.
11. **Portée de la clé** : connaître la version disponible n'élargit pas la portée de la clé, qui ne donne toujours accès à aucun contenu de Radar (règle 10 de la story 014).
12. **Présente dès la première diffusion** : l'alerte fait partie de la première version téléchargeable, sinon les postes équipés ne pourraient pas la recevoir.
13. **Mise à jour sans perte de clé** : la procédure de mise à jour décrite dans la FAQ conserve la clé enregistrée sur le poste. Le membre n'a ni à la recoller ni à la régénérer.
14. **Contenu de la FAQ** : un texte fixe, identique pour tous, qui évolue avec les déploiements de Radar et n'est pas modifiable depuis l'interface. Il couvre trois thèmes :
    - **Installation** : navigateurs compatibles, raison du mode développeur, sûreté de ce mode, que faire s'il est bloqué sur un poste.
    - **Mises à jour** : reconnaître une nouvelle version, mettre à jour sans perdre sa clé, raison de l'absence de mise à jour automatique.
    - **Clé personnelle** : clé perdue ou non copiée, clé expirée après 90 jours, équipement d'un second poste, révocation après la perte d'un poste.
15. **Aide** : un membre en difficulté est orienté vers l'espace Google Chat de la veille.

## Critères d'acceptation

- [x] Un membre connecté voit sur la page « Extension navigateur », avec ou sans clé, le bouton de téléchargement, la version disponible, la procédure d'installation et la FAQ.
- [x] Le bouton télécharge le fichier de la version affichée.
- [x] Un visiteur non connecté ne peut obtenir ni la page, ni le fichier, y compris avec son adresse directe.
- [ ] En suivant uniquement la procédure de la page, une personne installe l'extension téléchargée sur Chrome et sur Edge, y colle sa clé et ajoute un lien à l'instance de production (recette manuelle, condition de livraison).
- [ ] Sur l'extension téléchargée, l'icône ouvre la bulle avec l'adresse de la page préremplie, et le clic droit « Ajouter cette page à Radar » mène au même formulaire, sur Chrome et sur Edge (recette manuelle, condition de livraison).
- [ ] Un ajout depuis l'icône (contexte vide, une catégorie existante, adresse préremplie) se fait en moins de 15 secondes (recette manuelle, condition de livraison).
- [x] Avec une clé valide et une extension en retard, la bulle affiche « Nouvelle version disponible » avec un lien vers la page, et le membre peut quand même ajouter un lien.
- [x] Avec une extension à jour, aucune alerte n'apparaît.
- [x] Avec une clé absente, révoquée ou expirée, la bulle affiche l'écran de clé habituel, sans alerte.
- [x] Quand la version disponible ne peut pas être connue, aucune alerte n'apparaît et l'ajout fonctionne.
- [ ] En suivant la procédure de mise à jour de la FAQ, un membre passe à la nouvelle version et ajoute un lien sans recoller ni régénérer sa clé, sur Chrome et sur Edge (recette manuelle, condition de livraison).
- [x] La FAQ répond aux trois thèmes (installation, mises à jour, clé personnelle) et oriente vers l'espace Google Chat de la veille pour obtenir de l'aide.
- [x] La page reste lisible en clair, en sombre et sur mobile.

## Hors scope

- **Publication sur le Chrome Web Store** : reportée à une story ultérieure, avec le choix du compte développeur (dette n°4 de la story 014).
- **Installation en un clic ou imposée par l'administrateur Workspace** : impossible sans le Web Store pour le premier cas, non retenue pour le second.
- **Mise à jour automatique** : indisponible sans le Web Store ; l'alerte de la bulle en tient lieu.
- **Mise à jour obligatoire** : l'alerte n'empêche jamais un ajout.
- **Détection de la version installée par la page de Radar** : seule la bulle compare les versions.
- **Détection du navigateur** : pas d'adaptation à Firefox, Safari ou au mobile, qui restent non couverts.
- **Anciennes versions téléchargeables** : pas d'archive.
- **FAQ modifiable depuis l'interface** : pas d'écran d'administration.
- **Thème « données et confidentialité » dans la FAQ** : écarté au lancement ; la page de confidentialité publique existe déjà.
- **FAQ sur le reste de Radar** : la FAQ ne couvre que l'extension.
- **Mesure des téléchargements** : pas de compteur ; l'usage se juge par les retours et le nombre de clés générées, comme pour la story 014.
- **Annonce automatique du lancement ou des nouvelles versions dans Chat** : le lancement s'annonce à la main.

## Impacts transverses

- **Traduction / langues** : oui, français uniquement (R11) — boutons, procédure, FAQ et alerte de la bulle.
- **Droits d'accès** : inchangé — tout membre connecté, sans nouveau rôle ; la clé ne gagne aucun accès au contenu de Radar.
- **Cloisonnement des données** : non — le fichier et la FAQ sont identiques pour tous, et la clé reste visible de son seul propriétaire.
- **Apparence / déclinaisons** : la page garde l'identité noir et blanc de Radar, en clair et en sombre, et l'alerte suit celle de la bulle.
- **Exposition à des tiers** : non — le fichier reste réservé aux membres, sans revue Google tant que le Web Store n'est pas utilisé.
- **Emails / notifications** : non — seule l'alerte de la bulle signale une nouvelle version ; l'annonce de lancement est un message manuel, hors produit.
- **Données existantes** : aucune reprise — les clés déjà générées restent valables.
- **Comportement par défaut** : rien ne change pour un membre qui n'installe pas l'extension, hormis le contenu supplémentaire de la page.

## Questions ouvertes

- **Rattachement au backlog** : la fonctionnalité ne correspond à aucune ligne de `docs/product-backlog.md`. Options : (a) l'ajouter via `/forge:product-backlog` avant le plan, (b) au `/forge:sync` de clôture. Recommandé : (b), comme pour la story 014. → tranché : (b), capacité C1.10 et ligne `telechargement-extension` ajoutées au backlog au `/forge:sync`.
- **Geste de mise à jour** : le navigateur peut considérer un dossier différent comme une autre extension, et désinstaller efface la clé. Options : (a) remplacer le contenu du dossier existant puis recharger l'extension, (b) charger le nouveau dossier en conservant l'identité de l'extension. Recommandé : (a) par défaut, à prototyper au plan sur Chrome et Edge avant d'écrire la FAQ. → tranché : (a) comme geste principal de la FAQ, et l'identité reste fixe dans le zip pour qu'un chargement depuis un autre dossier conserve aussi la clé (prototype du plan, à confirmer en recette).

---

## Annexe — Pistes pour le plan

- **Production du paquet** : `make extension` construit le zip à la main dans `var/extension/`, exclu de Git et de l'image Docker (`.dockerignore`). L'image n'installe que `unzip`, ni `zip` ni l'extension PHP `zip` : à construire au build de l'image ou à la demande, à trancher.
- **Service du fichier** : le servir derrière le pare-feu (contrôleur et route sous `/extension`), pas depuis `public/`, servi sans Symfony : ce serait un contournement de R1.
- **Source de la version** : `extension/manifest.json` (actuellement `0.1.1`) porte la version, à incrémenter à chaque évolution pour que l'alerte se déclenche.
- **Alerte** : `popup.js` appelle déjà l'API `/api/extension` à l'ouverture. Faire renvoyer la version disponible par Radar et comparer dans la bulle n'envoie aucune donnée nouvelle ; sinon, `public/confidentialite-extension.html` (« Seulement […] quand vous ajoutez un lien ») est à revoir.
- **Conservation de la clé** : stockée dans `chrome.storage.local`, liée à l'identité de l'extension, qui dépend du chemin pour une extension non empaquetée sauf `key` dans le manifest. Le nom actuel `radar-extension-<version>` crée un dossier par version : prévoir un nom stable à prototyper.
- **Tests** : la bulle se teste en `file://` avec une API `chrome` simulée (`tests/E2E/ExtensionTestCase.php`), à compléter pour simuler la version du poste. Installation, icône, clic droit et mise à jour restent en recette manuelle.
- **Mise en page** : `docs/redesign/SPEC.md` §4.6 ne dessine que l'en-tête, la carte de clé et la démo animée. La FAQ peut s'appuyer sur `<details>` natif ; le lien de téléchargement doit échapper à Turbo Drive.
- **Dette n°1 de la story 014** : la diffusion à tous rend critique le réglage de `SYMFONY_TRUSTED_PROXIES`, sinon 10 clés refusées bloquent l'extension de tous les membres pendant 15 minutes. La variable est renseignée dans `environments/prod/.env` local ; sa valeur sur le serveur reste à confirmer avant l'ouverture.
- **Documentation** : `docs/stack.md` et la cible `extension` du `Makefile` décrivent encore le Web Store comme destination du zip.

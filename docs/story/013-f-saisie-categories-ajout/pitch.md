# Créer et choisir jusqu'à trois catégories au fil de la saisie

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/013-f-saisie-categories-ajout/`
> **Amont** : aucun

Dans le formulaire d'ajout, un membre tape le nom d'une catégorie puis Entrée : elle est créée pour toute l'équipe, ajoutée à la liste et sélectionnée d'un coup, autant de fois qu'il le faut, dans la limite de trois catégories par lien. Le formulaire s'ouvre sur le contexte, puis les catégories, puis l'adresse ; et un lien déjà présent peut être complété avec les catégories choisies au lieu d'être simplement refusé.

## Contexte

Aujourd'hui, le formulaire d'ajout présente l'adresse, puis les catégories existantes en pastilles à cocher, puis un unique champ « Ou créer une catégorie », puis le contexte. On ne peut créer qu'une seule catégorie par ajout, et un lien porte au plus deux catégories : dès que la limite est atteinte, les autres pastilles se grisent. Quand un lien touche plusieurs besoins dont aucun n'existe encore, le membre doit choisir lequel créer et renoncer aux autres — ou s'y reprendre à plusieurs fois, ce qui n'existe pas puisqu'on ne reclasse pas encore un lien.

Si on ne fait rien, les liens restent sous-classés : ils manquent dans les catégories où un collègue les chercherait des mois plus tard, alors que retrouver par besoin est la raison d'être de Radar. Le geste de création, lui, reste lourd (un champ à part, non relié aux pastilles), à rebours de la promesse d'un ajout aussi rapide qu'un post Chat.

## Alignement vision

- **Problème adressé** : mieux classer chaque lien pour qu'il soit retrouvé par tous les besoins qu'il sert, pas seulement par le premier qui vient à l'esprit du posteur.
- **Audience servie** : tous les contributeurs au moment de l'ajout, et par ricochet le développeur qui cherche par besoin.
- **Principes respectés** : principe 1 (aucun champ obligatoire ajouté, création en une frappe), principe 3 (la majuscule initiale fait lire chaque catégorie comme une phrase-besoin). **Principe 5 mis sous tension, assumé** : « une ou deux catégories » devient « une à trois » — édition ciblée de la vision à faire via `/vision` (voir `## Questions ouvertes`), dans le même esprit d'un classement concentré, sans éparpillement.
- **Hypothèse testée** : hypothèse 2 (migration de Chat si l'ajout reste sous 15 secondes) ; met aussi sous pression l'hypothèse 3 (catégories doublons), puisque créer devient plus facile.
- **Impact North Star** : indirect — un lien présent dans plus de catégories pertinentes a plus de chances d'être rouvert après 30 jours.

## Utilisateurs concernés

- **Membre connecté** (mêmes droits pour tous, R2) — nouveau formulaire d'ajout : contexte, catégories, adresse ; création de catégories à la frappe ; jusqu'à trois catégories ; peut compléter les catégories d'un lien déjà présent.
- **Contributeur qui reprend un lien de Chat** — même formulaire et mêmes règles que l'ajout, avec en plus la date et le posteur d'origine.
- **Admin des sources** — choisit jusqu'à trois catégories pour une source et les crée de la même façon.
- **Visiteur non connecté** — aucun changement (R1).

## User Stories

- En tant que **membre**, je veux taper le nom d'une catégorie et appuyer sur Entrée pour qu'elle soit créée et sélectionnée aussitôt, afin de classer mon lien sans quitter le clavier.
- En tant que **membre**, je veux créer plusieurs catégories au cours d'un même ajout, afin de couvrir tous les besoins qu'un lien sert.
- En tant que **membre**, je veux voir les catégories existantes filtrées au fil de ma frappe et retrouver la bonne d'un Entrée, afin de ne pas créer un doublon par mégarde.
- En tant que **membre**, je veux commencer par dire pourquoi le lien est utile, puis le classer, puis coller son adresse, afin de suivre l'ordre dans lequel je pense ma trouvaille.
- En tant que **membre**, je veux, quand le lien existe déjà, ajouter mes catégories à celles du lien existant, afin que mon travail de classement ne soit pas perdu.
- En tant que **membre**, je ne veux PAS pouvoir retenir plus de trois catégories pour un lien, afin que le classement reste concentré.
- En tant qu'**admin**, je veux créer et choisir les catégories d'une source de la même façon, jusqu'à trois, afin que les liens des sources soient classés comme ceux des membres.

## Règles métier

1. **Ordre du formulaire d'ajout** : contexte (facultatif), puis catégories, puis adresse. Le curseur est placé dans le contexte à l'ouverture. Seuls l'adresse et au moins une catégorie restent obligatoires (R7).
2. **Plafond** : un lien porte **une à trois** catégories (R4 révisée). La même limite s'applique aux sources.
3. **Saisie d'une catégorie** : les catégories existantes restent affichées en pastilles cliquables ; le champ de saisie les filtre au fil de la frappe.
4. **Entrée dans le champ de saisie** ne soumet jamais le formulaire. Si le nom saisi correspond à une catégorie existante (casse et espaces en bord ignorés), cette catégorie est sélectionnée ; sinon une nouvelle catégorie est créée.
5. **Création immédiate** : la catégorie créée existe aussitôt pour toute l'équipe, même si elle est désélectionnée ensuite ou si l'ajout est abandonné. Une catégorie sans lien n'apparaît pas dans la navigation par catégorie (comportement actuel inchangé).
6. **Création au-delà du plafond** : si trois catégories sont déjà sélectionnées, la catégorie est quand même créée et ajoutée aux pastilles, mais non sélectionnée ; un message indique d'en désélectionner une pour la retenir.
7. **Nom d'une catégorie créée** : 2 à 60 caractères, espaces en bord retirés. Sa **première lettre est mise en majuscule**, le reste est conservé tel quel (« tester une app mobile » → « Tester une app mobile » ; « iOS » et « API » ne sont pas altérés au-delà de la première lettre). Un nom invalide n'est pas créé et un message l'explique.
8. **Catégories existantes** : la majuscule initiale ne s'applique qu'aux catégories créées après la livraison ; les noms déjà en base restent tels quels, y compris quand une saisie les réutilise.
9. **Doublon de lien** : quand l'adresse saisie existe déjà, l'ajout reste bloqué et l'alerte renvoie vers la carte existante (comportement actuel). L'alerte propose en plus d'ajouter au lien existant les catégories sélectionnées qu'il n'a pas encore, dans la limite de la place restante (trois moins ses catégories actuelles). Le membre choisit lesquelles en cochant ou décochant les pastilles du formulaire : si elles ne tiennent pas, l'alerte indique combien en décocher et ne propose pas l'ajout. Si quelqu'un complète le lien entre-temps, l'alerte se réaffiche à jour avec un message. Aucune catégorie n'est jamais retirée du lien existant.
10. **Lien existant déjà complet** : s'il porte déjà trois catégories, ou si toutes les catégories sélectionnées y sont déjà, aucune proposition n'apparaît ; seule l'alerte reste.
11. **Contexte et doublon** : le contexte saisi n'est jamais reporté sur le lien existant (R12 : seul le posteur corrige son lien) ; l'alerte le précise.
12. **Complément de catégories** : tout membre peut compléter un lien existant par ce chemin (R2), quel que soit son posteur, y compris un lien de source. Ce complément n'est pas annoncé dans Google Chat (R5).

## Critères d'acceptation

- [x] À l'ouverture du formulaire d'ajout, les champs apparaissent dans l'ordre contexte, catégories, adresse, et le curseur est dans le contexte.
- [x] Taper un nouveau nom puis Entrée crée la catégorie, l'ajoute aux pastilles et la sélectionne, sans soumettre le formulaire.
- [x] Un membre crée deux nouvelles catégories et en coche une existante au cours d'un même ajout ; le lien est enregistré avec ces trois catégories.
- [x] Taper le nom d'une catégorie existante dans une autre casse puis Entrée sélectionne la catégorie existante, sans en créer une seconde.
- [x] Les pastilles sont filtrées au fil de la frappe dans le champ de saisie.
- [x] Une catégorie créée sous la forme « tester une app mobile » apparaît « Tester une app mobile » ; une catégorie existante en minuscule garde son nom.
- [x] Avec trois catégories sélectionnées, une nouvelle saisie suivie d'Entrée crée la catégorie sans la sélectionner, et un message invite à en désélectionner une.
- [x] Il est impossible de valider un lien avec quatre catégories ou sans catégorie ; le message est clair et en français.
- [x] Une catégorie créée puis désélectionnée, ou créée dans un ajout abandonné, est proposée au prochain ajout mais n'apparaît pas dans la navigation par catégorie.
- [x] Un nom de moins de 2 ou de plus de 60 caractères n'est pas créé et un message l'explique.
- [x] Une adresse déjà présente déclenche l'alerte de doublon, qui propose d'ajouter au lien existant les catégories sélectionnées qu'il n'a pas, sans dépasser trois ; après validation, la carte existante porte ces catégories.
- [x] Sur un lien existant qui porte déjà trois catégories, l'alerte de doublon ne propose aucun complément.
- [x] L'alerte de doublon précise que le contexte saisi n'est pas repris, et le contexte du lien existant reste inchangé.
- [x] Compléter les catégories d'un lien existant ne produit aucune annonce dans Google Chat.
- [x] La reprise d'un lien de Chat suit les mêmes règles de catégories que l'ajout.
- [x] Un admin enregistre une source avec trois catégories, dont une créée à la frappe.
- [ ] Un ajout complet (contexte vide, deux catégories dont une créée, adresse collée) se fait en moins de 15 secondes.

## Hors scope

- **Nettoyage des catégories vides** (supprimer une catégorie créée puis jamais utilisée) : relève de `fusion-categories` ; ces catégories restent proposées dans le formulaire en attendant.
- **Harmonisation des noms déjà en base** : choix assumé de n'appliquer la majuscule initiale qu'aux nouvelles catégories.
- **Reclassement d'un lien en dehors du doublon** (ajouter ou retirer une catégorie depuis une carte) : `reclassement-collectif`.
- **Retirer une catégorie d'un lien existant** : jamais par le chemin du doublon.
- **Correction du contexte d'un lien existant**, y compris par son propre posteur via le doublon : `correction-retrait-lien`.
- **Modification d'une source déjà enregistrée** : seul le formulaire d'enregistrement change.

## Impacts transverses

- **Traduction / langues** : nouveaux libellés et messages (création au-delà du plafond, nom invalide, complément sur doublon, contexte non repris) en français uniquement (R11).
- **Droits d'accès** : inchangé pour l'ajout et la création (tout membre, R2) ; le complément de catégories d'un lien existant est ouvert à tout membre ; les sources restent réservées aux admins (R13).
- **Cloisonnement des données** : non — tout est partagé (R8).
- **Apparence / déclinaisons** : non — un seul thème, clair et sombre.
- **Exposition à des tiers** : non.
- **Emails / notifications** : non — l'annonce Chat d'un nouveau lien est inchangée ; un complément de catégories n'est pas annoncé (R5).
- **Données existantes** : aucune reprise — les liens à une ou deux catégories restent valides, les noms de catégories existants ne changent pas.
- **Comportement par défaut** : pas d'activation ; le nouveau formulaire remplace l'actuel pour tous.

## Questions ouvertes

- **Édition de la vision et du backlog** : le principe 5 (« une ou deux catégories »), la règle R4 et les capacités C1.1 et C1.6 parlent de deux catégories. Options : (a) `/vision` en mode Éditer puis `/product-backlog` avant `/feature-plan`, (b) après la livraison, lors du `/sync`. Recommandé : (a), pour que le plan s'appuie sur une vision déjà à jour, comme pour la story 012. → tranché : (b), au `/sync` de clôture (décision du plan).
- **Rattachement au backlog** : la story ne correspond à aucune ligne (évolution de `ajout-lien`, livrée) et réalise une partie de C3.2 par le chemin du doublon. Options : (a) aucun rattachement, (b) une nouvelle ligne V2 créée par `/product-backlog`. Recommandé : (a), `reclassement-collectif` reste à cadrer en entier. → tranché : (a), aucun rattachement.

---

## Annexe — Pistes pour le plan

- Formulaire actuel : `src/Form/LinkType.php` (pastilles `EntityType` + champ `newCategory`), limite `LinkSubmission::MAX_CATEGORIES = 2`, composant Live `src/Twig/Components/LinkForm.php` et `templates/components/LinkForm.html.twig` (doublon détecté au fil de la saisie de l'URL, `getDuplicate()`).
- `HistoricalLinkType` hérite de `LinkType` : la reprise suivra sans travail dédié. `SourceType` (+ `SourceSubmission`) duplique les pastilles : piste d'un rendu ou d'un type partagé, à confirmer.
- `CategoryResolver::resolve()` réutilise déjà une catégorie de même nom (casse ignorée) : point d'appui pour la création immédiate et pour la majuscule initiale (`mb_` sur le premier caractère, multioctet : « éco… » → « Éco… »).
- `LinkRepository::countByCategory()` fait une jointure interne : les catégories vides restent hors de la navigation sans changement.
- La création immédiate implique une action serveur à l'Entrée (action Live) plutôt qu'une création à la soumission ; Entrée ne doit pas déclencher le submit natif.
- Le complément sur doublon touche le lien d'un autre membre : action distincte de l'ajout, sans passer par `LinkAnnouncer`.

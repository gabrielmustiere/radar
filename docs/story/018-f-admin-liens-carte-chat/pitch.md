# Permettre à un admin de supprimer un lien et de relancer son annonce, dans une carte Google Chat soignée

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/018-f-admin-liens-carte-chat/`
> **Amont** : aucun

Un admin peut retirer définitivement un lien de Radar et relancer son annonce dans Google Chat, depuis un menu sur la carte. L'annonce devient une vraie carte, avec un bandeau d'aperçu et des boutons, à la place du message texte actuel.

## Contexte

Une fois un lien entré dans Radar, personne ne peut le retirer : ni le posteur, dont le retrait est prévu mais pas livré, ni un admin. Un lien posté par erreur, cassé ou arrivé par une source suivie reste donc dans le fil et la recherche. Le posteur ne peut rien faire pour les liens automatiques, puisque leur posteur est le compte système.

L'annonce pose deux autres problèmes. Elle part une seule fois : si elle se perd, ou si un admin veut remettre en avant un lien repris de l'historique, rien ne permet de la renvoyer. Elle se limite aussi à un message texte, sans image ni bouton, alors que Google Chat reste le point de contact quotidien de l'équipe avec la veille. Une annonce peu engageante affaiblit le levier de curiosité dont dépend l'usage.

## Alignement vision

- **Problème adressé** : garder une base fiable, où ce qu'on retrouve mérite d'être retrouvé, et rendre la découverte plus attractive dans Chat.
- **Audience servie** : les admins pour la suppression et la relance ; tous les membres de l'espace Chat pour la carte.
- **Principes respectés** : principe 2 prolongé dans Chat (l'annonce se lit sans clic, avec un repli soigné sans image) ; principe 4 (Chat reste le haut-parleur, la discussion y reste). Le principe 5 n'est pas touché, car le classement reste collectif. La suppression est un droit admin limité, pas un rôle de curateur. L'outil reste interne : seul le bandeau d'annonce, dérivé d'une image déjà publique, est lisible sans connexion.
- **Hypothèse testée** : hypothèse 2, indirectement — une annonce plus soignée renforce l'intérêt de passer par Radar plutôt que de poster dans Chat.
- **Impact North Star** : indirect et positif par la curiosité, légèrement négatif par la suppression, car les ouvertures d'un lien supprimé ne comptent plus. Cet effet est assumé : la suppression reste exceptionnelle.

## Utilisateurs concernés

- **Admin** — dispose sur chaque carte de deux nouvelles actions : supprimer le lien et relancer l'annonce.
- **Membre de l'espace Google Chat** — reçoit une carte plus lisible, avec aperçu et boutons. Il peut voir un même lien annoncé à nouveau si un admin le relance.
- **Posteur** — son lien peut être retiré par un admin sans qu'il en soit notifié par Radar. Ses droits ne changent pas.
- **Membre de Radar non admin** — ne voit aucune nouvelle action dans Radar.

## User Stories

- En tant qu'**admin**, je veux supprimer un lien depuis sa carte afin de retirer de Radar un lien erroné, cassé ou sans intérêt, y compris un lien arrivé par une source suivie.
- En tant qu'**admin**, je veux confirmer une suppression en voyant le titre du lien afin de ne pas retirer le mauvais par erreur.
- En tant qu'**admin**, je veux relancer l'annonce d'un lien afin de rattraper une annonce perdue ou de remettre en avant un lien, même repris de l'historique.
- En tant qu'**admin**, je veux voir la date de la dernière annonce avant de relancer afin de ne pas spammer l'espace.
- En tant que **membre de l'espace Chat**, je veux voir une annonce avec bandeau, titre, contexte ou description et boutons afin de décider sans clic si le lien m'intéresse.
- En tant que **membre de l'espace Chat**, je veux ouvrir le lien ou sa carte Radar d'un bouton afin d'accéder directement à ce qui m'intéresse.
- En tant que **membre de Radar non admin**, je ne veux PAS voir les actions d'administration afin que ma carte reste simple.

## Règles métier

**Accès**

1. Seuls les admins voient et utilisent les actions de suppression et de relance. Ces deux gestes s'ajoutent aux exceptions à « pas de curateur », et complètent la règle « retrait réservé au posteur ».
2. Les actions se trouvent dans un menu « … » sur les cartes du fil et sur la page détaillée d'une carte. Elles ne s'affichent pas ailleurs.

**Suppression**

3. La suppression est définitive et demande une confirmation qui rappelle le titre du lien, ou son adresse s'il n'a pas de titre.
4. Tout lien peut être supprimé : ajouté par un membre, repris de l'historique ou arrivé par une source suivie.
5. Un lien supprimé disparaît du fil, des catégories et de la recherche. Ses ouvertures et son bandeau d'annonce sont effacés avec lui, et ses ouvertures ne comptent plus dans les indicateurs.
6. Les catégories du lien sont conservées, même si elles deviennent vides.
7. L'adresse d'un lien supprimé redevient libre : un membre peut la réajouter, et elle est alors traitée comme un nouveau lien.
8. Un article de source supprimé ne revient pas lors d'une relève normale. Il peut revenir si un admin lance une reprise qui le couvre, et ce comportement est accepté.
9. Radar ne publie rien dans Chat lors d'une suppression. L'annonce existante y reste, et son lien vers Radar mène à une page introuvable.
10. Un lien supprimé avant la mise en file de son annonce n'est jamais annoncé. Une annonce déjà en file, ou dont le traitement est en cours, peut encore partir.

**Relance**

11. Une annonce part automatiquement une seule fois, après l'ajout ; reclassements et corrections ne sont toujours pas annoncés, et un lien repris ne l'est jamais automatiquement. Seul un admin peut déclencher une annonce supplémentaire.
12. Un admin peut relancer l'annonce de tout lien, déjà annoncé ou non, repris de l'historique compris.
13. La relance reste indisponible tant que l'aperçu du lien est en attente, pour éviter que l'annonce automatique et la relance partent toutes les deux.
14. La relance demande une confirmation qui affiche la date de la dernière annonce, ou « jamais annoncé ». Une relance confirmée met cette date à jour.
15. Une relance utilise exactement la même carte qu'une première annonce, sans mention de rappel.
16. Le message de retour à l'admin indique que l'annonce est mise en file, pas qu'elle est publiée : Radar ne sait pas si Chat l'a reçue.
17. Tant que l'espace Chat n'est pas configuré, rien n'est envoyé, comme pour une annonce automatique.

**Carte d'annonce**

18. L'annonce contient, quand ils existent : en en-tête le titre, le type de source et les catégories ; le bandeau d'aperçu ; le contexte du posteur ou, à défaut, la description ; le posteur (ou la source suivie) ; et deux boutons, « Ouvrir le lien » et « Voir dans Radar », sans couleur personnalisée.
19. Le titre retombe sur l'adresse du lien quand la source n'en fournit pas.
20. Le bandeau est produit par Radar au ratio 3:1, sans déformation, à partir de l'aperçu qu'il conserve. Sans aperçu conservé, la carte passe à une version compacte, sans emplacement vide.
21. Les boutons ouvrent seulement des liens : aucune action Radar ne se déclenche depuis Chat.
22. Le bandeau est le seul contenu de Radar lisible sans connexion : Google doit pouvoir y accéder. Son adresse, propre à chaque lien, n'est pas devinable, et il cesse d'être accessible quand le lien est supprimé.
23. L'annonce reste en français, et un échec d'envoi n'empêche ni l'ajout ni la suppression d'un lien.

## Critères d'acceptation

**Suppression**

- [ ] Un admin voit le menu « … » sur une carte du fil et sur la page détaillée ; un membre non admin ne le voit pas.
- [ ] Un membre non admin qui tente de supprimer ou de relancer directement est refusé.
- [ ] Après confirmation, le lien n'apparaît plus dans le fil, ses catégories et la recherche, et sa page détaillée est introuvable.
- [ ] Annuler la confirmation ne change rien.
- [ ] Une adresse supprimée peut être réajoutée sans être signalée comme doublon.
- [ ] Une catégorie dont le dernier lien est supprimé existe toujours.
- [ ] Une suppression n'envoie aucun message dans Chat.
- [ ] Un lien supprimé pendant que son aperçu est en attente ne produit aucune annonce.

**Relance**

- [ ] Un admin peut relancer un lien déjà annoncé, jamais annoncé ou repris de l'historique, et une nouvelle annonce part.
- [ ] La confirmation affiche la date de la dernière annonce, ou « jamais annoncé ».
- [ ] La relance n'est pas proposée tant que l'aperçu est en attente.
- [ ] Une annonce relancée a le même contenu qu'une première annonce.
- [ ] Le message de retour indique une mise en file, pas une publication.
- [ ] L'ajout d'un lien produit toujours une seule annonce automatique, et un lien repris aucune.

**Carte d'annonce**

- [ ] Une annonce réelle, publiée après déploiement, s'affiche correctement dans l'espace de l'équipe.
- [ ] Un lien avec aperçu conservé produit une annonce avec bandeau non déformé, titre, posteur et deux boutons sans couleur personnalisée.
- [ ] L'annonce affiche le contexte du posteur s'il existe, sinon la description.
- [ ] Un lien sans aperçu conservé produit la version compacte, sans emplacement vide.
- [ ] Le bandeau d'un lien est lisible sans connexion et ne l'est plus après sa suppression ; les autres images de Radar restent réservées aux membres.
- [ ] Un lien sans titre affiche son adresse.
- [ ] Un lien issu d'une source suivie affiche le nom de cette source.
- [ ] Les boutons ouvrent le lien d'origine et sa carte Radar.

## Hors scope

- **Retrait de l'annonce dans Chat** : Radar ne peut pas effacer le message, un membre autorisé le fait à la main dans Chat.
- **Corbeille ou restauration** : la suppression est définitive.
- **Notification au posteur ou message de retrait** : l'admin prévient le posteur dans Chat s'il le juge utile.
- **Retrait ou correction par le posteur** : couvert par la ligne de backlog `correction-retrait-lien`, qui reste à livrer.
- **Actions depuis Chat** (reclasser, supprimer, réagir) : les boutons ouvrent seulement des liens.
- **Liste d'administration des liens** : les actions restent sur les cartes.
- **Suivi de réception des annonces** : Radar ne sait pas si Chat a reçu le message.
- **Liste de liens interdits** : un lien supprimé peut être réajouté ou revenir par une reprise.
- **Plusieurs espaces Chat ou mise en forme selon l'espace** : un seul espace d'équipe, comme aujourd'hui.

## Impacts transverses

- **Traduction / langues** : nouveaux libellés et annonces en français uniquement.
- **Droits d'accès** : deux nouveaux gestes réservés aux admins ; les droits des membres ne changent pas. Exception à « rien sans connexion » : le bandeau d'annonce, seul contenu lisible sans compte.
- **Cloisonnement des données** : non.
- **Apparence / déclinaisons** : menu visible des seuls admins, à prévoir en thème clair et sombre et à rendre utilisable sur mobile.
- **Exposition à des tiers** : la description et un bandeau servi par Radar s'ajoutent aux données envoyées à Google Chat. L'image provient d'une page déjà publique, et son adresse n'est transmise qu'à Google.
- **Emails / notifications** : un message Chat par relance ; aucun à la suppression.
- **Données existantes** : les anciennes annonces restent telles quelles dans Chat. Une relance d'un lien existant utilise la nouvelle carte et crée son bandeau. Les liens supprimés et leurs ouvertures ne sont pas récupérables.
- **Comportement par défaut** : rien ne change pour les membres non admin dans Radar. Toutes les nouvelles annonces utilisent la nouvelle carte, sans réglage.

## Questions ouvertes

- **Ordre avec la story `017-t-durcissement-securite`** : la correction contre l'injection de mentions et de liens porte sur le texte que cette story remplace. Options : (a) livrer la 017 d'abord, puis adapter sa protection à la carte, (b) reprendre la protection dans cette story. → tranché : (a), elle ferme un risque ouvert en production sans attendre la carte.
- **Rendu réel de la carte** : la documentation de Google ne confirmait pas la prise en charge des cartes par la connexion actuelle à Chat. Options : (a) essayer d'abord sur l'espace de l'équipe, (b) développer puis vérifier. → tranché : (a), les essais du 2026-11-04 ont été acceptés par Google et ont conduit au bandeau produit par Radar.
- **Mise à jour des règles du backlog** : R1, R5 et R12 ne décrivent plus le comportement cible, et la note de `correction-retrait-lien` sur les liens automatiques trouve sa réponse. Options : (a) réaligner à la clôture de la story, (b) avant le plan. Recommandé : (a), via `/forge:sync` ou `/forge:product-backlog`, ces fichiers ne relevant pas du pitch.

---

## Annexe — Pistes pour le plan

- Le message part aujourd'hui en texte dans `LinkAnnouncer::announce` (`src/Service/LinkAnnouncer.php:32`). `GoogleChatOptions::cardV2()` existe dans le bridge. Le champ `fallbackText` documenté par Google peut servir aux notifications mobiles, à passer au constructeur des options car le bridge n'a pas de méthode dédiée.
- `announced_at` est posé à la mise en file (`ChatMessage` routé vers `async`) : un échec après les relances part dans `failed` sans effet sur la date.
- Le garde de `LinkAnnouncer::announce` bloque les liens repris et déjà annoncés : la relance doit le contourner sans fragiliser le chemin automatique, notamment le rejeu de `FetchLinkPreviewHandler`.
- Le bandeau part de la copie conservée, `/media/` exigeant une connexion. `public/logo.png` est utilisable pour l'en-tête ; adresse absolue à confirmer depuis le worker.
- Le texte de carte est en HTML : la protection de la story 017 (sosies `‹ ›`) ne couvre pas forcément ce contexte, y compris pour les titres et les adresses des boutons.
- `LinkOpening` a déjà `onDelete: CASCADE`. `SourceItem` n'est pas lié au lien, d'où la règle 8 (`src/Service/SourcePublisher.php:47-58`). Les visuels stockés peuvent devenir orphelins ou être partagés par plusieurs liens : à examiner.
- Droits via `AdminVoter` / `RADAR_ADMIN`, avec actions POST protégées par CSRF. `_card.html.twig` est aussi inclus par `_duplicate.html.twig` et `PendingLinkCard` : le menu ne doit apparaître que dans les contextes de la règle 2. Le pied de carte est masqué sur mobile (`max-sm:hidden`), à prendre en compte pour le placement du menu.

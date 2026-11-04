# Review — Permettre à un admin de supprimer un lien et de relancer son annonce, dans une carte Google Chat soignée

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/018-f-admin-liens-carte-chat/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree, étapes 1 à 4 du plan, 19 fichiers modifiés + 12 nouveaux, 129 lignes ajoutées dans `src`, `templates` et `config` hors fichiers nouveaux

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 1 / 2
- **Mineurs restants** : 0 / 5
- **Statut** : **PRÊT À COMMITER SOUS RÉSERVE** de ne pas déployer ces étapes avant la story 017

Commiter sans déployer, livrer la 017, puis reprendre les étapes 5 et 6 avec `/forge:feature-implem`.

## Bloquants

_(aucun)_

## Importants

- [x] **[PLAN] La modale ne rappelle pas l'adresse d'un lien sans titre** — `templates/admin_link/show.html.twig:9` — la règle 3 du pitch exige le titre ou, à défaut, l'adresse, mais `link|link_title` retombe sur le domaine : deux articles sans titre du même site affichent tous deux `example.com` avant une suppression irréversible. Corrigé : `link.url` affiché sous le titre, avec un test.
- [ ] **[SECU] La relance renvoie un texte non neutralisé tant que la 017 n'est pas livrée** — `src/Service/LinkAnnouncer.php:51` — un titre contenant `<users/all>` notifie tout l'espace à chaque relance. La faille M2 préexiste, mais le geste devient répétable. Action : ne pas déployer ces étapes avant la 017 ; le correctif appartient à son plan.

## Mineurs

- [x] **[ROBUSTESSE] `store()` ne protège pas GD contre un aperçu illisible** — `src/Service/Preview/LinkBannerStore.php:48` — hors prod, l'avertissement d'`imagecreatefromstring` devient une exception au lieu du `null` promis (reproduit sur un fichier vide et sur des octets corrompus) ; en prod, `php_errors.throw` suit `kernel.debug`. Corrigé : même `set_error_handler` que `PreviewImageStore::decode`, avec deux cas de test.
- [x] **[STYLE] `reannounce()` et `publish()` font double emploi** — `src/Service/LinkAnnouncer.php:38-53` — Corrigé : `announce()` appelle `reannounce()` après ses gardes, `publish()` a disparu.
- [x] **[STYLE] Le libellé des flash diffère de celui de la carte** — `src/Controller/AdminLinkController.php:40` — `getTitle() ?? getUrl()` était dupliqué et affichait l'adresse brute. Corrigé : `LinkExtension::title()`, le libellé de la carte.
- [x] **[STYLE] La modale commune a perdu un nom accessible précis** — `templates/base.html.twig:139` — `aria-label="Radar"` ne disait plus ce qu'elle contient. Corrigé : `aria-labelledby` vers le `h1` chargé dans la frame.

- [x] **[BUG] La page directe d'administration portait deux frames de même identifiant** — `templates/base.html.twig:138` — trouvé pendant les corrections : la modale commune instanciait sa frame à côté de celle de la page, ce que `app_link_new` et `app_link_seed` évitent déjà. Corrigé : route ajoutée à l'exclusion, avec un test.

## Points positifs

- **Effacement prouvé sur la vraie base** : chacun des deux `DELETE` explicites a été retiré tour à tour et le test échoue, ce qui a révélé l'erreur du plan sur `link_category`.
- **Bandeau découplé des identifiants** : le nom HMAC empêche l'énumération, et le secret de production est distinct du `.env` commité.
- **Route publique resserrée** : seul le motif hexadécimal de 64 caractères sort sans connexion, et `/media/` reste redirigé vers la connexion.
- **Sorties échappées** : titres et adresses tiers restent échappés par Twig, y compris dans les flash.
- **Garde de `PendingLinkCard` utile** : sans elle, le rafraîchissement d'un lien supprimé lève une erreur Twig, et un test le prouve.

## Hors review (à vérifier en environnement réel)

- Étapes 5 et 6 non livrées : `LinkBannerStore::store()` n'a pas encore d'appelant et aucun critère d'acceptation de la carte n'est atteint.
- Rendu de la modale et du bouton en thème sombre : seul le thème clair a été relu.
- Bandeau accessible à Google depuis la production, et cache public de 24 h après suppression : à vérifier à l'étape 6.
- Tests E2E ignorés : catalogue de liens épuisé sur la base de dev, confirmé sur `AddLinkTest` ; relancer après un `make db-reset`.

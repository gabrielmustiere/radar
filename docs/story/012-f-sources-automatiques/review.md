# Review — Publier automatiquement les articles des sources suivies

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/012-f-sources-automatiques/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree, 22 fichiers modifiés + 47 nouveaux, ~2 800 lignes

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 2
- **Mineurs restants** : 0 / 3
- **Statut** : **PRÊT À COMMITER**

Tous les findings sont corrigés ; le fichier est conservé pour sa section « Hors review ». Prochaine étape : `/forge:sync`, puis `/forge:commit`.

## Bloquants

_(aucun)_

## Importants

- [x] **[BUG] Le compte « radar » est proposé comme posteur d'origine dans « Reprendre un lien de Chat »** — `src/Form/HistoricalLinkType.php:33` — le choix des posteurs liste tous les utilisateurs (`UserRepository::findAllOrderedByEmail()`), compte système compris dès la première relève : un membre peut reprendre un lien de l'historique « posté par radar », qui se confondrait avec un article automatique sans en porter le badge. Corrigé : `UserRepository::findMembersOrderedByEmail()` exclut le compte système, testé dans `LinkSeedTest`.
- [x] **[TEST] Critère « retirer une source arrête la publication » non vérifié** — `src/MessageHandler/FetchSourceHandler.php:23`, `src/Repository/SourceRepository.php:43` — ni la garde `isActive()` du handler ni le filtre `removedAt IS NULL` de `findActiveIds()` ne sont exercés : une régression publierait à nouveau les articles d'une source retirée. Corrigé : `SourceLinkTest::testRemovedSourceIsNoLongerFetched` (source retirée, article oublié, `FetchSources` puis `FetchSource` → rien n'est publié ni relevé).

## Mineurs

- [x] **[ROBUSTESSE] Une source réactivée affiche l'état de relève de sa vie précédente** — `src/Entity/Source.php:121` — `reactivate()` garde `lastFetchedAt`, `lastError` et `lastErrorAt` : la page affiche « Dernière relève réussie le … » ou une erreur d'avant le retrait. Corrigé : remis à `null` par `reactivate()`, vérifié dans `SourceControllerTest`.
- [x] **[TEST] L'E2E lit le formulaire avant la réponse de Turbo** — `tests/E2E/SourceTest.php:26` — avec Turbo Drive, le clic sur « Suivre » rend la main avant la réponse : `innerText()` lit l'ancien formulaire, si bien qu'un flux injoignable fait échouer le test au lieu de l'ignorer. Corrigé : le test attend la ligne de la source ou l'erreur du champ (`or()`) avant de décider.
- [x] **[STYLE] Noms de classe qualifiés en ligne dans deux tests** — `tests/Controller/SourceControllerTest.php:61`, `tests/E2E/SourceTest.php:74` — `\App\Entity\SourceItem::class` et `\Playwright\Locator\LocatorInterface` : importés, comme dans `AddLinkTest`.

## Points positifs

- **Sécurité des flux** : le lecteur réutilise le client des aperçus (pas de réseau privé, limiteur par hôte, plafonds de durée), plafonne la taille à 2 Mo, et le parseur ne résout jamais d'entité externe — vérifié par un test dédié.
- **Idempotence de la relève** : unicité `(source, clé)`, articles marqués vus dans le même flush que leur lien, doublons R6 marqués vus sans publication, erreur de flux enregistrée sans exception ni retry inutile.
- **Pas de N+1** : la source est jointe dans les deux requêtes du fil, et la page des sources charge ses catégories en une requête.
- **Tests contre des flux simulés uniques par exécution** (`/feed/journal?run=…`) : les tests fonctionnels restent indépendants malgré la base de test partagée, et couvrent la relève de bout en bout (publication, badge, annonce unique, erreur isolée).
- **Retrait logique et réactivation** : les cartes gardent le nom de leur source, et une adresse n'a jamais qu'une ligne.

## Hors review (à vérifier en environnement réel)

- Ajouter le worker `messenger:consume scheduler_default` au déploiement de production ; sans lui, aucune relève n'a lieu, sans erreur visible ailleurs que la « dernière relève réussie » de `/sources`.
- Liens déjà en base dont l'adresse porte une ancre : leur forme normalisée reste sans ancre (choix assumé, non renormalisés). Un même lien recollé avec son ancre ne sera pas reconnu comme doublon.
- Après un `make db-reset` serveur lancé, relancer `make serve-d` : les workers gardent une connexion vers l'ancien fichier SQLite et n'avancent plus (comportement préexistant).
- L'E2E `SourceTest` lit le vrai flux FeedBurner depuis le serveur de dev : dépendant du réseau.
- `composer audit` signale deux avis préexistants (`symfony/expression-language`, `symfony/mime`), hors de cette story.

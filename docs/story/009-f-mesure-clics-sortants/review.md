# Review — Compter chaque ouverture de lien avec son âge

> **But** : juger le diff au regard de l'intention — dire si on commite, et ce qui bloque.
> **Registre** : technique
> **Story** : `docs/story/009-f-mesure-clics-sortants/`
> **Amont** : `plan.md` · `pitch.md`
> **Diff examiné** : working tree, 4 fichiers modifiés + 7 nouveaux

## Synthèse

- **Bloquants restants** : 0 / 0
- **Importants restants** : 0 / 0
- **Mineurs restants** : 1 / 1
- **Statut** : **PRÊT À COMMITER**

`/forge:sync`, puis `/forge:commit`. Le mineur ouvert part en dette du report.

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [ ] **[SECU] Mesure gonflable par une requête GET forgée** — `src/Controller/LinkController.php` (`app_link_open`) — un membre connecté qui charge une image ou une page pointant vers `/liens/{id}/ouvrir` compte une ouverture sans clic. Impact limité à la fiabilité de la North Star d'un outil interne ; un POST casserait l'ouverture en nouvel onglet par simple lien. À reconsidérer si les indicateurs (V2) montrent des valeurs aberrantes.

## Points positifs

- **Pas de redirection ouverte** : la destination vient uniquement de l'URL enregistrée du lien.
- **Âge stocké** : la North Star sera un simple `COUNT` filtré, insensible aux futures éditions de date.
- **Destination visible** : le `title` du bouton « Ouvrir » compense le `href` interne.

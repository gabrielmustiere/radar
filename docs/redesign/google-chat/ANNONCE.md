# Annonce Google Chat — proposition 4 « Couleur et action par type »

> Source : canvas de design, onglet **V4 · Alertes Google Chat**, maquette 4 (`maquette-annonce.html`, même format que les autres maquettes : à lire, pas à exécuter).
> Code concerné : `src/Service/LinkAnnouncementCard.php` (contenu de la carte) et, à la marge, `src/Service/LinkAnnouncer.php`.
> Les règles métier de la story 008 ne changent pas : une seule annonce, jamais pour un lien repris, envoyée après l'aperçu, et un échec d'envoi ne bloque pas l'ajout.

## Intention

Transposer les cartes typées de Radar dans Chat : on reconnaît d'un coup d'œil ce qu'est le lien (icône et couleur du type), on lit pourquoi il compte (le « pourquoi » du posteur, mis en avant), et on agit en un clic avec un bouton dont le libellé dépend du type.

## Anatomie de la carte (`cardsV2`, une section)

1. **Titre** (`decoratedText`, premier widget). Pas d'en-tête de carte : `header.title` n'accepte aucun formatage, or le titre doit être en gras.
   - `startIcon` : l'icône du type (PNG 128 × 128 public, voir « Icônes »), `imageType: SQUARE`, `altText` : le libellé du type ;
   - `text` : `<b>{titre}</b>`, avec le même titre de repli lisible que dans l'app (« @symfony sur X » plutôt que l'URL) ;
   - `bottomLabel` : `{libellé du type} · {catégories}` (déjà fait par `subtitle()`). Pour un article, le domaine est inséré : `Article · martinfowler.com · Tests`.
2. **Description** (`textParagraph`, facultative) : la description de l'aperçu, coupée côté serveur à ~160 caractères avec « … ». Affichée seulement pour GitHub et les articles, et seulement si elle apporte quelque chose de plus que le titre.
3. **Le pourquoi** (`textParagraph`) : `<b><font color="{texte}">{posteur} :</font></b> <i>{contexte}</i>`.
   - sans contexte : `<font color="{texte}">Posté par {posteur}</font>` ;
   - lien d'une source suivie : `<font color="#C2410C">Via le flux {nom de la source}</font>`.
4. **Boutons** (`buttonList`) :
   - **action principale** : libellé selon le type, `color` = couleur du type, `onClick.openLink.url` = l'URL du lien (comme aujourd'hui) ;
   - **« Voir dans Radar »** : bouton secondaire vers `app_link_show`. Dans la maquette il est sans bordure ; le champ `type: "BORDERLESS"` des boutons de carte est **à vérifier** pour un webhook, sinon on garde le bouton par défaut.

**La bannière disparaît** dans cette proposition. `LinkBannerStore` et la route publique `app_announcement_banner` deviennent inutiles pour les nouvelles annonces. Ne les supprimer qu'après décision, car les annonces déjà envoyées pointent encore vers leurs bandeaux.

## Correspondance par type

| Cas | Règle | Icône | Couleur du bouton (`color` RGBA 0–1) | Couleur du texte (`<font>`) | Bouton principal |
|---|---|---|---|---|---|
| YouTube | `SourceType::YouTube` | `youtube.png` | `#BE123C` → `{red: .745, green: .071, blue: .235}` | `#BE123C` | Regarder la vidéo |
| GitHub | `SourceType::GitHub` | `github.png` | `#5B21B6` → `{red: .357, green: .129, blue: .714}` | `#6D28D9` | Voir le dépôt |
| X | `SourceType::X` | `x.png` | `#0369A1` → `{red: .012, green: .412, blue: .631}` | `#0369A1` | Voir sur X |
| Source suivie | `$link->getSource() !== null`, prioritaire sur le type | `rss.png` | `#C2410C` → `{red: .761, green: .255, blue: .047}` | `#C2410C` | Lire l'article |
| Article | `SourceType::Article` | `article.png` | `#0F766E` → `{red: .059, green: .463, blue: .431}` | `#0F766E` | Lire l'article |
| Image | `SourceType::Image` | `image.png` | `#17181C` → `{red: .090, green: .094, blue: .110}` | `#17181C` | Voir l'image |

Toujours ajouter `alpha: 1` à la couleur du bouton. Chat choisit seul la couleur du texte d'un bouton plein et la passe en noir sur un fond qu'il juge clair : c'était le cas du violet `#7C3AED` de la maquette. GitHub et YouTube prennent donc une teinte plus foncée que dans l'app. Les couleurs reprennent la [DÉCISION 1] de `SPEC.md` (X en bleu ciel, Article en turquoise) ; si elle est refusée, X et Article passent à `#17181C`.

## Icônes

Les 6 PNG sont dans `docs/redesign/google-chat/icons/`. Google Chat les télécharge sans compte : les déplacer dans `public/chat/icons/`, qui est servi tel quel comme `public/logo.png`, sans toucher à `access_control`. L'URL se construit comme celle du logo aujourd'hui : `{url de app_page}chat/icons/{type}.png`.

## Exemple de payload (GitHub)

```json
{
  "fallbackText": "w3c/webauthn · Dépôt GitHub · Sécurité · Posté par gabriel",
  "cardsV2": [{
    "cardId": "link-7",
    "card": {
      "sections": [{
        "widgets": [
          { "decoratedText": {
            "startIcon": { "iconUrl": "https://radar.mustiere.fr/chat/icons/github.png", "imageType": "SQUARE", "altText": "Dépôt GitHub" },
            "text": "<b>w3c/webauthn</b>",
            "bottomLabel": "Dépôt GitHub · Sécurité"
          } },
          { "textParagraph": { "text": "Web Authentication: An API for accessing Public Key Credentials" } },
          { "textParagraph": { "text": "<b><font color=\"#6D28D9\">gabriel :</font></b> <i>La spécification WebAuthn, pour trancher quand un navigateur se comporte bizarrement.</i>" } },
          { "buttonList": { "buttons": [
            {
              "text": "Voir le dépôt",
              "color": { "red": 0.357, "green": 0.129, "blue": 0.714, "alpha": 1 },
              "onClick": { "openLink": { "url": "https://github.com/w3c/webauthn" } }
            },
            {
              "text": "Voir dans Radar",
              "onClick": { "openLink": { "url": "https://radar.mustiere.fr/liens/7" } }
            }
          ] } }
        ]
      }]
    }
  }]
}
```

## Sécurité (déjà en place, à conserver)

- Tout texte venu de l'extérieur (titre, description, contexte, nom du posteur, nom de la source, catégories) passe par `safeText()` **avant** d'être placé dans le HTML de la carte. Les balises `<b>`, `<i>` et `<font>` sont ajoutées par le code, jamais tirées des données.
- Les URL passent par `safeUrl()`.

## Tests à prévoir

- Un test unitaire de `LinkAnnouncementCard` par cas du tableau : icône, couleur, libellé du bouton, ligne du pourquoi avec et sans contexte, source suivie.
- Échappement : un contexte contenant `<users/all>` ou `<a href=…>` ne produit ni mention ni lien.
- Description coupée à ~160 caractères ; absente pour YouTube et X.

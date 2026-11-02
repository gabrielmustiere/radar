// Publiée sur le Web Store, l'extension porte une `update_url` ; chargée non empaquetée, elle vise le serveur local.
export const RADAR_URL = chrome.runtime.getManifest().update_url ? 'https://radar.mustiere.fr' : 'https://radar.wip';

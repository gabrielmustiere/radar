const MENU_ID = 'radar-add-page';

chrome.runtime.onInstalled.addListener(() => {
    chrome.contextMenus.create({ id: MENU_ID, title: 'Ajouter cette page à Radar', contexts: ['page'] });
});

chrome.contextMenus.onClicked.addListener(async (info) => {
    if (info.menuItemId !== MENU_ID) {
        return;
    }

    try {
        await chrome.action.openPopup();
    } catch {
        // Certains navigateurs refusent d'ouvrir la bulle depuis le menu : même formulaire dans une petite fenêtre.
        const query = new URLSearchParams({ url: info.pageUrl });
        await chrome.windows.create({ url: `popup.html?${query}`, type: 'popup', width: 420, height: 640 });
    }
});

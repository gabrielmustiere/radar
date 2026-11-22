import { Controller } from '@hotwired/stimulus';

// L'invite native (Chrome, Edge) n'est émise qu'une fois par chargement complet : gardée au niveau du module,
// elle survit aux visites Turbo qui reconnectent le contrôleur.
let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
});

/** Entrée « Installer l'app » : invite native quand le navigateur en fournit une, page d'aide sinon. */
export default class extends Controller {
    connect() {
        this.element.hidden = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    }

    async install(event) {
        if (installPrompt === null) {
            return;
        }

        event.preventDefault();
        const prompt = installPrompt;
        installPrompt = null;
        await prompt.prompt();
    }
}

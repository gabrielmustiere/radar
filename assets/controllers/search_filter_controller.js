import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

// Relaie la saisie de la topbar à la liste de la page (Live Component), qui se filtre sans recharger.
export default class extends Controller {
    async filter() {
        const list = await getComponent(document.querySelector('[data-live-name-value="LinkList"]'));
        list.set('q', this.element.elements.q.value, true, 300);
    }
}

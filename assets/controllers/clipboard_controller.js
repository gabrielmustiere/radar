import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'label', 'error'];

    async copy() {
        // Clé sélectionnée dans tous les cas : un Cmd+C reste possible si le presse-papiers est refusé.
        window.getSelection().selectAllChildren(this.sourceTarget);
        try {
            await navigator.clipboard.writeText(this.sourceTarget.textContent.trim());
            this.labelTarget.textContent = 'Copiée';
        } catch {
            this.errorTarget.hidden = false;
        }
    }
}

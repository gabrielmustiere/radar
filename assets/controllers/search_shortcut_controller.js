import { Controller } from '@hotwired/stimulus';

// « / » place le curseur dans la recherche, sauf quand on est déjà en train de saisir ailleurs.
export default class extends Controller {
    static targets = ['input'];

    focus(event) {
        if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        const field = event.target.closest('input, textarea, select, [contenteditable]');
        if (field || document.querySelector('dialog[open]')) {
            return;
        }

        event.preventDefault();
        this.inputTarget.focus();
        this.inputTarget.select();
    }
}

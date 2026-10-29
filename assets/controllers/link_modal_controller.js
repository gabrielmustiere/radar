import { Controller } from '@hotwired/stimulus';

// Modale d'ajout de lien : s'ouvre dès que sa frame se met à charger, se vide à la fermeture pour repartir d'un formulaire neuf.
export default class extends Controller {
    static targets = ['template', 'frame'];

    connect() {
        if (!this.hasFrameTarget) {
            this.element.append(this.templateTarget.content.cloneNode(true));
        }
        this.placeholder = this.templateTarget.content.querySelector('turbo-frame').innerHTML;
    }

    open() {
        if (!this.element.open) {
            this.element.showModal();
        }
    }

    close() {
        this.element.close();
        this.reset();
    }

    reset() {
        this.frameTarget.removeAttribute('src');
        this.frameTarget.innerHTML = this.placeholder;
    }

    closeOnBackdrop(event) {
        if (event.target !== this.element) {
            return;
        }

        const rect = this.element.getBoundingClientRect();
        const inside = event.clientX >= rect.left && event.clientX <= rect.right
            && event.clientY >= rect.top && event.clientY <= rect.bottom;
        if (!inside) {
            this.close();
        }
    }
}

import { Controller } from '@hotwired/stimulus';

// Masonry en gardant l'ordre de lecture ligne par ligne : la grille passe en rangées de 1px
// et chaque carte en occupe autant que sa hauteur. Sans JS, la grille reste une grille classique.
export default class extends Controller {
    static targets = ['item'];

    initialize() {
        this.observer = new ResizeObserver((entries) => entries.forEach((entry) => this.place(entry.target)));
    }

    connect() {
        this.gap = parseFloat(getComputedStyle(this.element).rowGap) || 0;
        this.element.style.gridAutoRows = '1px';
        this.element.style.rowGap = '0';
    }

    disconnect() {
        this.element.style.gridAutoRows = '';
        this.element.style.rowGap = '';
    }

    itemTargetConnected(item) {
        this.observer.observe(item);
    }

    itemTargetDisconnected(item) {
        this.observer.unobserve(item);
    }

    place(item) {
        item.style.gridRowEnd = `span ${Math.ceil(item.getBoundingClientRect().height + this.gap)}`;
    }
}

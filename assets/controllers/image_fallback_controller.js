import { Controller } from '@hotwired/stimulus';

// Une image d'aperçu qui ne charge pas (source qui bloque le hotlink, image supprimée) laisse place au visuel de repli.
export default class extends Controller {
    static targets = ['image', 'fallback'];

    connect() {
        if (this.imageTarget.complete && this.imageTarget.naturalWidth === 0) {
            this.fallback();
        }
    }

    fallback() {
        this.imageTarget.remove();
        this.fallbackTarget.hidden = false;
    }
}

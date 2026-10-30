import { Controller } from '@hotwired/stimulus';

// Façade vidéo : le lecteur YouTube ne se charge qu'au clic. Lancer la vidéo compte comme une ouverture du lien (North Star).
export default class extends Controller {
    static values = { videoId: String, playUrl: String, token: String, title: String };

    play() {
        const body = new FormData();
        body.append('_token', this.tokenValue);
        fetch(this.playUrlValue, { method: 'POST', body, keepalive: true }).catch(() => {});

        const player = document.createElement('iframe');
        player.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(this.videoIdValue)}?autoplay=1`;
        player.title = this.titleValue;
        player.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        player.allowFullscreen = true;
        player.referrerPolicy = 'strict-origin-when-cross-origin';
        player.className = 'absolute inset-0 size-full';
        player.dataset.test = 'link-video-player';
        this.element.replaceChildren(player);
    }
}

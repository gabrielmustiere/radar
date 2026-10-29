import './stimulus_bootstrap.js';
import './styles/app.css';
import { initFlowbite } from 'flowbite';

// Flowbite ne s'initialise qu'au premier chargement : on le relance après chaque rendu Turbo
// (mêmes événements que le build Turbo officiel de Flowbite).
['turbo:render', 'turbo:frame-load', 'turbo:after-stream-render'].forEach((event) => {
    document.addEventListener(event, () => initFlowbite());
});

// Réponse sans la frame attendue (redirection vers le fil après l'ajout d'un lien en modale) :
// on l'affiche en page complète plutôt que le message « Content missing » de Turbo.
document.addEventListener('turbo:frame-missing', (event) => {
    event.preventDefault();
    event.detail.visit(event.detail.response);
});

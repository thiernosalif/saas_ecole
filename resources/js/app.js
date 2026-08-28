import './bootstrap';

// Livewire 3 embarque et démarre déjà sa propre instance Alpine.js (exposée en
// window.Alpine via @livewireScripts) sur toute page utilisant un composant
// Livewire full-page. On ne réimporte donc pas alpinejs ni n'appelle Alpine.start()
// ici pour éviter une double initialisation — les directives/plugins custom
// s'enregistrent via l'événement 'alpine:init' ci-dessous.
document.addEventListener('alpine:init', () => {
    // Ex. Alpine.directive('...', ...) ou Alpine.store('...', {...})
});

/**
 * public/js/hero-vinyl.js
 *
 * Interactividad del vinilo animado en la sección Hero.
 *
 * Comportamiento:
 *   - Al hacer clic en el disco, alterna entre girar y pausar.
 *   - Utiliza la clase CSS '.is-paused' para controlar animation-play-state.
 *   - No requiere dependencias externas (vanilla JS puro).
 */

(function () {
    'use strict';

    var vinylScene = document.querySelector('.vinyl-scene');

    if (!vinylScene) return;

    vinylScene.addEventListener('click', function () {
        var vinyl = vinylScene.querySelector('.vinyl');
        if (!vinyl) return;
        vinyl.classList.toggle('is-paused');
    });
})();

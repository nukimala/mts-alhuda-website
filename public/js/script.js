/* ============================================================================
   script.js — interaksi kecil website publik.
   Tanpa dependensi. Semua elemen opsional: kalau tidak ada, tidak error.
   ============================================================================ */
(function () {
    'use strict';

    // --- menu seluler -------------------------------------------------------
    var toggle = document.querySelector('[data-nav-toggle]');
    var links = document.getElementById('nav-links');

    if (toggle && links) {
        toggle.addEventListener('click', function () {
            var open = links.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.querySelector('.mi').textContent = open ? 'close' : 'menu';
        });

        links.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                links.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.querySelector('.mi').textContent = 'menu';
            }
        });
    }

    // --- tahun berjalan di footer ------------------------------------------
    document.querySelectorAll('[data-year]').forEach(function (el) {
        el.textContent = new Date().getFullYear();
    });
})();

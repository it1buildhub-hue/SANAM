/*
 * SANAM Assets: puts the display name of the active company in front of the page title, for example
 * "Company - Page title - SANAM". The name comes from front/brand.php?format=json. Without a branded
 * company, or when the request fails, the standard title is kept.
 */
(function () {
    'use strict';

    var script = document.currentScript;
    if (!script || !window.fetch) {
        return;
    }
    var endpoint = new URL('../front/brand.php?format=json', script.src);

    fetch(endpoint.href, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (response) {
            return response.ok ? response.json() : null;
        })
        .then(function (brand) {
            if (brand && typeof brand.name === 'string' && brand.name !== '') {
                document.title = brand.name + ' - ' + document.title;
            }
        })
        .catch(function () {
            // Branding is optional.
        });
})();

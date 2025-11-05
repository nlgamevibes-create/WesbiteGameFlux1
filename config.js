/**
 * GameFlux Website Configuratie
 * 
 * BELANGRIJK: Als je frontend op Netlify/Cloudflare Pages hebt (geen PHP support),
 * moet je de PHP backend op een aparte server hosten!
 * 
 * Pas PHP_SERVER_URL aan naar jouw PHP server (bijv. website.gameflux.nl)
 */

// Detecteer of we op Netlify/Cloudflare Pages staan
const isNetlifyOrCloudflare = window.location.hostname.includes('netlify.app') || 
                               window.location.hostname.includes('pages.dev') ||
                               window.location.hostname.includes('cloudflare') ||
                               window.location.hostname.includes('github.io');

// PHP Server URL - PAS DEZE AAN naar jouw PHP server!
// Voorbeelden:
// - https://api.gameflux.nl
// - https://backend.gameflux.nl
// - https://panel.gameflux.nl
const PHP_SERVER_URL = 'https://website.gameflux.nl'; // <-- PAS DEZE AAN naar je werkende PHP server!

// Gebruik altijd PHP_SERVER_URL als deze is ingesteld, anders gebruik huidige domain
// Als we op een static hosting staan (Netlify/Cloudflare/GitHub Pages), MOETEN we een externe PHP server gebruiken
const isStaticHosting = isNetlifyOrCloudflare;
const API_BASE_URL = (PHP_SERVER_URL && PHP_SERVER_URL !== '') ? PHP_SERVER_URL : window.location.origin;

// Export config
window.GameFluxConfig = {
    API_BASE_URL: API_BASE_URL,
    API_ENDPOINT: API_BASE_URL + '/payment-proxy.php'
};

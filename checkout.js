// Stripe Checkout Integration
document.addEventListener('DOMContentLoaded', () => {
    // Stripe public key - Vervang met jouw Stripe publishable key
    // Note: De key die je hebt is een live key (pk_live_), zorg dat je de juiste gebruikt
    // Vervang met je Stripe Publishable Key van je .env bestand of server configuratie
    // Stripe Publishable Key - deze hoort bij je Secret Key
    // Als je deze niet hebt, haal hem op uit Stripe Dashboard -> Developers -> API keys
    const STRIPE_PUBLISHABLE_KEY = 'pk_live_51SOpweJyuvSjv9sEihIs2wjDUthZIZXTJinhvw7HQanrIUgNIsQn0few2ur7H0OJdeuXgibSvT86CyhySH6TlvlN00CSCV4Wfd';
    const stripe = Stripe ? Stripe(STRIPE_PUBLISHABLE_KEY) : null;
    
    // Stripe Checkout - frontend only
    
    // Get package info from URL parameters or checkout section
    const urlParams = new URLSearchParams(window.location.search);
    let packageName = urlParams.get('package');
    let packagePrice = urlParams.get('price');
    
    // If not in URL, try to get from checkout section display
    if (!packageName || !packagePrice) {
        const packageDisplay = document.getElementById('packageNameDisplay');
        const priceDisplay = document.getElementById('packagePriceDisplay');
        if (packageDisplay && packageDisplay.textContent !== '-') {
            packageName = packageDisplay.textContent;
        }
        if (priceDisplay && priceDisplay.textContent !== '-') {
            packagePrice = priceDisplay.textContent.replace('€', '');
        }
    }
    
    // Fallback to defaults
    packageName = packageName || 'FXServer I';
    packagePrice = packagePrice || '2,10';
    
    // Convert price from "2,10" to 2.10 for calculations
    const priceNumber = parseFloat(packagePrice.replace(',', '.'));
    
    // Package data
    const packages = {
        'FXServer I': { price: '2,10', name: 'FXServer I', priceNum: 2.10 },
        'FXServer II': { price: '4,10', name: 'FXServer II', priceNum: 4.10 },
        'FXServer III': { price: '9,00', name: 'FXServer III', priceNum: 9.00 },
        'FXServer IV': { price: '13,50', name: 'FXServer IV', priceNum: 13.50 },
        'FXServer V': { price: '20,00', name: 'FXServer V', priceNum: 20.00 }
    };
    
    const selectedPackage = packages[packageName] || packages['FXServer I'];
    
    // Update display with package info
    document.getElementById('summaryPackage').textContent = selectedPackage.name;
    document.getElementById('summaryPrice').textContent = '€' + selectedPackage.price;
    document.getElementById('summaryTotal').textContent = '€' + selectedPackage.price;
    document.getElementById('packageNameDisplay').textContent = selectedPackage.name;
    document.getElementById('packagePriceDisplay').textContent = '€' + selectedPackage.price;
    
    // Auto-select Stripe payment method
    selectedPaymentMethod = 'stripe';
    const payButton = document.getElementById('payButton');
    
    // Pay button handler
    payButton.addEventListener('click', async () => {
        // Check if Stripe Publishable Key is configured
        const keyIsPlaceholder = !STRIPE_PUBLISHABLE_KEY || 
                                  STRIPE_PUBLISHABLE_KEY.includes('YOUR_PUBLISHABLE_KEY') || 
                                  STRIPE_PUBLISHABLE_KEY.includes('YOUR_') ||
                                  STRIPE_PUBLISHABLE_KEY.length < 50;
        
        if (keyIsPlaceholder) {
            showMessage('error', '⚠️ Stripe Publishable Key is niet geconfigureerd!<br><br>Voeg je Publishable Key toe in checkout.js (regel 8).<br><br>Je kunt deze vinden in:<br>Stripe Dashboard → Developers → API keys<br><br>De key begint met: pk_live_');
            return;
        }
        
        // Check if Stripe library is loaded
        if (!stripe) {
            showMessage('error', 'Stripe library niet geladen. Controleer je internetverbinding.');
            return;
        }
        
        // Disable button and show loading
        payButton.disabled = true;
        const buttonText = payButton.querySelector('.button-text');
        const buttonLoader = payButton.querySelector('.button-loader');
        buttonText.style.display = 'none';
        buttonLoader.style.display = 'block';
        
        try {
            await processStripePayment(selectedPackage);
        } catch (error) {
            // Error is already handled in processStripePayment
            console.error('Payment process error:', error);
            
            // Re-enable button
            payButton.disabled = false;
            const buttonText = payButton.querySelector('.button-text');
            const buttonLoader = payButton.querySelector('.button-loader');
            if (buttonText) buttonText.style.display = 'block';
            if (buttonLoader) buttonLoader.style.display = 'none';
        }
    });
    
    async function processStripePayment(pkg) {
        // Get Discord username if provided
        const discordUsername = document.getElementById('discordUsername')?.value?.trim() || '';
        
        const paymentData = {
            method: 'stripe',
            package: pkg.name,
            amount: pkg.priceNum,
            price: pkg.price,
            currency: 'EUR',
            discord_username: discordUsername
        };
        
        try {
            // Determine API endpoint - gebruik config als beschikbaar
            let API_ENDPOINT;
            if (window.GameFluxConfig && window.GameFluxConfig.API_ENDPOINT) {
                API_ENDPOINT = window.GameFluxConfig.API_ENDPOINT;
            } else {
                API_ENDPOINT = window.location.origin + '/payment-proxy.php';
            }
            
            console.log('Creating Stripe checkout session...');
            console.log('API Endpoint:', API_ENDPOINT);
            console.log('Payment Data:', paymentData);
            
            let response;
            let responseText;
            
            try {
                response = await fetch(API_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(paymentData),
                    mode: 'cors',
                    credentials: 'omit',
                    cache: 'no-cache'
                });
                
                // Check if we got a response at all
                if (!response) {
                    throw new Error('No response from server. Check if ' + API_ENDPOINT + ' is accessible.');
                }
                
                responseText = await response.text();
            } catch (fetchError) {
                // Network error - server is unreachable
                console.error('Fetch error details:', fetchError);
                
                const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                
                let errorMsg = '⚠️ Kan geen verbinding maken met de server<br><br>' +
                    '<strong>Endpoint:</strong> <code>' + API_ENDPOINT + '</code><br><br>' +
                    '<strong>Mogelijke oorzaken:</strong><br>' +
                    '• PHP server draait niet<br>' +
                    '• Verkeerde URL in config.js<br>' +
                    '• CORS probleem<br>' +
                    '• Firewall/netwerk blokkeert verbinding<br><br>';
                
                if (isLocalhost) {
                    errorMsg += '<strong>Localhost oplossing:</strong><br>' +
                        '1. Start PHP server: <code>php -S localhost:8000</code><br>' +
                        '2. Of gebruik externe server: pas <code>config.js</code> aan';
                } else {
                    errorMsg += '<strong>Oplossing:</strong><br>' +
                        '1. Controleer of <code>payment-proxy.php</code> op de server staat<br>' +
                        '2. Check server logs voor errors<br>' +
                        '3. Verifieer PHP is geïnstalleerd en draait<br>' +
                        '4. Controleer <code>config.js</code> heeft de juiste <code>PHP_SERVER_URL</code>';
                }
                
                throw new Error(errorMsg);
            }
            console.log('Response status:', response.status);
            console.log('Response:', responseText);
            
            // Check if response is empty
            if (!responseText || responseText.trim().length === 0) {
                const isNetlify = window.location.hostname.includes('netlify.app');
                const isCloudflare = window.location.hostname.includes('pages.dev');
                
                let errorMsg = 'Server returned empty response.<br><br>';
                
                if (isNetlify || isCloudflare) {
                    errorMsg += '<strong>⚠️ Netlify/Cloudflare Pages ondersteunt GEEN PHP!</strong><br><br>' +
                        '<strong>Oplossing:</strong><br>' +
                        '1. Upload <code>payment-proxy.php</code> naar een PHP server<br>' +
                        '2. Pas <code>config.js</code> aan met de juiste <code>PHP_SERVER_URL</code><br><br>' +
                        '<strong>Huidige configuratie:</strong><br>' +
                        'API Endpoint: <code>' + API_ENDPOINT + '</code><br>' +
                        'Frontend: <code>' + window.location.origin + '</code>';
                } else {
                    errorMsg += 'Dit betekent meestal:<br><br>' +
                        '• PHP werkt niet op je server<br>' +
                        '• payment-proxy.php heeft een syntax error<br>' +
                        '• PHP errors worden onderdrukt<br><br>' +
                        '<strong>Oplossing:</strong><br>' +
                        '1. Check server error logs<br>' +
                        '2. Verifieer dat PHP is geïnstalleerd en draait<br>' +
                        '3. Controleer of payment-proxy.php correct is geüpload';
                }
                
                throw new Error(errorMsg);
            }
            
            // Check if response is HTML (PHP error page)
            if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html')) {
                // Extract error message from HTML if possible
                const errorMatch = responseText.match(/<title>([^<]+)<\/title>/i) || 
                                  responseText.match(/Fatal error[^<]*/i) ||
                                  responseText.match(/Parse error[^<]*/i);
                const phpError = errorMatch ? errorMatch[0].substring(0, 200) : 'PHP error';
                throw new Error('PHP Error: ' + phpError + '. Check server logs for details.');
            }
            
            if (!response.ok) {
                let errorData;
                try {
                    errorData = JSON.parse(responseText);
                } catch (e) {
                    // If it's not JSON, it might be a PHP error or plain text
                    if (response.status === 404) {
                        throw new Error('404: payment-proxy.php not found. Make sure the file is uploaded to your server.');
                    } else if (response.status === 500) {
                        throw new Error('500: Server error. Check PHP error logs. Response: ' + responseText.substring(0, 200));
                    } else {
                        throw new Error('Server error (Status ' + response.status + '): ' + responseText.substring(0, 200));
                    }
                }
                
                throw new Error(errorData.error || 'Failed to create checkout session');
            }
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response from server. Server might be returning an error page. Response: ' + responseText.substring(0, 200));
            }
            
            // Check if result exists and has success property
            if (!result) {
                throw new Error('No response from server');
            }
            
            // Check if result has success property
            if (result.success === false || (result.success === undefined && result.error)) {
                const errorMsg = result.error || 'Failed to create checkout session';
                throw new Error(errorMsg);
            }
            
            if (!result.sessionId) {
                throw new Error('No session ID received from server. Response: ' + JSON.stringify(result));
            }
            
            if (stripe) {
                // Redirect to Stripe Checkout
                const { error } = await stripe.redirectToCheckout({
                    sessionId: result.sessionId
                });
                
                if (error) {
                    throw new Error('Stripe redirect error: ' + error.message);
                }
            } else {
                throw new Error('Stripe library not loaded');
            }
            
        } catch (error) {
            console.error('Payment error:', error);
            console.error('Error name:', error.name);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            
            let errorMessage = 'Er is een fout opgetreden bij het starten van de betaling.';
            
            // Network errors
            if (error.name === 'TypeError' && (error.message.includes('fetch') || error.message.includes('Failed to fetch'))) {
                const currentEndpoint = window.GameFluxConfig?.API_ENDPOINT || window.location.origin + '/payment-proxy.php';
                const isNetlify = window.location.hostname.includes('netlify.app');
                const isCloudflare = window.location.hostname.includes('pages.dev');
                const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                
                // Log detailed diagnostic info
                console.error('=== FETCH ERROR DIAGNOSTICS ===');
                console.error('Error:', error);
                console.error('API Endpoint:', currentEndpoint);
                console.error('Current URL:', window.location.href);
                console.error('Hostname:', window.location.hostname);
                console.error('Is Netlify:', isNetlify);
                console.error('Is Cloudflare:', isCloudflare);
                console.error('Is Localhost:', isLocalhost);
                console.error('Config:', window.GameFluxConfig);
                console.error('================================');
                
                let specificGuidance = '';
                
                if (isNetlify || isCloudflare) {
                    specificGuidance = '<strong>⚠️ Netlify/Cloudflare Pages heeft GEEN PHP support!</strong><br><br>' +
                        'Je moet een aparte PHP server gebruiken.<br><br>' +
                        '<strong>Huidige configuratie:</strong><br>' +
                        'API Endpoint: <code>' + currentEndpoint + '</code><br><br>' +
                        '<strong>Oplossing:</strong><br>' +
                        '1. Upload payment-proxy.php naar een PHP server<br>' +
                        '2. Pas config.js aan met de juiste <code>PHP_SERVER_URL</code>';
                } else if (isLocalhost) {
                    specificGuidance = '<strong>Localhost detectie:</strong><br><br>' +
                        'Je draait op localhost. Dit betekent:<br>' +
                        '• PHP moet lokaal draaien (bijv. XAMPP, PHP built-in server)<br>' +
                        '• Of gebruik een externe PHP server door <code>config.js</code> aan te passen<br><br>' +
                        '<strong>Oplossing:</strong><br>' +
                        '1. Start PHP server: <code>php -S localhost:8000</code><br>' +
                        '2. Of pas <code>config.js</code> aan met een externe PHP server URL';
                } else {
                    specificGuidance = '<strong>Oplossing:</strong><br>' +
                        '1. Controleer of payment-proxy.php bestaat op je server<br>' +
                        '2. Check server error logs<br>' +
                        '3. Verifieer dat PHP correct werkt op je server<br>' +
                        '4. Controleer of <code>config.js</code> de juiste <code>PHP_SERVER_URL</code> heeft';
                }
                
                errorMessage = '⚠️ Kan geen verbinding maken met de server<br><br>' +
                    '<strong>Mogelijke oorzaken:</strong><br>' +
                    '• payment-proxy.php is niet bereikbaar<br>' +
                    '• PHP server draait niet<br>' +
                    '• CORS probleem<br>' +
                    '• Netwerkprobleem<br>' +
                    '• Verkeerde API endpoint configuratie<br><br>' +
                    specificGuidance;
            } 
            // 404 errors
            else if (error.message.includes('404') || error.message.includes('not found')) {
                errorMessage = '⚠️ Backend bestand niet gevonden<br><br>' +
                    'Het bestand <code>payment-proxy.php</code> ontbreekt op je server.<br><br>' +
                    '<strong>Oplossing:</strong><br>' +
                    'Upload payment-proxy.php naar dezelfde directory als index.html<br>' +
                    'Test daarna: <a href="' + window.location.origin + '/payment-proxy.php" target="_blank" style="color: var(--primary-color);">payment-proxy.php</a>';
            }
            // PHP errors
            else if (error.message.includes('PHP Error') || error.message.includes('Fatal error') || error.message.includes('Parse error')) {
                errorMessage = '⚠️ PHP Server Error<br><br>' +
                    '<strong>Fout:</strong><br>' +
                    '<code style="background: rgba(255,0,0,0.1); padding: 0.5rem; border-radius: 4px; display: block; margin: 0.5rem 0;">' + 
                    error.message.substring(0, 300) + '</code><br><br>' +
                    '<strong>Oplossing:</strong><br>' +
                    '1. Check PHP error logs op je server<br>' +
                    '2. Controleer of PHP correct is geïnstalleerd<br>' +
                    '3. Test payment-proxy.php direct in browser';
            }
            // Stripe errors
            else if (error.message.includes('Stripe')) {
                errorMessage = '⚠️ Stripe Checkout Fout<br><br>' +
                    error.message + '<br><br>' +
                    '<strong>Mogelijke oorzaken:</strong><br>' +
                    '• Stripe secret key is onjuist<br>' +
                    '• Stripe API probleem<br>' +
                    '• Ongeldige checkout session';
            }
            // Other errors
            else {
                errorMessage = '⚠️ ' + error.message + '<br><br>' +
                    '<strong>Debug informatie:</strong><br>' +
                    '• Error: ' + error.name + '<br>' +
                    '• Check console voor meer details';
            }
            
            showMessage('error', errorMessage);
            
            // Re-enable button
            const payButton = document.getElementById('payButton');
            if (payButton) {
                payButton.disabled = false;
                const buttonText = payButton.querySelector('.button-text');
                const buttonLoader = payButton.querySelector('.button-loader');
                if (buttonText) buttonText.style.display = 'block';
                if (buttonLoader) buttonLoader.style.display = 'none';
            }
            
            throw error;
        }
    }
    
    function showTestModeStripe(pkg) {
        // Show info message
        showMessage('info', 
            `💳 <strong>Stripe Checkout</strong><br><br>
            <strong>Pakket:</strong> ${pkg.name}<br>
            <strong>Bedrag:</strong> €${pkg.price}<br><br>
            ℹ️ Een backend server is vereist voor Stripe Checkout.<br><br>
            <a href="https://stripe.com/docs/testing" target="_blank" style="color: var(--primary-color); text-decoration: underline;">📖 Stripe Test Cards (4242 4242 4242 4242)</a>`);
        
        // Re-enable button
        const payButton = document.getElementById('payButton');
        if (payButton) {
            const buttonText = payButton.querySelector('.button-text');
            const buttonLoader = payButton.querySelector('.button-loader');
            payButton.disabled = false;
            if (buttonText) buttonText.style.display = 'block';
            if (buttonLoader) buttonLoader.style.display = 'none';
        }
    }
    
    function showMessage(type, message) {
        const messageDiv = document.getElementById('paymentMessage');
        messageDiv.innerHTML = message;
        messageDiv.className = `payment-message-enhanced ${type}`;
        messageDiv.style.display = 'block';
        
        // Scroll to message
        setTimeout(() => {
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
});

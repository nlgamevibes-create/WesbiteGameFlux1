<?php
/**
 * Stripe Checkout Session Creator
 * Compatibel met PHP 7.4 – 8.3
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

function sendJsonError($message, $code = 500)
{
    if (ob_get_level() > 0) ob_end_clean();
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    }
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendJsonError('PHP Fatal Error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line']);
    }
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Access-Control-Max-Age: 3600');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Alleen POST toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Method not allowed. Use POST.', 405);
}

try {
    // Inkomende JSON
    $input = file_get_contents('php://input');
    if (empty($input)) sendJsonError('No data received', 400);

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) sendJsonError('Invalid JSON: ' . json_last_error_msg(), 400);

    $package = $data['package'] ?? '';
    $amount  = floatval($data['amount'] ?? 0);
    $currency = $data['currency'] ?? 'EUR';
    $discordUsername = $data['discord_username'] ?? '';
    $price = $data['price'] ?? '';

    if (empty($package) || $amount <= 0)
        sendJsonError('Package and amount required.', 400);

    // Stripe key ophalen
    $stripeSecretKey = getenv('STRIPE_SECRET_KEY');
    if (empty($stripeSecretKey) && file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        if (defined('STRIPE_SECRET_KEY')) $stripeSecretKey = STRIPE_SECRET_KEY;
    }

    if (empty($stripeSecretKey) || strpos($stripeSecretKey, 'sk_') !== 0)
        sendJsonError('Stripe secret key not configured or invalid', 500);

    // URL's
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['REQUEST_URI']);
    $baseUrl = $protocol . '://' . $host . rtrim($path, '/');

    $successUrl = $baseUrl . '/payment-success.html?session_id={CHECKOUT_SESSION_ID}';
    $cancelUrl  = $baseUrl . '/index.html?package=' . urlencode($package) . '&price=' . urlencode($price);

    if (!function_exists('curl_init')) sendJsonError('cURL not enabled on this server', 500);

    $amountInCents = intval($amount * 100);
    $sessionData = [
        'payment_method_types[0]' => 'card',
        'line_items[0][price_data][currency]' => strtolower($currency),
        'line_items[0][price_data][product_data][name]' => $package,
        'line_items[0][price_data][product_data][description]' => 'GameFlux Hosting - ' . $package,
        'line_items[0][price_data][unit_amount]' => $amountInCents,
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'metadata[package]' => $package,
        'metadata[discord_username]' => $discordUsername
    ];

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($sessionData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $stripeSecretKey,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) sendJsonError('cURL error: ' . $curlError, 500);
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $msg = $errorData['error']['message'] ?? ('Stripe API error (' . $httpCode . ')');
        sendJsonError($msg, $httpCode);
    }

    $session = json_decode($response, true);
    if (!$session || empty($session['id'])) sendJsonError('Invalid response from Stripe API', 500);

    ob_end_clean();
    echo json_encode(['success' => true, 'sessionId' => $session['id']]);
    exit;

} catch (Throwable $e) {
    sendJsonError('Unexpected error: ' . $e->getMessage(), 500);
}

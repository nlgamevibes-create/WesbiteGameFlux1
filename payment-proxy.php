<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

function sendJsonError($msg, $code = 500)
{
    if (ob_get_level()) ob_end_clean();
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    }
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendJsonError('PHP Fatal Error: ' . $e['message'] . ' in ' . basename($e['file']) . ' line ' . $e['line']);
    }
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Method not allowed. Use POST.', 405);
}

try {
    $input = file_get_contents('php://input');
    if (!$input) sendJsonError('No data received', 400);
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) sendJsonError('Invalid JSON', 400);

    $package = $data['package'] ?? '';
    $amount  = floatval($data['amount'] ?? 0);
    $currency = $data['currency'] ?? 'EUR';
    $discord = $data['discord_username'] ?? '';
    $price = $data['price'] ?? '';

    if (empty($package) || $amount <= 0)
        sendJsonError('Package and amount required.', 400);

    $key = getenv('STRIPE_SECRET_KEY');
    if (!$key && file_exists(__DIR__ . '/config.php')) {
        require __DIR__ . '/config.php';
        if (defined('STRIPE_SECRET_KEY')) $key = STRIPE_SECRET_KEY;
    }
    if (!$key || strpos($key, 'sk_') !== 0)
        sendJsonError('Stripe key missing or invalid.', 500);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['REQUEST_URI']);
    $base = $protocol . '://' . $host . rtrim($path, '/');

    $success = $base . '/payment-success.html?session_id={CHECKOUT_SESSION_ID}';
    $cancel  = $base . '/index.html?package=' . urlencode($package) . '&price=' . urlencode($price);

    if (!function_exists('curl_init')) sendJsonError('cURL not enabled', 500);

    $payload = [
        'payment_method_types[0]' => 'card',
        'line_items[0][price_data][currency]' => strtolower($currency),
        'line_items[0][price_data][product_data][name]' => $package,
        'line_items[0][price_data][product_data][description]' => 'GameFlux Hosting - ' . $package,
        'line_items[0][price_data][unit_amount]' => intval($amount * 100),
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $success,
        'cancel_url' => $cancel,
        'metadata[package]' => $package,
        'metadata[discord_username]' => $discord
    ];

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) sendJsonError('cURL error: ' . $error, 500);
    if ($code !== 200) {
        $err = json_decode($response, true);
        $msg = $err['error']['message'] ?? 'Stripe API error (' . $code . ')';
        sendJsonError($msg, $code);
    }

    $session = json_decode($response, true);
    if (empty($session['id'])) sendJsonError('Invalid response from Stripe', 500);

    ob_end_clean();
    echo json_encode(['success' => true, 'sessionId' => $session['id']]);
    exit;

} catch (Throwable $t) {
    sendJsonError('Unexpected error: ' . $t->getMessage(), 500);
}

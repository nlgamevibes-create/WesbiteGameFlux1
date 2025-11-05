<?php
// Stripe Checkout Session Creator
// Vereist: Stripe Secret Key in environment variable STRIPE_SECRET_KEY

// Prevent any output before headers
ob_start();

// Set error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

// Clear any previous output
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Access-Control-Max-Age: 3600');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get request data
$input = file_get_contents('php://input');

// Log for debugging (remove in production)
error_log('Payment proxy request: ' . $input);

if (empty($input)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No data received'
    ]);
    exit;
}

$data = json_decode($input, true);

if (!$data) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

// Validate required fields
$package = $data['package'] ?? '';
$amount = floatval($data['amount'] ?? 0);
$currency = $data['currency'] ?? 'EUR';
$discordUsername = $data['discord_username'] ?? '';
$price = $data['price'] ?? '';

if (empty($package) || $amount <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Package and amount required. Received: ' . json_encode($data)
    ]);
    exit;
}

// Get Stripe secret key from environment
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');

if (empty($stripeSecretKey)) {
    // Fallback: check if it's set in a config file
    if (file_exists('config.php')) {
        require_once 'config.php';
        if (defined('STRIPE_SECRET_KEY')) {
            $stripeSecretKey = STRIPE_SECRET_KEY;
        }
    }
}

// If still empty, use hardcoded key (for now - in production use environment variable)
if (empty($stripeSecretKey)) {
    $stripeSecretKey = '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';
}

if (empty($stripeSecretKey) || (function_exists('str_starts_with') && !str_starts_with($stripeSecretKey, 'sk_')) || (!function_exists('str_starts_with') && strpos($stripeSecretKey, 'sk_') !== 0)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Stripe secret key not configured or invalid'
    ]);
    exit;
}

// Build base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['REQUEST_URI']);
$baseUrl = $protocol . '://' . $host . rtrim($path, '/');

// Success and cancel URLs
$successUrl = $baseUrl . '/payment-success.html?session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/index.html?package=' . urlencode($package) . '&price=' . urlencode($price);

// Create Stripe checkout session via API
$amountInCents = intval($amount * 100);

$sessionData = [
    'payment_method_types[0]' => 'card',
    'line_items[0][price_data][currency]' => strtolower($currency),
    'line_items[0][price_data][product_data][name]' => $package,
    'line_items[0][price_data][product_data][description]' => 'GameFlux FiveM Server Hosting - ' . $package,
    'line_items[0][price_data][unit_amount]' => $amountInCents,
    'line_items[0][quantity]' => 1,
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'metadata[package]' => $package,
    'metadata[discord_username]' => $discordUsername
];

// Check if cURL is available
if (!function_exists('curl_init')) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'cURL is not enabled on this server'
    ]);
    exit;
}

// Call Stripe API
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($sessionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded'
]);

// Set timeout
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Curl error: ' . $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    ob_end_clean();
    http_response_code($httpCode);
    $errorData = json_decode($response, true);
    
    // Better error message extraction
    if (isset($errorData['error']['message'])) {
        $errorMsg = $errorData['error']['message'];
    } else if (isset($errorData['error'])) {
        $errorMsg = is_string($errorData['error']) ? $errorData['error'] : 'Stripe API error';
    } else {
        $errorMsg = 'Stripe API error (HTTP ' . $httpCode . ')';
        if (!empty($response)) {
            $errorMsg .= ': ' . substr($response, 0, 200);
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => $errorMsg
    ]);
    exit;
}

$session = json_decode($response, true);

if (!$session) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from Stripe API: ' . substr($response, 0, 200)
    ]);
    exit;
}

if (isset($session['id'])) {
    ob_end_clean(); // Clear any buffered output
    echo json_encode([
        'success' => true,
        'sessionId' => $session['id']
    ]);
    exit;
} else {
    http_response_code(500);
    ob_end_clean(); // Clear any buffered output
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create session. Stripe response: ' . json_encode($session)
    ]);
    exit;
}


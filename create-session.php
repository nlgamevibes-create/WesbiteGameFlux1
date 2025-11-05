<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

function sendJsonError($msg, $code = 500) {
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Method not allowed. Use POST.', 405);
}

require_once __DIR__ . '/config.php';
$key = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
if (!$key || strpos($key, 'sk_') !== 0)
    sendJsonError('Stripe secret key missing', 500);

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$package = $data['package'] ?? 'Test';
$amount = floatval($data['amount'] ?? 0);
$currency = $data['currency'] ?? 'EUR';

if ($amount <= 0) sendJsonError('Invalid amount', 400);

$payload = [
    'payment_method_types[0]' => 'card',
    'line_items[0][price_data][currency]' => strtolower($currency),
    'line_items[0][price_data][product_data][name]' => $package,
    'line_items[0][price_data][unit_amount]' => intval($amount * 100),
    'line_items[0][quantity]' => 1,
    'mode' => 'payment',
    'success_url' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/success.html',
    'cancel_url' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/cancel.html'
];

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/x-www-form-urlencoded'
    ]
]);
$res = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http !== 200) sendJsonError('Stripe API error: '.$res, $http);

$session = json_decode($res, true);
echo json_encode(['success' => true, 'sessionId' => $session['id']]);

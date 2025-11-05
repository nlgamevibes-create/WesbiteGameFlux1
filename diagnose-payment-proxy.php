<?php
/**
 * Diagnostic endpoint for payment-proxy.php
 * This helps identify issues with the payment proxy
 * 
 * Usage: Visit https://website.gameflux.nl/diagnose-payment-proxy.php
 */

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// Clear output buffer
ob_end_clean();

// Collect diagnostic information
$diagnostics = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    ],
    'extensions' => [
        'curl' => [
            'enabled' => function_exists('curl_init'),
            'version' => function_exists('curl_version') ? curl_version()['version'] : 'N/A'
        ],
        'json' => [
            'enabled' => function_exists('json_encode'),
        ],
        'openssl' => [
            'enabled' => extension_loaded('openssl'),
        ],
    ],
    'file_checks' => [
        'payment_proxy_exists' => file_exists('payment-proxy.php'),
        'payment_proxy_readable' => file_exists('payment-proxy.php') ? is_readable('payment-proxy.php') : false,
        'config_php_exists' => file_exists('config.php'),
    ],
    'environment' => [
        'stripe_key_set' => !empty(getenv('STRIPE_SECRET_KEY')),
        'error_reporting' => error_reporting(),
        'display_errors' => ini_get('display_errors'),
        'log_errors' => ini_get('log_errors'),
        'error_log' => ini_get('error_log'),
    ],
    'permissions' => [
        'can_write' => is_writable('.'),
        'can_read' => is_readable('.'),
    ]
];

// Test payment-proxy.php syntax if it exists
if (file_exists('payment-proxy.php')) {
    $syntaxCheck = shell_exec('php -l payment-proxy.php 2>&1');
    $diagnostics['syntax_check'] = [
        'command' => 'php -l payment-proxy.php',
        'output' => $syntaxCheck,
        'valid' => strpos($syntaxCheck, 'No syntax errors') !== false
    ];
}

// Test if we can make a basic request to payment-proxy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_payment_proxy'])) {
    $testData = [
        'package' => 'FXServer I',
        'amount' => 2.10,
        'currency' => 'EUR',
        'price' => '2,10'
    ];
    
    // Try to include payment-proxy.php and see what happens
    ob_start();
    try {
        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $testData;
        
        // We can't actually call payment-proxy.php from here easily,
        // but we can check if it's syntactically correct
        $diagnostics['payment_proxy_test'] = [
            'status' => 'Cannot test directly, but syntax check should reveal issues',
            'note' => 'Upload test-payment-proxy.php to test the endpoint directly'
        ];
    } catch (Exception $e) {
        $diagnostics['payment_proxy_test'] = [
            'error' => $e->getMessage()
        ];
    }
    ob_end_clean();
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);


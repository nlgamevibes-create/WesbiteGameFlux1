<?php
// Test endpoint om te controleren of PHP werkt
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Max-Age: 3600');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

ob_end_clean();

echo json_encode([
    'success' => true,
    'message' => 'PHP is working!',
    'php_version' => phpversion(),
    'curl_enabled' => function_exists('curl_init'),
    'json_enabled' => function_exists('json_encode'),
    'timestamp' => date('Y-m-d H:i:s'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
]);


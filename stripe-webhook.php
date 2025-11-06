<?php
/**
 * Stripe Webhook Handler voor Payment Confirmation Emails
 * 
 * Configureer deze webhook in je Stripe Dashboard:
 * - Ga naar Developers > Webhooks
 * - Voeg endpoint toe: https://jouw-domein.nl/stripe-webhook.php
 * - Selecteer events: checkout.session.completed, payment_intent.succeeded
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Load config
require_once __DIR__ . '/config.php';

// Webhook secret key (configureer in Stripe Dashboard)
// Haal deze op uit: Developers > Webhooks > Signing secret
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// Email configuratie
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'info@gameflux.nl');
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@gameflux.nl');
define('FROM_NAME', 'GameFlux');

function sendJsonResponse($data, $code = 200) {
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function logMessage($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data) {
        $log .= ' - ' . json_encode($data);
    }
    $log .= PHP_EOL;
    file_put_contents(__DIR__ . '/webhook.log', $log, FILE_APPEND);
}

function sendConfirmationEmail($customerEmail, $package, $amount, $orderId, $sessionId) {
    $subject = "🎉 Betaling Succesvol - GameFlux Hosting";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #FF6B35; }
            .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .info-row:last-child { border-bottom: none; }
            .label { font-weight: bold; color: #666; }
            .value { color: #333; }
            .button { display: inline-block; background: #5865F2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin: 10px 5px; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Betaling Succesvol!</h1>
                <p>Bedankt voor je bestelling bij GameFlux</p>
            </div>
            <div class='content'>
                <p>Beste klant,</p>
                <p>Je betaling is succesvol verwerkt. Hieronder vind je alle details van je bestelling.</p>
                
                <div class='info-box'>
                    <h3 style='margin-top: 0; color: #FF6B35;'>📋 Bestelgegevens</h3>
                    <div class='info-row'>
                        <span class='label'>Bestelnummer:</span>
                        <span class='value'><strong>{$orderId}</strong></span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Pakket:</span>
                        <span class='value'><strong>{$package}</strong></span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Bedrag:</span>
                        <span class='value'><strong>€" . number_format($amount, 2, ',', '.') . "</strong></span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Status:</span>
                        <span class='value' style='color: #4CAF50;'><strong>✅ Betaald</strong></span>
                    </div>
                </div>
                
                <div class='info-box'>
                    <h3 style='margin-top: 0; color: #FF6B35;'>📋 Volgende Stappen</h3>
                    <ol style='text-align: left;'>
                        <li><strong>Maak een ticket aan in Discord</strong> met je bestelnummer: <strong>{$orderId}</strong></li>
                        <li>Ons team neemt contact met je op binnen 24 uur</li>
                        <li>Je server wordt geactiveerd zodra de betaling is bevestigd</li>
                    </ol>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://discord.gg/zddgyTcpFe' class='button' style='background: #5865F2;'>Maak Ticket in Discord</a>
                    <a href='https://panel.gameflux.nl/auth/login' class='button' style='background: #FF6B35;'>Naar Dashboard</a>
                </div>
                
                <div class='footer'>
                    <p>Met vriendelijke groet,<br>Het GameFlux Team</p>
                    <p>Voor vragen kun je ons bereiken via Discord: <a href='https://discord.gg/zddgyTcpFe'>https://discord.gg/zddgyTcpFe</a></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $sent = mail($customerEmail, $subject, $message, $headers);
    
    if ($sent) {
        logMessage("Confirmation email sent to {$customerEmail} for order {$orderId}");
    } else {
        logMessage("Failed to send confirmation email to {$customerEmail} for order {$orderId}");
    }
    
    return $sent;
}

function sendAdminNotification($customerEmail, $package, $amount, $orderId) {
    $subject = "Nieuwe Bestelling - {$package} - €" . number_format($amount, 2, ',', '.');
    
    $message = "
    Nieuwe bestelling ontvangen:
    
    Bestelnummer: {$orderId}
    Pakket: {$package}
    Bedrag: €" . number_format($amount, 2, ',', '.') . "
    Klant Email: {$customerEmail}
    Datum: " . date('d-m-Y H:i:s') . "
    ";
    
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    mail(ADMIN_EMAIL, $subject, $message, $headers);
}

function sendDiscordWebhook($customerEmail, $package, $amount, $orderId) {
    if (empty(DISCORD_WEBHOOK_URL)) {
        logMessage("Discord webhook URL not configured, skipping Discord notification");
        return false;
    }
    
    $embed = [
        'title' => '💳 Nieuwe Betaling Ontvangen',
        'description' => 'Er is een nieuwe betaling succesvol verwerkt!',
        'color' => 0x4CAF50, // Green color
        'fields' => [
            [
                'name' => '📦 Pakket',
                'value' => $package,
                'inline' => true
            ],
            [
                'name' => '💰 Bedrag',
                'value' => '€' . number_format($amount, 2, ',', '.'),
                'inline' => true
            ],
            [
                'name' => '📧 Klant Email',
                'value' => $customerEmail,
                'inline' => false
            ],
            [
                'name' => '🆔 Bestelnummer',
                'value' => '`' . $orderId . '`',
                'inline' => false
            ],
            [
                'name' => '🕐 Tijdstip',
                'value' => date('d-m-Y H:i:s'),
                'inline' => false
            ]
        ],
        'footer' => [
            'text' => 'GameFlux Payment System'
        ],
        'timestamp' => date('c')
    ];
    
    $payload = [
        'embeds' => [$embed]
    ];
    
    $ch = curl_init(DISCORD_WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logMessage("Discord webhook cURL error: " . $error);
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        logMessage("Discord webhook sent successfully for order {$orderId}");
        return true;
    } else {
        logMessage("Discord webhook failed with HTTP code {$httpCode}: " . $response);
        return false;
    }
}

// Get raw POST body
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload)) {
    logMessage("Empty webhook payload");
    sendJsonResponse(['error' => 'No payload'], 400);
}

// Verify webhook signature (if webhook secret is configured)
if (STRIPE_WEBHOOK_SECRET && !empty($sig_header)) {
    // Simple signature verification (for production, use Stripe PHP library)
    $timestamp = null;
    $signatures = [];
    
    if (preg_match('/t=(\d+),v1=([^,]+)/', $sig_header, $matches)) {
        $timestamp = $matches[1];
        $signatures[] = $matches[2];
    }
    
    // Check timestamp (reject if older than 5 minutes)
    if ($timestamp && (time() - $timestamp > 300)) {
        logMessage("Webhook timestamp too old");
        sendJsonResponse(['error' => 'Timestamp too old'], 400);
    }
    
    // Verify signature
    $signed_payload = $timestamp . '.' . $payload;
    $expected_signature = hash_hmac('sha256', $signed_payload, STRIPE_WEBHOOK_SECRET);
    
    $valid = false;
    foreach ($signatures as $signature) {
        if (hash_equals($expected_signature, $signature)) {
            $valid = true;
            break;
        }
    }
    
    if (!$valid) {
        logMessage("Webhook signature verification failed");
        sendJsonResponse(['error' => 'Invalid signature'], 400);
    }
}

// Decode the JSON payload
$event = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    logMessage("Invalid JSON in webhook payload");
    sendJsonResponse(['error' => 'Invalid JSON'], 400);
}

logMessage("Webhook event received", ['type' => $event['type'] ?? 'unknown']);

// Handle different event types
$eventType = $event['type'] ?? '';

switch ($eventType) {
    case 'checkout.session.completed':
        $session = $event['data']['object'];
        $customerEmail = $session['customer_details']['email'] ?? $session['customer_email'] ?? '';
        $amount = ($session['amount_total'] ?? 0) / 100;
        $currency = strtoupper($session['currency'] ?? 'eur');
        $sessionId = $session['id'] ?? '';
        $package = $session['metadata']['package'] ?? 'Onbekend pakket';
        $orderId = $sessionId; // Use session ID as order ID
        
        if ($customerEmail) {
            sendConfirmationEmail($customerEmail, $package, $amount, $orderId, $sessionId);
            sendAdminNotification($customerEmail, $package, $amount, $orderId);
            sendDiscordWebhook($customerEmail, $package, $amount, $orderId);
            logMessage("Processed checkout.session.completed", [
                'email' => $customerEmail,
                'package' => $package,
                'amount' => $amount
            ]);
        }
        break;
        
    case 'payment_intent.succeeded':
        $paymentIntent = $event['data']['object'];
        $customerEmail = $paymentIntent['receipt_email'] ?? '';
        $amount = ($paymentIntent['amount'] ?? 0) / 100;
        $sessionId = $paymentIntent['id'] ?? '';
        
        if ($customerEmail) {
            $package = $paymentIntent['metadata']['package'] ?? 'Onbekend pakket';
            sendConfirmationEmail($customerEmail, $package, $amount, $sessionId, $sessionId);
            sendAdminNotification($customerEmail, $package, $amount, $sessionId);
            sendDiscordWebhook($customerEmail, $package, $amount, $sessionId);
            logMessage("Processed payment_intent.succeeded", [
                'email' => $customerEmail,
                'package' => $package,
                'amount' => $amount
            ]);
        }
        break;
        
    default:
        logMessage("Unhandled event type: {$eventType}");
}

sendJsonResponse(['received' => true]);


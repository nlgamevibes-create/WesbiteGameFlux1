<?php
// Gebruik hier je eigen nieuwe sleutel uit Stripe (nooit publiek tonen!)
define('STRIPE_SECRET_KEY', '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');

// Discord Webhook URL - Voeg hier je Discord webhook URL toe
// Haal deze op via: Discord Server > Server Settings > Integrations > Webhooks > New Webhook
define('DISCORD_WEBHOOK_URL', getenv('DISCORD_WEBHOOK_URL') ?: 'https://discord.com/api/webhooks/1435336888167829535/Du6REgJJZ1REHcH-vEK046AxLlliI9ERoA9PSULwk2AHbwkFem3wP1lfpdha9YcsBlER');

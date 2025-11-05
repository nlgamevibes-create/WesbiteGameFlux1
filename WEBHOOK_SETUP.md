# Stripe Webhook Setup Instructies

## Email Bevestigingen Instellen

De webhook handler (`stripe-webhook.php`) stuurt automatisch bevestigingsemails naar klanten wanneer een betaling succesvol is.

### Stap 1: Webhook Configureren in Stripe Dashboard

1. Ga naar je [Stripe Dashboard](https://dashboard.stripe.com)
2. Navigeer naar **Developers** > **Webhooks**
3. Klik op **Add endpoint**
4. Voer je webhook URL in:
   ```
   https://jouw-domein.nl/stripe-webhook.php
   ```
5. Selecteer de volgende events:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
6. Klik op **Add endpoint**
7. Kopieer de **Signing secret** (begint met `whsec_`)

### Stap 2: Webhook Secret Configureren

Voeg de webhook secret toe aan je `config.php` of als environment variable:

**Option 1: Via config.php**
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');
```

**Option 2: Via Environment Variable**
```bash
export STRIPE_WEBHOOK_SECRET='whsec_...'
```

### Stap 3: Email Configuratie

Pas de email instellingen aan in `stripe-webhook.php` of via environment variables:

```php
define('ADMIN_EMAIL', 'info@gameflux.nl');      // Email voor admin notificaties
define('FROM_EMAIL', 'noreply@gameflux.nl');    // Verzender email
define('FROM_NAME', 'GameFlux');                 // Verzender naam
```

### Stap 4: Test de Webhook

1. Gebruik de Stripe CLI om de webhook lokaal te testen:
   ```bash
   stripe listen --forward-to localhost/stripe-webhook.php
   ```

2. Of gebruik de test tool in Stripe Dashboard:
   - Ga naar **Developers** > **Webhooks**
   - Klik op je webhook endpoint
   - Klik op **Send test webhook**

### Belangrijke Opmerkingen

- **Stripe Payment Links**: Stripe stuurt automatisch receipt emails via hun eigen systeem. Deze webhook voegt extra custom confirmation emails toe.
- **Email Server**: Zorg dat je PHP server email kan versturen (SMTP of mail() functie).
- **Logs**: Webhook logs worden opgeslagen in `webhook.log` voor debugging.

### Troubleshooting

- **Geen emails ontvangen**: Check of je email server correct is geconfigureerd
- **Webhook wordt niet aangeroepen**: Controleer of de URL correct is en bereikbaar is
- **Signature verification failed**: Controleer of de webhook secret correct is ingesteld
- **Check logs**: Bekijk `webhook.log` voor details over webhook events

### Alternatief: Stripe's Automatische Emails

Stripe stuurt standaard receipt emails voor alle betalingen. Deze kun je aanpassen in:
- **Stripe Dashboard** > **Settings** > **Emails**
- Hier kun je de receipt email templates aanpassen


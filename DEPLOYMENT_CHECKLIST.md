# Payment Proxy Deployment Checklist

## Issue: Empty Server Response

The error "Server returned empty response" indicates that `payment-proxy.php` is either:
1. Not uploaded to the server
2. Has a syntax error
3. Encountering a fatal error that's not being caught
4. PHP is not configured correctly

## ✅ Fixed Files

1. **`payment-proxy.php`** - Enhanced error handling:
   - Added comprehensive error catching
   - Improved fatal error handler
   - Better header management
   - Try-catch blocks for exceptions
   - Always returns JSON (never empty)

2. **`diagnose-payment-proxy.php`** - Diagnostic tool to test your server

## 📋 Deployment Steps

### Step 1: Upload Files to PHP Server

Upload these files to `https://website.gameflux.nl`:
- ✅ `payment-proxy.php` (updated version)
- ✅ `diagnose-payment-proxy.php` (new diagnostic tool)
- ✅ `test-payment-proxy.php` (existing test file)

### Step 2: Test PHP Server

1. **Test basic PHP:**
   ```
   https://website.gameflux.nl/test-payment-proxy.php
   ```
   Should return: `{"success":true,"message":"PHP is working!",...}`

2. **Run diagnostics:**
   ```
   https://website.gameflux.nl/diagnose-payment-proxy.php
   ```
   This will show:
   - PHP version
   - Required extensions (cURL, JSON, OpenSSL)
   - File permissions
   - Syntax errors
   - Environment variables

### Step 3: Test Payment Proxy

Use browser console or Postman to test:

```javascript
fetch('https://website.gameflux.nl/payment-proxy.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        package: 'FXServer I',
        amount: 2.10,
        currency: 'EUR',
        price: '2,10'
    })
})
.then(r => r.text())
.then(console.log)
.catch(console.error);
```

**Expected response:**
- Success: `{"success":true,"sessionId":"cs_test_..."}`
- Error: `{"success":false,"error":"..."}` (should NEVER be empty!)

### Step 4: Check Server Logs

If you still get empty responses, check:
- PHP error logs (location in `diagnose-payment-proxy.php` output)
- Web server error logs
- Check if `display_errors` is enabled (should be OFF in production)

### Step 5: Verify Configuration

1. **Stripe Secret Key:**
   - Set `STRIPE_SECRET_KEY` environment variable on server
   - OR create `config.php` with `define('STRIPE_SECRET_KEY', 'sk_live_...')`
   - OR the file will use the hardcoded key (not recommended for production)

2. **CORS:**
   - The file already sets CORS headers
   - Make sure your server isn't blocking them

3. **File Permissions:**
   - `payment-proxy.php` should be readable (644 or 755)
   - Server should be able to execute PHP files

## 🔍 Troubleshooting

### Empty Response Still Occurs?

1. **Check PHP syntax:**
   ```bash
   php -l payment-proxy.php
   ```
   Should show: `No syntax errors detected`

2. **Enable error logging:**
   - Check `diagnose-payment-proxy.php` for error log location
   - View logs after making a request

3. **Test with simple endpoint:**
   - First test `test-payment-proxy.php` (should work)
   - Then test `payment-proxy.php` (might fail)

4. **Check server configuration:**
   - PHP version >= 7.0 required
   - cURL extension must be enabled
   - `allow_url_fopen` should be enabled

### Common Issues

| Issue | Solution |
|-------|----------|
| 404 Not Found | File not uploaded or wrong path |
| 500 Internal Error | Check PHP error logs |
| Empty response | PHP fatal error - check logs |
| CORS error | Check CORS headers in response |
| cURL error | Enable cURL extension |

## 📝 Files to Upload

1. `payment-proxy.php` - Main payment handler
2. `diagnose-payment-proxy.php` - Diagnostic tool
3. `test-payment-proxy.php` - Simple PHP test

## ⚠️ Security Notes

1. **Remove hardcoded Stripe key** from `payment-proxy.php` before production
2. Use environment variables or secure config file
3. Enable HTTPS only
4. Set proper file permissions (644 for files, 755 for directories)

## ✅ Verification

After deployment, verify:
- [ ] `test-payment-proxy.php` returns JSON
- [ ] `diagnose-payment-proxy.php` shows all checks passing
- [ ] `payment-proxy.php` returns JSON (not empty)
- [ ] Payment flow works end-to-end
- [ ] No errors in browser console
- [ ] No errors in server logs


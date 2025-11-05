# Upload Instructions - Fix 404 Error

## ❌ Current Problem

**404 Not Found** for `https://website.gameflux.nl/payment-proxy.php`

This means the file is **not uploaded** to your PHP server yet.

## ✅ Solution: Upload Files

### Step 1: Upload These Files to `https://website.gameflux.nl`

You need to upload these files to your PHP server (`website.gameflux.nl`):

1. **`payment-proxy.php`** ← Main file (required)
2. **`test-payment-proxy.php`** ← Test if PHP works (optional but helpful)
3. **`diagnose-payment-proxy.php`** ← Diagnostic tool (optional but helpful)

### Step 2: Upload Method

**Option A: FTP/SFTP**
- Connect to your server via FTP
- Upload files to the **web root** (usually `public_html`, `www`, or `html` folder)
- Make sure files are in the same directory as `index.html` (if it exists)

**Option B: cPanel File Manager**
- Login to cPanel
- Go to File Manager
- Navigate to `public_html` (or your web root)
- Upload the PHP files

**Option C: SSH/SCP**
```bash
scp payment-proxy.php user@website.gameflux.nl:/path/to/webroot/
scp test-payment-proxy.php user@website.gameflux.nl:/path/to/webroot/
```

### Step 3: Verify File Location

The file should be accessible at:
```
https://website.gameflux.nl/payment-proxy.php
```

**NOT:**
- ❌ `https://website.gameflux.nl/subfolder/payment-proxy.php`
- ❌ `https://website.gameflux.nl/payment-proxy.php/index.php`
- ✅ `https://website.gameflux.nl/payment-proxy.php` (correct)

### Step 4: Test After Upload

1. **Test basic PHP:**
   ```
   https://website.gameflux.nl/test-payment-proxy.php
   ```
   Should return JSON, not HTML or 404.

2. **Test payment proxy:**
   ```
   https://website.gameflux.nl/payment-proxy.php
   ```
   Should return JSON error (method not allowed for GET), NOT 404.

3. **Check file permissions:**
   - Files should be readable: `644` or `755`
   - PHP should be able to execute them

## 🔍 Troubleshooting

### Still Getting 404?

1. **Check file exists:**
   - Use FTP/cPanel to verify file is actually uploaded
   - Check file name spelling (case-sensitive on Linux servers)
   - Make sure it's `payment-proxy.php` not `payment-proxy.php.txt`

2. **Check file location:**
   - File must be in the **web root** directory
   - Not in a subdirectory unless you update `config.js`

3. **Check PHP is enabled:**
   - Visit: `https://website.gameflux.nl/test-payment-proxy.php`
   - If this also returns 404, PHP might not be configured

4. **Check server configuration:**
   - Some servers require `.htaccess` to allow PHP execution
   - Check if other PHP files work on the server

### Getting 500 Error Instead?

Good! This means the file exists but has an error. Check:
- PHP error logs
- File permissions
- PHP version (needs PHP 7.0+)

### Getting "Method not allowed" Error?

Perfect! This means the file exists and is working. The error is expected for GET requests.

## 📝 Quick Checklist

- [ ] `payment-proxy.php` uploaded to `website.gameflux.nl`
- [ ] File is in web root directory
- [ ] File name is exactly `payment-proxy.php` (case-sensitive)
- [ ] File permissions are correct (644 or 755)
- [ ] `test-payment-proxy.php` returns JSON (not 404)
- [ ] `payment-proxy.php` returns JSON error (not 404)

## 🎯 Expected Results

**Before upload:**
- ❌ 404 Not Found
- ❌ "Page not found" HTML response

**After upload:**
- ✅ JSON response (even if it's an error, it's JSON!)
- ✅ No 404 errors
- ✅ Can test payment flow


# Netlify Deployment Guide - GameFlux Website

## Problem: 404 Error on Netlify

If you're seeing a "Site not found" or 404 error on Netlify, follow these steps:

## Solution Steps

### 1. Verify Netlify Dashboard Settings

In your Netlify dashboard:
- **Publish directory**: Should be set to `.` (dot) or left empty
- **Build command**: Leave empty (no build needed for static site)
- **Base directory**: Leave empty

### 2. Push Files to GitHub

Make sure all files are committed and pushed to your repository:

```bash
git add .
git commit -m "Add Netlify configuration"
git push origin main
```

### 3. Connect Repository to Netlify

If not already connected:
1. Go to Netlify Dashboard
2. Click "Add new site" → "Import an existing project"
3. Select "GitHub" and authorize
4. Choose your repository: `nlgamevibes-create/WesbiteGameFlux1`
5. Configure build settings:
   - **Publish directory**: `.` (or leave empty)
   - **Build command**: (leave empty)
6. Click "Deploy site"

### 4. Important: PHP Files Don't Work on Netlify

⚠️ **Netlify is a static hosting service - it does NOT support PHP!**

Your `payment-proxy.php` file needs to be hosted on a separate PHP server.

**Current Configuration:**
- Your `config.js` is already set up to use an external PHP server
- It's configured to use: `https://website.gameflux.nl`

**To Fix:**
1. Upload `payment-proxy.php` to your PHP server (e.g., `website.gameflux.nl`)
2. Make sure the PHP server is accessible and has CORS enabled
3. Verify `config.js` has the correct `PHP_SERVER_URL`:
   ```javascript
   const PHP_SERVER_URL = 'https://website.gameflux.nl';
   ```

### 5. Verify Files Are Deployed

After deployment, check that these files exist:
- ✅ `index.html`
- ✅ `styles.css`
- ✅ `script.js`
- ✅ `checkout.js`
- ✅ `config.js`
- ✅ `logo.png`
- ✅ `payment-success.html`
- ✅ `netlify.toml`

### 6. Test Your Site

After deployment:
1. Visit your Netlify URL
2. The homepage should load (`index.html`)
3. Check browser console for any errors
4. Test the checkout flow (will need PHP server for payment)

## Troubleshooting

### Still seeing 404?

1. **Check Netlify Deploy Logs:**
   - Go to Netlify Dashboard → Your Site → Deploys
   - Check if there are any build errors

2. **Verify File Structure:**
   - All files should be in the root directory
   - `index.html` must be in the root

3. **Clear Cache:**
   - In Netlify Dashboard → Site settings → Build & deploy → Clear cache and retry deploy

4. **Check Branch:**
   - Make sure Netlify is deploying from the correct branch (usually `main`)

### PHP Backend Not Working?

1. **Verify PHP Server:**
   - Test `https://website.gameflux.nl/payment-proxy.php` directly
   - Should return JSON (not HTML)

2. **Check CORS:**
   - PHP server must allow requests from your Netlify domain
   - Check `payment-proxy.php` has CORS headers enabled

3. **Environment Variables:**
   - Make sure `STRIPE_SECRET_KEY` is set on your PHP server
   - Don't hardcode keys in production!

## Files Included

- ✅ `netlify.toml` - Netlify configuration
- ✅ `_redirects` - Redirect rules (empty for now)
- ✅ `config.js` - Already configured for Netlify + external PHP

## Next Steps

1. Commit and push the new `netlify.toml` file
2. Redeploy on Netlify
3. Verify your PHP server is accessible
4. Test the full payment flow


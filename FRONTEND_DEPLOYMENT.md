# Envisage Frontend - Production Deployment Guide

## 🚀 Quick Deploy to cPanel

### Prerequisites
- Node.js installed locally
- Frontend configured and tested locally
- cPanel access with file manager

### Step 1: Build Production Bundle

```powershell
cd c:\wamp64\www\Envisage\frontend
npm run build
```

This creates an optimized production build in the `.next` folder.

### Step 2: Create Production Environment File

Create `frontend/.env.production` with:

```bash
NEXT_PUBLIC_API_URL=https://envisagezm.com/api
NEXT_PUBLIC_APP_URL=https://envisagezm.com
NEXT_PUBLIC_APP_NAME="Envisage Marketplace"
NEXT_PUBLIC_APP_DESCRIPTION="Buy and sell items securely with escrow protection"
NEXT_PUBLIC_ENABLE_ANALYTICS=true
```

### Step 3: Export Static Site (Option A - Recommended for cPanel)

For static hosting on cPanel:

1. Update `next.config.js`:
```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true
  }
}

module.exports = nextConfig
```

2. Build and export:
```powershell
npm run build
```

3. The `out` folder contains your static site

### Step 4: Deploy to cPanel

**Option A: Static Export (Easiest)**
1. ZIP the `out` folder
2. Upload to cPanel File Manager
3. Extract to `/home/envithcy/public_html/` or subdomain folder
4. Done! Visit https://envisagezm.com

**Option B: Full Node.js (Advanced)**
1. Create Node.js app in cPanel
2. Upload all frontend files
3. Run `npm install --production`
4. Set entry point to `server.js`
5. Start the application

### Step 5: Configure Domain

**Main Domain:**
- Extract to `/home/envithcy/public_html/`
- API accessible at `/api` (already configured)

**Subdomain (e.g., shop.envisagezm.com):**
1. Create subdomain in cPanel
2. Extract frontend to subdomain's root folder
3. Update CORS in backend to allow subdomain

## 🔧 Alternative: Deploy to Vercel (Recommended)

Vercel is optimized for Next.js and offers:
- Automatic deployments
- SSL certificates
- CDN worldwide
- Zero configuration

### Deploy to Vercel:

```powershell
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Deploy
cd c:\wamp64\www\Envisage\frontend
vercel --prod
```

Add environment variables in Vercel dashboard:
- `NEXT_PUBLIC_API_URL` = `https://envisagezm.com/api`
- `NEXT_PUBLIC_APP_URL` = `https://your-vercel-domain.vercel.app`

Update CORS in backend `.env`:
```bash
FRONTEND_URL=https://your-vercel-domain.vercel.app
```

## 📝 Post-Deployment Checklist

- [ ] Frontend loads correctly
- [ ] Products display from API
- [ ] Images load properly
- [ ] Authentication works
- [ ] Cart functionality works
- [ ] Checkout process works
- [ ] SSL certificate active
- [ ] CORS configured correctly
- [ ] All API endpoints accessible

## 🐛 Troubleshooting

**Issue: Frontend shows but no products**
- Check CORS settings in backend
- Verify API URL in `.env.production`
- Check browser console for errors

**Issue: Images not loading**
- Ensure `images.unoptimized: true` for static export
- Check storage symlink on backend
- Verify image URLs in API response

**Issue: 404 errors on refresh**
- Add `.htaccess` for SPA routing:
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

## 🎯 Performance Optimization

1. Enable caching in cPanel
2. Use Cloudflare CDN
3. Compress images before upload
4. Enable gzip compression
5. Minify CSS/JS (done by Next.js)

---

**Need help?** Check the full documentation or contact support.

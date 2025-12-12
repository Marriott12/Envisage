# Envisage Frontend - Deployment Guide

## Prerequisites

- Node.js 18+ and npm 9+
- Backend API running and accessible
- Domain name and SSL certificate (production)
- CDN configured (optional but recommended)

## Environment Configuration

### 1. Create Environment File

Copy the example environment file and configure for your environment:

```bash
cp .env.example .env.local
```

### 2. Required Environment Variables

#### Production URLs
```env
NEXT_PUBLIC_SITE_URL=https://envisage.com
NEXT_PUBLIC_API_URL=https://api.envisage.com
```

#### Analytics & Monitoring (Required for Phase 6)
```env
NEXT_PUBLIC_SENTRY_DSN=https://xxx@xxx.ingest.sentry.io/xxx
NEXT_PUBLIC_POSTHOG_KEY=phc_xxxxx
NEXT_PUBLIC_POSTHOG_HOST=https://app.posthog.com
SENTRY_DEV_MODE=false
POSTHOG_DEV_MODE=false
```

**Get API Keys:**
- Sentry: https://sentry.io/ (Create project → Get DSN)
- PostHog: https://posthog.com/ (Create project → Get API key)

#### Google Services (Required for Phase 3 & 5)
```env
NEXT_PUBLIC_GOOGLE_PLACES_API_KEY=your_key_here
NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=your_key_here
```

**Get API Keys:**
- Google Cloud Console: https://console.cloud.google.com/
- Enable APIs: Places API, Maps JavaScript API
- Create credentials → API key
- Restrict key to your domain

#### Internationalization (Phase 10)
```env
NEXT_PUBLIC_DEFAULT_LOCALE=en
NEXT_PUBLIC_SUPPORTED_LOCALES=en,es,fr,de,ar
```

#### Feature Flags
```env
NEXT_PUBLIC_ENABLE_VOICE_SEARCH=true
NEXT_PUBLIC_ENABLE_VISUAL_SEARCH=true
NEXT_PUBLIC_ENABLE_AR_PREVIEW=false
NEXT_PUBLIC_ENABLE_ANALYTICS=true
NEXT_PUBLIC_ENABLE_PWA=true
```

## Pre-Deployment Checklist

### 1. Install Dependencies
```bash
npm install
```

### 2. Run Tests
```bash
# Unit tests
npm run test

# E2E tests
npm run test:e2e

# Check coverage
npm run test:coverage
```

### 3. Run Linting
```bash
npm run lint
```

### 4. Build for Production
```bash
npm run build
```

### 5. Test Production Build Locally
```bash
npm run start
```

Visit `http://localhost:3000` to verify the build.

## Deployment Options

### Option 1: Vercel (Recommended)

1. **Install Vercel CLI**
```bash
npm install -g vercel
```

2. **Login**
```bash
vercel login
```

3. **Deploy**
```bash
# Preview deployment
vercel

# Production deployment
vercel --prod
```

4. **Configure Environment Variables**
- Go to Vercel Dashboard → Project Settings → Environment Variables
- Add all required variables from `.env.example`
- Separate values for Development, Preview, and Production

5. **Configure Build Settings**
- Framework Preset: Next.js
- Build Command: `npm run build`
- Output Directory: `.next`
- Install Command: `npm install`

### Option 2: Netlify

1. **Install Netlify CLI**
```bash
npm install -g netlify-cli
```

2. **Login**
```bash
netlify login
```

3. **Deploy**
```bash
netlify deploy --prod
```

4. **Configuration** (netlify.toml)
```toml
[build]
  command = "npm run build"
  publish = ".next"

[[plugins]]
  package = "@netlify/plugin-nextjs"
```

### Option 3: Docker

1. **Create Dockerfile**
```dockerfile
FROM node:18-alpine AS base

# Install dependencies only when needed
FROM base AS deps
RUN apk add --no-cache libc6-compat
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# Rebuild the source code only when needed
FROM base AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Set environment variables for build
ARG NEXT_PUBLIC_SITE_URL
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_SITE_URL=$NEXT_PUBLIC_SITE_URL
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL

RUN npm run build

# Production image
FROM base AS runner
WORKDIR /app

ENV NODE_ENV production

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

USER nextjs

EXPOSE 3000

ENV PORT 3000

CMD ["node", "server.js"]
```

2. **Build and Run**
```bash
# Build image
docker build -t envisage-frontend .

# Run container
docker run -p 3000:3000 --env-file .env.local envisage-frontend
```

### Option 4: Traditional Hosting (VPS/Dedicated)

1. **Install Node.js on Server**
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

2. **Install PM2**
```bash
npm install -g pm2
```

3. **Upload Files**
```bash
rsync -avz --exclude 'node_modules' ./ user@server:/var/www/envisage
```

4. **Install Dependencies & Build**
```bash
ssh user@server
cd /var/www/envisage
npm install
npm run build
```

5. **Start with PM2**
```bash
pm2 start npm --name "envisage" -- start
pm2 save
pm2 startup
```

6. **Configure Nginx**
```nginx
server {
    listen 80;
    server_name envisage.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

## Post-Deployment Steps

### 1. Verify Deployment
- [ ] Site loads correctly
- [ ] All images display
- [ ] Search functionality works
- [ ] Checkout flow completes
- [ ] Analytics tracking active
- [ ] Language switcher works
- [ ] Mobile responsive
- [ ] PWA installs correctly

### 2. Performance Monitoring
- [ ] Sentry capturing errors
- [ ] PostHog recording events
- [ ] Core Web Vitals in acceptable range
- [ ] Lighthouse score > 90

### 3. SEO Setup
- [ ] Submit sitemap to Google Search Console
- [ ] Verify robots.txt accessible
- [ ] Check structured data with Rich Results Test
- [ ] Verify Open Graph tags
- [ ] Test Twitter Card preview

### 4. CDN Configuration (Optional)
- Configure Cloudflare or similar CDN
- Enable caching for static assets
- Set cache rules for API responses
- Enable HTTP/3 and Brotli compression

### 5. Monitoring & Alerts
- Set up uptime monitoring (UptimeRobot, Pingdom)
- Configure Sentry alerts for critical errors
- Set up performance budget alerts
- Monitor API response times

## Rollback Procedure

### Vercel
```bash
vercel rollback
```

### Docker
```bash
docker pull envisage-frontend:previous
docker stop envisage-frontend
docker run -d --name envisage-frontend envisage-frontend:previous
```

### PM2
```bash
pm2 stop envisage
git checkout previous-commit
npm install
npm run build
pm2 restart envisage
```

## Troubleshooting

### Build Fails
- Check Node.js version (requires 18+)
- Clear `.next` folder: `rm -rf .next`
- Clear node_modules: `rm -rf node_modules && npm install`
- Check for TypeScript errors: `npm run type-check`

### Runtime Errors
- Check Sentry for error details
- Verify environment variables are set
- Check API connectivity
- Review server logs

### Performance Issues
- Enable caching in production
- Optimize images (use Next.js Image component)
- Enable compression (Brotli/Gzip)
- Use CDN for static assets
- Check bundle size: `npm run analyze`

## Security Best Practices

1. **Environment Variables**
   - Never commit `.env.local` to Git
   - Use secrets management (Vercel Secrets, AWS Secrets Manager)
   - Rotate API keys regularly

2. **Headers**
   - Configure security headers in `next.config.js`
   - Enable CSP (Content Security Policy)
   - Set CORS policies

3. **Dependencies**
   - Run `npm audit` regularly
   - Update dependencies: `npm update`
   - Use Dependabot for automated updates

4. **SSL/TLS**
   - Use HTTPS only in production
   - Enable HSTS headers
   - Use TLS 1.3

## Support

For issues or questions:
- Check [TESTING.md](./TESTING.md) for testing documentation
- Review [README.md](./README.md) for development setup
- Contact: support@envisage.com

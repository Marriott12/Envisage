# Envisage Frontend - Quick Start Guide

## For Developers - Get Running in 5 Minutes

### 1. Prerequisites Check
```bash
node --version  # Should be 18+
npm --version   # Should be 9+
```

### 2. Install Dependencies
```bash
cd frontend
npm install
```

### 3. Create Environment File
```bash
# Copy example
cp .env.example .env.local

# Minimum required for local development
# Edit .env.local and add:
NEXT_PUBLIC_SITE_URL=http://localhost:3000
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

### 4. Start Development Server
```bash
npm run dev
```

Visit: http://localhost:3000

## Essential Commands

```bash
# Development
npm run dev              # Start dev server (port 3000)
npm run build            # Production build
npm run start            # Start production server

# Testing
npm run test             # Run all unit tests
npm run test:e2e         # Run E2E tests
npm run storybook        # Component documentation

# Code Quality
npm run lint             # Check code style
npm run type-check       # TypeScript validation
```

## Key Features to Test

1. **Search** - Try voice search, visual search, filters
2. **Products** - View 360° images, compare products
3. **Cart** - Add items, apply coupons, recover abandoned cart
4. **Checkout** - Multi-step flow with address autocomplete
5. **Mobile** - Test on mobile device or Chrome DevTools
6. **Accessibility** - Try keyboard navigation (Tab key)
7. **i18n** - Switch languages using language selector

## Project Structure (Simplified)

```
frontend/
├── app/              # Pages and routes
├── components/       # React components
├── hooks/           # Custom hooks
├── stores/          # State management
├── styles/          # CSS files
└── locales/         # Translations
```

## Need Help?

- **Full Documentation**: [README.md](./README.md)
- **Deployment Guide**: [DEPLOYMENT.md](./DEPLOYMENT.md)
- **Testing Guide**: [TESTING.md](./TESTING.md)

## Common Issues

**Port 3000 already in use:**
```bash
# Windows
netstat -ano | findstr :3000
taskkill /PID <PID> /F

# Kill and restart
npm run dev
```

**Build fails:**
```bash
# Clear cache and rebuild
rm -rf .next node_modules
npm install
npm run build
```

**TypeScript errors:**
```bash
npm run type-check
```

## Quick Tips

- Use **Ctrl+Click** on imports to jump to file
- Run **tests in watch mode** during development
- Check **Storybook** for component examples
- Use **browser DevTools** for debugging
- Enable **React DevTools** extension

Happy coding! 🚀

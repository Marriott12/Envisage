# 🧪 Frontend Testing Guide

## ✅ Server Status
**Server Running:** http://localhost:3000  
**API Connected:** https://envisagezm.com/api  
**Status:** ✅ Operational

---

## 📋 All Available Pages

### 🏠 **Public Pages** (No login required)

| Route | Status | Purpose | Test Steps |
|-------|--------|---------|------------|
| `/` | ✅ Compiled | Homepage with featured products | 1. Visit http://localhost:3000<br>2. Check products display<br>3. Verify navigation works |
| `/marketplace` | ✅ Compiled | Full product catalog | 1. Visit http://localhost:3000/marketplace<br>2. Browse all 15 products<br>3. Test filters/search<br>4. Click product details |
| `/marketplace/[id]` | Ready | Individual product details | 1. Click any product<br>2. View full description<br>3. Test "Add to Cart" button |
| `/login` | ✅ Compiled | User login page | 1. Visit http://localhost:3000/login<br>2. Try login with:<br>- admin@envisagezm.com / Admin@2025<br>- john@example.com / Buyer@2025 |
| `/register` | ✅ Compiled | New user registration | 1. Visit http://localhost:3000/register<br>2. Fill form (name, email, password)<br>3. Select role: Customer or Seller |
| `/blog` | Ready | Blog/articles section | 1. Visit http://localhost:3000/blog<br>2. View articles |

---

### 🔐 **Protected Pages** (Login required)

| Route | Status | Purpose | Login Required | Test Steps |
|-------|--------|---------|----------------|------------|
| `/dashboard` | Ready | User dashboard | ✅ Yes | 1. Login first<br>2. Visit http://localhost:3000/dashboard<br>3. View stats/overview |
| `/profile` | Ready | User profile settings | ✅ Yes | 1. Login<br>2. Visit http://localhost:3000/profile<br>3. Edit profile info |
| `/cart` | Ready | Shopping cart | ✅ Yes | 1. Add products to cart<br>2. Visit http://localhost:3000/cart<br>3. Update quantities<br>4. Proceed to checkout |
| `/checkout` | Ready | Payment & checkout | ✅ Yes | 1. Have items in cart<br>2. Visit http://localhost:3000/checkout<br>3. Fill shipping info<br>4. Test payment (Stripe test mode) |
| `/orders` | Ready | Order history | ✅ Yes | 1. Login<br>2. Visit http://localhost:3000/orders<br>3. View past orders |
| `/favorites` | Ready | Saved/wishlisted items | ✅ Yes | 1. Login<br>2. Visit http://localhost:3000/favorites<br>3. View favorited products |
| `/notifications` | Ready | User notifications | ✅ Yes | 1. Login<br>2. Visit http://localhost:3000/notifications<br>3. Check alerts |

---

### 🛍️ **Seller Pages** (Seller account required)

| Route | Status | Purpose | Required Role | Test Steps |
|-------|--------|---------|---------------|------------|
| `/sell` | Ready | Create new listing | Seller | 1. Login as seller:<br>- techstore@envisagezm.com / Seller@2025<br>2. Visit http://localhost:3000/sell<br>3. Fill product form<br>4. Upload images<br>5. Submit listing |
| `/listings` | Ready | Manage seller's products | Seller | 1. Login as seller<br>2. Visit http://localhost:3000/listings<br>3. View all your listings<br>4. Edit/delete products |

---

### 👨‍💼 **Admin Pages** (Admin account required)

| Route | Status | Purpose | Required Role | Test Steps |
|-------|--------|---------|---------------|------------|
| `/admin-panel` | Ready | Admin dashboard | Admin | 1. Login as admin:<br>- admin@envisagezm.com / Admin@2025<br>2. Visit http://localhost:3000/admin-panel<br>3. Manage users/products/orders |

---

## 🧪 Comprehensive Testing Checklist

### Phase 1: Homepage Testing
```
□ Visit http://localhost:3000
□ Verify logo and navigation bar appear
□ Check featured products display (should show products from API)
□ Test "Browse Marketplace" button
□ Click on a product card
□ Verify footer links work
```

### Phase 2: Marketplace Testing
```
□ Visit http://localhost:3000/marketplace
□ Verify all 15 products display
□ Test category filters (Electronics, Fashion, Home, Sports)
□ Try search functionality
□ Test sort options (price, newest, etc.)
□ Click "View Details" on a product
□ Verify product page shows:
  - Title, price, description
  - Seller information
  - Stock status
  - "Add to Cart" button
□ Test "Add to Cart" functionality
```

### Phase 3: Authentication Testing
```
□ Visit http://localhost:3000/login
□ Test login with valid credentials:
  - Admin: admin@envisagezm.com / Admin@2025
  - Seller: techstore@envisagezm.com / Seller@2025
  - Customer: john@example.com / Buyer@2025
□ Test login with invalid credentials
□ Verify error messages display correctly
□ Test "Forgot Password" link (if available)

□ Visit http://localhost:3000/register
□ Fill registration form
□ Select role (Customer/Seller)
□ Submit registration
□ Verify confirmation/redirect
```

### Phase 4: Cart & Checkout Testing
```
□ Add multiple products to cart
□ Visit http://localhost:3000/cart
□ Verify cart items display correctly
□ Test quantity adjustment (+/-)
□ Test remove item
□ Check subtotal calculation
□ Click "Proceed to Checkout"

□ Visit http://localhost:3000/checkout
□ Fill shipping information
□ Select payment method
□ Test Stripe payment (use test card: 4242 4242 4242 4242)
□ Verify order confirmation
```

### Phase 5: User Dashboard Testing
```
□ Login as customer
□ Visit http://localhost:3000/dashboard
□ Verify dashboard displays:
  - Recent orders
  - Account stats
  - Quick actions
□ Test navigation to profile, orders, favorites
```

### Phase 6: Seller Features Testing
```
□ Login as seller (techstore@envisagezm.com / Seller@2025)
□ Visit http://localhost:3000/sell
□ Create new product listing:
  - Upload product images
  - Fill title, description, price
  - Select category
  - Set stock quantity
□ Submit listing
□ Verify success message

□ Visit http://localhost:3000/listings
□ View all seller's products
□ Test edit product
□ Test delete product
□ Verify changes reflect on marketplace
```

### Phase 7: Admin Features Testing
```
□ Login as admin (admin@envisagezm.com / Admin@2025)
□ Visit http://localhost:3000/admin-panel
□ Test user management:
  - View all users
  - Edit user roles
  - Suspend/activate users
□ Test product management:
  - Approve/reject listings
  - Edit any product
  - Remove inappropriate content
□ Test order management:
  - View all orders
  - Update order status
  - Handle disputes
□ Test analytics/reports
```

---

## 🔍 What to Look For

### ✅ Working Correctly
- Pages load without errors
- Products display from API
- Images load properly
- Navigation works smoothly
- Forms validate input
- Authentication redirects work
- API responses are fast (<2s)
- Mobile responsive design
- Error messages are user-friendly

### ❌ Issues to Report
- 404 errors on existing routes
- Blank pages or infinite loading
- API connection errors
- Images not loading
- Broken navigation links
- Form submission failures
- Authentication not working
- Console errors (F12 → Console tab)
- Layout breaking on mobile

---

## 🛠️ Quick Debugging Commands

### View Browser Console
Press `F12` in browser → **Console** tab  
Look for red error messages

### Check API Response
```javascript
// Open browser console (F12), paste this:
fetch('https://envisagezm.com/api/products')
  .then(r => r.json())
  .then(data => console.log(data))
```

### Test Authentication
```javascript
// In browser console:
console.log('API URL:', process.env.NEXT_PUBLIC_API_URL)
```

### Clear Browser Cache
1. Press `Ctrl + Shift + Delete`
2. Select "Cached images and files"
3. Clear data
4. Reload page (`Ctrl + F5`)

---

## 📊 Expected API Data

### Products (15 total)
- **Laptops:** Dell XPS 15, MacBook Air M2, HP Pavilion, Refurb ThinkPad
- **Phones:** iPhone 14 Pro Max, Galaxy S23 Ultra, Pixel 7 Pro
- **Accessories:** Logitech Mouse, RGB Keyboard, Sony Headphones, USB-C Hub
- **Fashion:** T-Shirt, Jeans, Floral Dress, Leather Handbag

### Categories (9 total)
- Electronics → Computers & Laptops, Mobile Phones, Computer Accessories
- Fashion & Clothing → Men's Clothing, Women's Clothing
- Home & Garden
- Sports & Outdoors

### Users (6 total)
- **Admin:** admin@envisagezm.com
- **Sellers:** techstore@, electronics@, fashion@envisagezm.com
- **Customers:** john@example.com, sarah@example.com

---

## 🚀 Next Steps After Testing

### If Everything Works Locally ✅
1. **Deploy Frontend to Production**
   - Option A: Vercel (Recommended - Free, Fast)
   - Option B: cPanel Static Export
   - See `FRONTEND_DEPLOYMENT.md` for details

2. **Update Backend CORS**
   - Add production frontend URL to allowed origins
   - Update in `backend/config/cors.php`

3. **Configure Environment Variables**
   - Update `.env.production` with frontend URL
   - Add Stripe production keys
   - Configure email SMTP

4. **Final Testing**
   - Test complete flow on production
   - Verify payments work
   - Check email notifications

### If Issues Found ❌
1. **Document the Issue**
   - Which page?
   - What action caused it?
   - Error message?
   - Screenshot?

2. **Check Browser Console**
   - F12 → Console
   - Look for red errors
   - Copy error message

3. **Report for Fix**
   - Page URL
   - Steps to reproduce
   - Expected vs actual behavior
   - Console errors

---

## 📞 Test Accounts

### Admin Account
```
Email: admin@envisagezm.com
Password: Admin@2025
Role: Admin
```

### Seller Accounts
```
Email: techstore@envisagezm.com
Password: Seller@2025
Role: Seller

Email: electronics@envisagezm.com  
Password: Seller@2025
Role: Seller

Email: fashion@envisagezm.com
Password: Seller@2025
Role: Seller
```

### Customer Accounts
```
Email: john@example.com
Password: Buyer@2025
Role: Customer

Email: sarah@example.com
Password: Buyer@2025
Role: Customer
```

---

## 💡 Testing Tips

1. **Start Simple:** Test public pages first (homepage, marketplace)
2. **Use Incognito:** Test authentication in incognito/private window
3. **Test Multiple Roles:** Login as admin, seller, customer separately
4. **Check Mobile:** Resize browser or use device toolbar (F12 → Device toolbar)
5. **Clear Cache:** If pages don't update, clear browser cache
6. **Check Network:** F12 → Network tab to see API calls
7. **Console Errors:** F12 → Console to catch JavaScript errors

---

## 📈 Success Criteria

Your marketplace is **fully operational** when:

- ✅ All 14+ pages load without errors
- ✅ Products display correctly from API
- ✅ Authentication works (login/register/logout)
- ✅ Shopping cart functions properly
- ✅ Checkout process completes
- ✅ Sellers can create listings
- ✅ Admin can manage users/products
- ✅ Images load quickly
- ✅ Mobile responsive
- ✅ No console errors

---

**Current Status:** 🟢 Server Running | 🔗 API Connected | ⏳ Ready for Testing

**Start Here:** http://localhost:3000

---

*Last Updated: Just now*
*Next.js Version: 14.0.0*
*API: https://envisagezm.com/api*

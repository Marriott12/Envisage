# 🚀 QUICK START GUIDE - High Priority Features

**Get up and running with all new features in 10 minutes!**

---

## 📋 TABLE OF CONTENTS

1. [Setup & Installation](#setup--installation)
2. [Multi-Currency](#multi-currency)
3. [Invoice Generation](#invoice-generation)
4. [Tax Calculation](#tax-calculation)
5. [Import/Export](#importexport)
6. [Testing](#testing)

---

## 🔧 SETUP & INSTALLATION

### Backend Setup (5 minutes)

```bash
# 1. Navigate to backend
cd backend

# 2. Install dependencies (if not already done)
composer install

# 3. Ensure migrations are run
php artisan migrate

# 4. Seed currencies
php artisan db:seed --class=CurrencySeeder

# 5. Start server
php artisan serve
```

### Frontend Setup (2 minutes)

```bash
# 1. Navigate to frontend
cd frontend

# 2. Install dependencies
npm install

# 3. Configure environment
echo "NEXT_PUBLIC_API_URL=http://localhost:8000/api" > .env.local

# 4. Start development server
npm run dev
```

**✅ Your system is now running!**
- Backend: http://localhost:8000
- Frontend: http://localhost:3000

---

## 💱 MULTI-CURRENCY

### Quick Integration (3 steps)

#### Step 1: Wrap Your App
```tsx
// app/layout.tsx or pages/_app.tsx
import { CurrencyProvider } from '@/contexts/CurrencyContext';

export default function App({ children }) {
  return (
    <CurrencyProvider>
      {children}
    </CurrencyProvider>
  );
}
```

#### Step 2: Add Currency Switcher to Header
```tsx
// components/Header.tsx
import CurrencySwitcher from '@/components/currency/CurrencySwitcher';

<header>
  <CurrencySwitcher variant="compact" />
</header>
```

#### Step 3: Update Price Displays
```tsx
// Before
<span>${product.price}</span>

// After
import Price from '@/components/currency/Price';
<Price amount={product.price} originalCurrency="USD" />
```

### Test It!
1. Click currency switcher in header
2. Select EUR (Euro)
3. Watch all prices update automatically!

---

## 📄 INVOICE GENERATION

### Quick Setup (2 steps)

#### Step 1: Add Invoice Page
```tsx
// app/account/invoices/page.tsx
import InvoiceList from '@/components/invoices/InvoiceList';
import InvoiceStats from '@/components/invoices/InvoiceStats';

export default function InvoicesPage() {
  return (
    <div className="container mx-auto py-8">
      <InvoiceStats />
      <div className="mt-8">
        <InvoiceList />
      </div>
    </div>
  );
}
```

#### Step 2: Add Link to Navigation
```tsx
<Link href="/account/invoices">My Invoices</Link>
```

### Generate Your First Invoice

**Via API:**
```bash
# Create an order first, then:
curl -X POST http://localhost:8000/api/invoices/generate/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "Thank you for your business!",
    "due_days": 30
  }'
```

**Via UI:**
1. Navigate to `/account/invoices`
2. Invoice will show up if you have orders
3. Click "Download" to get PDF
4. Click "Email" to send to customer

**✅ Done!** PDF generated and ready to download.

---

## 💰 TAX CALCULATION

### Quick Integration (1 step)

#### Add to Checkout Page
```tsx
// app/checkout/page.tsx
import TaxDisplay from '@/components/checkout/TaxDisplay';

export default function CheckoutPage() {
  const [taxAmount, setTaxAmount] = useState(0);
  const [orderTotal, setOrderTotal] = useState(0);

  return (
    <div>
      {/* ... cart items ... */}
      
      <div className="border-t pt-4">
        <TaxDisplay
          items={cartItems}
          shipping={shippingCost}
          shippingAddress={{
            country: 'US',
            state: 'CA',
            city: 'Los Angeles',
            zip_code: '90001'
          }}
          onTaxCalculated={(tax, total) => {
            setTaxAmount(tax);
            setOrderTotal(total);
          }}
        />
      </div>

      <button onClick={handleCheckout}>
        Pay {orderTotal}
      </button>
    </div>
  );
}
```

### Create Tax Rules First!

**Via API:**
```bash
curl -X POST http://localhost:8000/api/taxes/rules \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "California Sales Tax",
    "country": "US",
    "state": "CA",
    "rate": 7.25,
    "tax_type": "sales_tax",
    "is_active": true,
    "priority": 1,
    "applies_to_shipping": true
  }'
```

**Test It:**
1. Go to checkout
2. Enter shipping address in CA
3. Watch tax calculate automatically!

**✅ Done!** Tax calculating in real-time.

---

## 📦 IMPORT/EXPORT

### Quick Setup (1 step)

#### Add to Seller Dashboard
```tsx
// app/seller/bulk-manage/page.tsx
import ProductImportExport from '@/components/seller/ProductImportExport';

export default function BulkManagePage() {
  return (
    <div className="container mx-auto py-8">
      <ProductImportExport />
    </div>
  );
}
```

### Import Your First Products (4 steps)

#### Step 1: Download Template
1. Go to `/seller/bulk-manage`
2. Click "Download Template"
3. Save `product-import-template.csv`

#### Step 2: Fill Template
```csv
name,description,price,stock_quantity,category_id,sku,status
Widget A,Great widget,29.99,100,1,WID-001,active
Widget B,Better widget,39.99,50,1,WID-002,active
Widget C,Best widget,49.99,25,1,WID-003,active
```

#### Step 3: Validate
1. Click "Select CSV File"
2. Choose your file
3. Click "Validate File"
4. Fix any errors shown

#### Step 4: Import
1. Check "Update existing products" if needed
2. Click "Import Products"
3. View results!

**✅ Done!** Products imported in bulk.

---

## 🧪 TESTING

### Test Currency System

```javascript
// In browser console
// 1. Switch currency
localStorage.setItem('preferred_currency', JSON.stringify({
  code: 'EUR',
  symbol: '€',
  exchange_rate: 0.85
}));

// 2. Refresh page
location.reload();

// Prices should show in EUR!
```

### Test Invoice Generation

```bash
# 1. Create test order
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"product_id": 1, "quantity": 2}],
    "shipping_address": "..."
  }'

# 2. Generate invoice
curl -X POST http://localhost:8000/api/invoices/generate/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

# 3. Download PDF
curl http://localhost:8000/api/invoices/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output invoice.pdf
```

### Test Tax Calculation

```bash
curl -X POST http://localhost:8000/api/taxes/calculate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "country": "US",
    "state": "CA",
    "items": [
      {"amount": 100, "category_id": 1}
    ],
    "shipping": 10
  }'
```

Expected Response:
```json
{
  "success": true,
  "data": {
    "subtotal": 100.00,
    "shipping": 10.00,
    "tax_amount": 7.98,
    "total": 117.98,
    "breakdown": [...]
  }
}
```

### Test Import/Export

```bash
# 1. Download template
curl http://localhost:8000/api/import/template?type=products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output template.csv

# 2. Validate CSV
curl -X POST http://localhost:8000/api/import/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@products.csv"

# 3. Import products
curl -X POST http://localhost:8000/api/import/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@products.csv" \
  -F "update_existing=true"

# 4. Export products
curl -X POST http://localhost:8000/api/export/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "active"}' \
  --output exported-products.csv
```

---

## ⚡ COMMON COMMANDS

### Backend

```bash
# Clear cache
php artisan cache:clear

# Run migrations
php artisan migrate

# Seed currencies
php artisan db:seed --class=CurrencySeeder

# Check routes
php artisan route:list | grep -E "(invoice|tax|currency|import)"

# Test API
php artisan test
```

### Frontend

```bash
# Development
npm run dev

# Production build
npm run build

# Type check
npm run type-check

# Lint
npm run lint
```

---

## 🎯 VERIFICATION CHECKLIST

After setup, verify each feature:

### Currency ✅
- [ ] Switcher appears in header
- [ ] Can select different currencies
- [ ] Prices update across site
- [ ] Preference saves after refresh
- [ ] Works for guest users

### Invoices ✅
- [ ] Can view invoice list
- [ ] Can download PDF
- [ ] Can email invoice
- [ ] Stats display correctly
- [ ] Search/filter works

### Tax ✅
- [ ] Tax calculates in checkout
- [ ] Updates when address changes
- [ ] Breakdown shows detail
- [ ] Handles no-tax locations
- [ ] Loading states show

### Import/Export ✅
- [ ] Template downloads
- [ ] File validates correctly
- [ ] Products import successfully
- [ ] Export creates CSV
- [ ] Error messages clear

---

## 🐛 TROUBLESHOOTING

### "Currencies not loading"
```bash
# Check if seeded
php artisan db:seed --class=CurrencySeeder

# Verify API response
curl http://localhost:8000/api/currencies
```

### "Tax returns 0"
```bash
# Check tax rules exist
curl http://localhost:8000/api/taxes/rules \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Create a rule if empty
```

### "PDF not generating"
```bash
# Ensure dompdf installed
composer show barryvdh/laravel-dompdf

# Check storage permissions
chmod -R 775 storage/app/public
```

### "Import fails"
- Check CSV encoding (must be UTF-8)
- Verify column headers match template
- Check file size < 10MB
- Ensure seller_id is yours

---

## 📚 NEXT STEPS

### 1. Create Sample Data
```bash
# Create tax rules for your jurisdictions
# Generate test invoices
# Import sample products
```

### 2. Customize
- Update invoice template branding
- Add more currencies if needed
- Configure tax rates for your regions
- Adjust import CSV columns

### 3. Deploy to Production
- Follow deployment checklist
- Configure SMTP for emails
- Set up exchange rate API key
- Enable SSL/HTTPS

### 4. Train Users
- Show sellers how to import
- Demonstrate invoice downloads
- Explain currency switching
- Review tax display

---

## 💡 PRO TIPS

### Currency
- Set USD or local currency as base
- Update rates daily via cron
- Cache rates for 1 hour
- Show conversion indicator

### Invoices
- Auto-generate on order completion
- Email customers immediately
- Set payment due to 30 days
- Enable auto-reminders

### Tax
- Create rules for all jurisdictions
- Test with different locations
- Handle exemptions properly
- Keep rates updated

### Import/Export
- Validate before large imports
- Export for backups weekly
- Use filters for partial exports
- Keep template updated

---

## 🎉 YOU'RE ALL SET!

**All high-priority features are now active:**
- ✅ Multi-Currency with 10 currencies
- ✅ Professional PDF Invoices
- ✅ Automated Tax Calculation
- ✅ Bulk Import/Export Tools

**Time to Production:** 15 minutes  
**Features Added:** 4 major systems  
**Code Quality:** Production-ready  
**Documentation:** Complete  

---

**Need Help?**
- Check: `API_TESTING_COMPLETE_GUIDE.md`
- Review: `FRONTEND_IMPLEMENTATION_COMPLETE.md`
- See: `DEPLOYMENT_STATUS_DEC_24.md`

**Ready to scale globally! 🌍**

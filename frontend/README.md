# Envisage Marketplace Frontend

A modern Next.js marketplace frontend application with secure escrow payments.

## Features

- **Modern Stack**: Next.js 14, TypeScript, Tailwind CSS
- **Secure Authentication**: JWT-based auth with persistent state
- **Marketplace**: Browse, search, and filter listings
- **Checkout Flow**: Complete checkout with shipping and payment info
- **Payment Integration**: Stripe and Flutterwave support
- **Responsive Design**: Mobile-first responsive design
- **State Management**: Zustand for lightweight state management

## Setup Instructions

### Prerequisites

- Node.js 18 or later
- PHP backend running on `http://localhost/envisage/`

### Installation

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Start the development server:**
   ```bash
   npm run dev
   ```

3. **Open your browser:**
   Navigate to `http://localhost:3000`

## Project Structure

```
├── app/                    # Next.js App Router pages
│   ├── layout.tsx         # Root layout with Toaster
│   ├── page.tsx           # Home page
│   ├── marketplace/       
│   │   ├── page.tsx       # Listings page with filters
│   │   └── [id]/          
│   │       └── page.tsx   # Listing detail page
├── components/            # Reusable React components
│   ├── Header.tsx         # Navigation header
│   ├── ListingCard.tsx    # Product card component
│   └── CheckoutModal.tsx  # Checkout form modal
├── lib/                   # Utility libraries
│   ├── api.ts             # API client with TypeScript types
│   ├── store.ts           # Zustand state management
│   └── utils.ts           # Helper functions
├── public/                # Static assets
└── styles/                # Global CSS styles
    └── globals.css        # Tailwind CSS and custom styles
```

## Key Components

### API Integration (`lib/api.ts`)
- Centralized API client with Axios
- TypeScript interfaces for all endpoints
- JWT token handling with interceptors
- Marketplace and authentication methods

### State Management (`lib/store.ts`)
- **Auth Store**: User authentication state
- **Cart Store**: Shopping cart with persistence
- **UI Store**: Global UI state (modals, loading, etc.)

### Listing Card (`components/ListingCard.tsx`)
- Product display with image, price, seller info
- Favorite toggle functionality
- Condition badges and animations
- Responsive grid layout

### Checkout Modal (`components/CheckoutModal.tsx`)
- Multi-step checkout process
- Address validation and collection
- Payment method selection
- Order summary and total calculation
- Integration with backend payment APIs

## API Endpoints Used

### Marketplace
- `GET /api/marketplace/listings` - Browse listings with filters
- `GET /api/marketplace/listing/{id}` - Get listing details
- `POST /api/marketplace/listing/{id}/buy` - Purchase listing

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `GET /api/auth/user` - Get current user
- `POST /api/auth/logout` - User logout

## Environment Variables

Create a `.env.local` file with:

```env
NEXT_PUBLIC_API_URL=http://localhost/envisage/api
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run start` - Start production server
- `npm run lint` - Run ESLint

## Payment Integration

The checkout modal integrates with the PHP backend's payment service:

1. **Stripe**: Credit/debit card payments
2. **Flutterwave**: Bank transfers and mobile money

Orders are processed with escrow protection, holding funds until order confirmation.

## Responsive Design

- Mobile-first approach with Tailwind CSS
- Breakpoints: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px)
- Optimized for all screen sizes and devices

## Security Features

- **JWT Authentication**: Secure token-based auth
- **XSS Protection**: Sanitized inputs and outputs  
- **HTTPS Ready**: Production-ready security headers
- **Escrow Protection**: Secure payment processing

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

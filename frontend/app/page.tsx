'use client';

import Link from 'next/link';
import ProductList from './components/ProductList';
import Header from '@/components/Header';
import { 
  ShieldCheckIcon, 
  CreditCardIcon, 
  TruckIcon,
  StarIcon,
  TagIcon,
  UserGroupIcon
} from '@heroicons/react/24/outline';

const features = [
  {
    icon: ShieldCheckIcon,
    title: 'Secure Escrow',
    description: 'Your payments are protected with escrow until you confirm receipt of items.'
  },
  {
    icon: CreditCardIcon,
    title: 'Multiple Payment Options',
    description: 'Pay with credit cards, bank transfers, or mobile money through Stripe and Flutterwave.'
  },
  {
    icon: TruckIcon,
    title: 'Tracked Shipping',
    description: 'Track your orders from purchase to delivery with integrated shipping updates.'
  },
  {
    icon: StarIcon,
    title: 'Verified Sellers',
    description: 'All sellers are verified and rated by the community for your peace of mind.'
  },
  {
    icon: TagIcon,
    title: 'Best Prices',
    description: 'Find the best deals on new and used items across all categories.'
  },
  {
    icon: UserGroupIcon,
    title: 'Community Driven',
    description: 'Join a trusted community of buyers and sellers from around the world.'
  }
];

const categories = [
  { name: 'Electronics', count: '2,341 items', image: '/images/electronics.jpg' },
  { name: 'Fashion', count: '1,876 items', image: '/images/fashion.jpg' },
  { name: 'Home & Garden', count: '1,523 items', image: '/images/home.jpg' },
  { name: 'Sports', count: '987 items', image: '/images/sports.jpg' },
  { name: 'Books', count: '765 items', image: '/images/books.jpg' },
  { name: 'Automotive', count: '432 items', image: '/images/automotive.jpg' },
];

export default function HomePage() {
  return (
    <>
      <Header />
      <main>
        {/* Hero Section */}
        <section className="bg-blue-50 py-16 text-center">
          <h1 className="text-4xl font-extrabold mb-4">Welcome to Envisage Marketplace</h1>
          <p className="text-lg mb-8">Buy, sell, and discover amazing products with secure payments and tracked shipping.</p>
          <Link href="/products" className="bg-blue-600 text-white px-6 py-3 rounded shadow hover:bg-blue-700">Shop Now</Link>
        </section>

        {/* Features Section */}
        <section className="py-12 bg-white">
          <div className="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
            {features.map((feature, idx) => (
              <div key={idx} className="flex flex-col items-center text-center p-6 border rounded-lg shadow-sm">
                <feature.icon className="h-10 w-10 text-blue-600 mb-2" />
                <h3 className="font-bold text-lg mb-1">{feature.title}</h3>
                <p className="text-gray-600">{feature.description}</p>
              </div>
            ))}
          </div>
        </section>

        {/* Product List from backend */}
        <div className="max-w-6xl mx-auto">
          <ProductList />
        </div>

        {/* Categories Section */}
        <section className="py-12">
          <h2 className="text-2xl font-bold mb-6 text-center">Shop by Category</h2>
          <div className="grid grid-cols-2 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            {categories.map((cat, idx) => (
              <div key={idx} className="bg-white rounded-lg shadow p-4 flex flex-col items-center">
                <img src={cat.image} alt={cat.name} className="w-24 h-24 object-cover rounded mb-2" />
                <div className="font-semibold">{cat.name}</div>
                <div className="text-gray-500 text-sm">{cat.count}</div>
              </div>
            ))}
          </div>
        </section>

        {/* Hero Section with Gradient */}
        <section className="bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 text-white">
          <div className="container mx-auto px-4 py-20">
            <div className="text-center max-w-4xl mx-auto">
              <h1 className="text-5xl md:text-6xl font-bold mb-6">
                Buy & Sell with
                <span className="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">
                  Complete Security
                </span>
              </h1>
              <p className="text-xl md:text-2xl mb-8 text-primary-100">
                Discover amazing deals on trusted marketplace with escrow protection,
                multiple payment options, and verified sellers.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Link
                  href="/marketplace"
                  className="bg-white text-primary-700 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-primary-50 transition-colors inline-flex items-center justify-center"
                >
                  Start Shopping
                </Link>
                <Link
                  href="/sell"
                  className="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-primary-700 transition-colors inline-flex items-center justify-center"
                >
                  Start Selling
                </Link>
              </div>
            </div>
          </div>
        </section>

        {/* Features Section */}
        <section className="py-20 bg-gray-50">
          <div className="container mx-auto px-4">
            <div className="text-center mb-16">
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Why Choose Envisage Marketplace?
              </h2>
              <p className="text-xl text-gray-600 max-w-3xl mx-auto">
                We've built the most secure and user-friendly marketplace platform
                with features designed to protect both buyers and sellers.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {features.map((feature, index) => (
                <div
                  key={index}
                  className="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-shadow"
                >
                  <div className="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                    <feature.icon className="h-6 w-6 text-primary-600" />
                  </div>
                  <h3 className="text-xl font-semibold text-gray-900 mb-2">
                    {feature.title}
                  </h3>
                  <p className="text-gray-600">
                    {feature.description}
                  </p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Categories Section */}
        <section className="py-20">
          <div className="container mx-auto px-4">
            <div className="text-center mb-16">
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Popular Categories
              </h2>
              <p className="text-xl text-gray-600">
                Explore thousands of items across all categories
              </p>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
              {categories.map((category, index) => (
                <Link
                  key={index}
                  href={`/marketplace?category=${encodeURIComponent(category.name.toLowerCase())}`}
                  className="group"
                >
                  <div className="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all group-hover:-translate-y-1">
                    <div className="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
                      <span className="text-2xl font-bold text-white">
                        {category.name.charAt(0)}
                      </span>
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900 text-center mb-1">
                      {category.name}
                    </h3>
                    <p className="text-sm text-gray-600 text-center">
                      {category.count}
                    </p>
                  </div>
                </Link>
              ))}
            </div>

            <div className="text-center mt-12">
              <Link
                href="/marketplace"
                className="btn-primary inline-flex items-center px-8 py-3"
              >
                View All Categories
              </Link>
            </div>
          </div>
        </section>

        {/* Stats Section */}
        <section className="py-20 bg-primary-600 text-white">
          <div className="container mx-auto px-4">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
              <div>
                <div className="text-4xl md:text-5xl font-bold mb-2">10K+</div>
                <div className="text-primary-100">Active Users</div>
              </div>
              <div>
                <div className="text-4xl md:text-5xl font-bold mb-2">50K+</div>
                <div className="text-primary-100">Items Sold</div>
              </div>
              <div>
                <div className="text-4xl md:text-5xl font-bold mb-2">99.8%</div>
                <div className="text-primary-100">Success Rate</div>
              </div>
              <div>
                <div className="text-4xl md:text-5xl font-bold mb-2">24/7</div>
                <div className="text-primary-100">Support</div>
              </div>
            </div>
          </div>
        </section>

        {/* CTA Section */}
        <section className="py-20">
          <div className="container mx-auto px-4">
            <div className="bg-gradient-to-r from-primary-600 to-primary-700 rounded-3xl p-12 text-center text-white">
              <h2 className="text-3xl md:text-4xl font-bold mb-4">
                Ready to Get Started?
              </h2>
              <p className="text-xl mb-8 text-primary-100">
                Join thousands of satisfied customers who trust Envisage Marketplace
                for their buying and selling needs.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Link
                  href="/register"
                  className="bg-white text-primary-700 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-primary-50 transition-colors inline-flex items-center justify-center"
                >
                  Create Account
                </Link>
                <Link
                  href="/marketplace"
                  className="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-primary-700 transition-colors inline-flex items-center justify-center"
                >
                  Browse Items
                </Link>
              </div>
            </div>
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center space-x-2 mb-4">
                <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                  <span className="text-white font-bold text-sm">E</span>
                </div>
                <span className="text-xl font-bold">Envisage</span>
              </div>
              <p className="text-gray-400 mb-4">
                The most secure marketplace for buying and selling online with escrow protection.
              </p>
            </div>

            <div>
              <h4 className="font-semibold mb-4">Marketplace</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/marketplace" className="hover:text-white transition-colors">Browse All</Link></li>
                <li><Link href="/marketplace?category=electronics" className="hover:text-white transition-colors">Electronics</Link></li>
                <li><Link href="/marketplace?category=fashion" className="hover:text-white transition-colors">Fashion</Link></li>
                <li><Link href="/sell" className="hover:text-white transition-colors">Start Selling</Link></li>
              </ul>
            </div>

            <div>
              <h4 className="font-semibold mb-4">Support</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/help" className="hover:text-white transition-colors">Help Center</Link></li>
                <li><Link href="/contact" className="hover:text-white transition-colors">Contact Us</Link></li>
                <li><Link href="/security" className="hover:text-white transition-colors">Security</Link></li>
                <li><Link href="/fees" className="hover:text-white transition-colors">Fees</Link></li>
              </ul>
            </div>

            <div>
              <h4 className="font-semibold mb-4">Legal</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/terms" className="hover:text-white transition-colors">Terms of Service</Link></li>
                <li><Link href="/privacy" className="hover:text-white transition-colors">Privacy Policy</Link></li>
                <li><Link href="/cookies" className="hover:text-white transition-colors">Cookie Policy</Link></li>
              </ul>
            </div>
          </div>

          <hr className="border-gray-800 my-8" />

          <div className="text-center text-gray-400">
            <p>&copy; 2024 Envisage Marketplace. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </>
  );
}

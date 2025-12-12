import type { Metadata } from 'next';
// import { Inter } from 'next/font/google'; // Temporarily disabled due to build issues
import { Toaster } from 'react-hot-toast';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { LocaleProvider } from '@/components/i18n/LocaleProvider';
import './globals.css';
import '@/styles/accessibility.css';
import '@/styles/mobile.css';
import '@/styles/rtl.css';

// const inter = Inter({ subsets: ['latin'] }); // Disabled

export const metadata: Metadata = {
  title: 'Envisage Marketplace',
  description: 'Buy and sell items securely with escrow protection',
  icons: {
    icon: '/favicon.ico',
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className="font-sans">{/* Changed from {inter.className} */}
        <LocaleProvider>
          <ErrorBoundary>
            {children}
            <Toaster
              position="top-right"
              toastOptions={{
                duration: 4000,
                style: {
                  background: '#363636',
                  color: '#fff',
                },
                success: {
                  duration: 3000,
                  iconTheme: {
                    primary: '#10b981',
                    secondary: '#fff',
                  },
                },
                error: {
                  duration: 5000,
                  iconTheme: {
                    primary: '#ef4444',
                    secondary: '#fff',
                  },
                },
              }}
            />
          </ErrorBoundary>
        </LocaleProvider>
      </body>
    </html>
  );
}

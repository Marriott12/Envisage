import NavBar from '../components/NavBar';
import '../app/globals.css';

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="bg-gray-50 min-h-screen">
        <NavBar />
        <div className="pt-8">
          {children}
        </div>
      </body>
    </html>
  );
}

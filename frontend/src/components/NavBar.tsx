import Link from 'next/link';

const navLinks = [
  { href: '/', label: 'Home' },
  { href: '/services', label: 'Services' },
  { href: '/training-events', label: 'Training & Events' },
  { href: '/marketplace', label: 'Marketplace' },
  { href: '/client-portal', label: 'Client Portal' },
  { href: '/affiliate-dashboard', label: 'Affiliate Dashboard' },
  { href: '/blog', label: 'Blog' },
  { href: '/contact', label: 'Contact' },
];

export default function NavBar() {
  return (
    <nav className="w-full bg-white shadow py-4 px-6 flex justify-between items-center">
      <Link href="/">
        <span className="font-bold text-blue-700 text-xl">Envisage</span>
      </Link>
      <div className="flex gap-6">
        {navLinks.map(link => (
          <Link key={link.href} href={link.href} className="text-blue-700 hover:underline">
            {link.label}
          </Link>
        ))}
      </div>
      <div>
        <Link href="/login" className="px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800 transition">Login</Link>
      </div>
    </nav>
  );
}

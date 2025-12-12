'use client';

/**
 * Skip to Content link for keyboard navigation
 * WCAG 2.1 Success Criterion 2.4.1 (Level A)
 */
export function SkipToContent() {
  return (
    <a
      href="#main-content"
      className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-lg focus:shadow-lg"
      aria-label="Skip to main content"
    >
      Skip to main content
    </a>
  );
}

/**
 * Skip navigation component with multiple targets
 */
interface SkipNavigationProps {
  links?: Array<{
    href: string;
    label: string;
  }>;
}

export function SkipNavigation({ links }: SkipNavigationProps) {
  const defaultLinks = [
    { href: '#main-content', label: 'Skip to main content' },
    { href: '#navigation', label: 'Skip to navigation' },
    { href: '#footer', label: 'Skip to footer' },
  ];

  const navigationLinks = links || defaultLinks;

  return (
    <nav
      aria-label="Skip navigation"
      className="sr-only focus-within:not-sr-only focus-within:absolute focus-within:top-4 focus-within:left-4 focus-within:z-50"
    >
      <ul className="flex flex-col gap-2 bg-white p-2 rounded-lg shadow-lg">
        {navigationLinks.map((link) => (
          <li key={link.href}>
            <a
              href={link.href}
              className="block px-4 py-2 text-blue-600 hover:bg-blue-50 rounded focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              {link.label}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  );
}

export default SkipToContent;

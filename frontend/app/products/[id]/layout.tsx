import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Product Details',
  description: 'View product details, reviews, and place orders',
};

export default function ProductLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return children;
}

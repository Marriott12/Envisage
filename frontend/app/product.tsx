import React, { useEffect, useState } from 'react';
import { getProducts } from './api';

export default function ProductDetailsPage({ productId }: { productId: number }) {
  const [product, setProduct] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getProducts().then(data => {
      const found = data.find((p: any) => p.id === productId);
      setProduct(found);
      setLoading(false);
    });
  }, [productId]);

  if (loading) return <div>Loading product...</div>;
  if (!product) return <div>Product not found.</div>;

  return (
    <section className="py-8 max-w-2xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">{product.name}</h2>
      <img src={product.image_url || '/images/electronics.jpg'} alt={product.name} className="w-full h-64 object-cover mb-4 rounded" />
      <p className="mb-4">{product.description}</p>
      <div className="font-bold text-blue-600 text-xl mb-4">${product.price}</div>
      {/* TODO: Add reviews, related products */}
    </section>
  );
}

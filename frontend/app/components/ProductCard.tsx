import React, { useState } from 'react';
import { addToCart } from '../api';

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  image_url?: string;
}

export default function ProductCard({ product }: { product: Product }) {
  const [added, setAdded] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleAddToCart = async () => {
    setError(null);
    setAdded(true);
    try {
      // For demo, use userId 1
      await addToCart(1, product.id, 1);
    } catch (e: any) {
      setError('Failed to add to cart');
    }
    setTimeout(() => setAdded(false), 1500);
  };

  return (
    <div className="border rounded-lg p-4 shadow flex flex-col">
      <img src={product.image_url || '/images/electronics.jpg'} alt={product.name} className="w-full h-40 object-cover mb-2 rounded" />
      <h3 className="font-semibold text-lg">{product.name}</h3>
      <p className="text-gray-600 flex-1">{product.description}</p>
      <div className="mt-2 font-bold text-blue-600">${product.price}</div>
      <button
        className={`mt-4 px-4 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700 transition ${added ? 'bg-green-500' : ''}`}
        onClick={handleAddToCart}
        disabled={added}
      >
        {added ? 'Added!' : 'Add to Cart'}
      </button>
      {error && <div className="text-red-500 mt-2">{error}</div>}
    </div>
  );
}

import React, { useState } from 'react';
import { getProducts } from './api';

export default function SearchPage() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<any[]>([]);

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    const products = await getProducts();
    setResults(products.filter((p: any) => p.name.toLowerCase().includes(query.toLowerCase())));
  };

  return (
    <section className="py-8 max-w-4xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Search Products</h2>
      <form className="mb-6" onSubmit={handleSearch}>
        <input type="text" value={query} onChange={e => setQuery(e.target.value)} placeholder="Search..." className="w-full border rounded px-4 py-2" />
        <button className="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700 mt-2">Search</button>
      </form>
      <ul>
        {results.map(product => (
          <li key={product.id} className="border-b py-2">{product.name}</li>
        ))}
      </ul>
    </section>
  );
}

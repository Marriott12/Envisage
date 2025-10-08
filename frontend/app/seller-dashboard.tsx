import React, { useEffect, useState } from 'react';
import { getSellerListings, getSellerAnalytics } from './api';

export default function SellerDashboard() {
  const [listings, setListings] = useState<any[]>([]);
  const [analytics, setAnalytics] = useState<any>(null);
  useEffect(() => {
    getSellerListings().then(setListings);
    getSellerAnalytics().then(setAnalytics);
  }, []);
  return (
    <section className="py-8 max-w-5xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Seller Dashboard</h2>
      <div className="mb-6">{analytics ? JSON.stringify(analytics) : 'Loading analytics...'}</div>
      <div>
        <h3 className="font-bold mb-2">Listings</h3>
        <ul>
          {listings.map(listing => (
            <li key={listing.id} className="border-b py-2">{listing.name}</li>
          ))}
        </ul>
      </div>
    </section>
  );
}

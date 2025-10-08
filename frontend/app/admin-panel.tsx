import React, { useEffect, useState } from 'react';
import { getAdminData } from './api';

export default function AdminPanel() {
  const [data, setData] = useState<any>(null);
  useEffect(() => {
    getAdminData().then(setData);
  }, []);
  return (
    <section className="py-8 max-w-5xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Admin Panel</h2>
      <div>{data ? JSON.stringify(data) : 'Loading admin data...'}</div>
    </section>
  );
}

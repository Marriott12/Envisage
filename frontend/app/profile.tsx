import React, { useState } from 'react';
import { updateProfile } from './api';

export default function ProfilePage() {
  const [name, setName] = useState('Test User');
  const [email, setEmail] = useState('testuser@example.com');
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess(false);
    try {
      await updateProfile(name, email);
      setSuccess(true);
    } catch {
      setError('Failed to update profile');
    }
  };

  return (
    <section className="py-8 max-w-md mx-auto">
      <h2 className="text-2xl font-bold mb-4">Profile Management</h2>
      <form className="space-y-4" onSubmit={handleSave}>
        <input type="text" value={name} onChange={e => setName(e.target.value)} className="w-full border rounded px-4 py-2" />
        <input type="email" value={email} onChange={e => setEmail(e.target.value)} className="w-full border rounded px-4 py-2" />
        <button className="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700">Save Changes</button>
        {success && <div className="text-green-600 mt-2">Profile updated!</div>}
        {error && <div className="text-red-500 mt-2">{error}</div>}
      </form>
    </section>
  );
}

import React, { useState } from 'react';
import { login } from './api';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    try {
      const data = await login(email, password);
      localStorage.setItem('token', data.access_token);
      // TODO: Redirect to homepage or dashboard
    } catch (err: any) {
      setError('Login failed');
    }
  };

  return (
    <section className="py-8 max-w-md mx-auto">
      <h2 className="text-2xl font-bold mb-4">Login</h2>
      <form className="space-y-4" onSubmit={handleLogin}>
        <input type="email" placeholder="Email" value={email} onChange={e => setEmail(e.target.value)} className="w-full border rounded px-4 py-2" />
        <input type="password" placeholder="Password" value={password} onChange={e => setPassword(e.target.value)} className="w-full border rounded px-4 py-2" />
        <button className="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700">Login</button>
        {error && <div className="text-red-500 mt-2">{error}</div>}
      </form>
    </section>
  );
}

import React, { useEffect } from 'react';
import { logoutUser } from './api';

export default function LogoutPage() {
  useEffect(() => {
    logoutUser();
    localStorage.removeItem('token');
    // TODO: Redirect to login/homepage
  }, []);

  return (
    <section className="py-8 max-w-md mx-auto text-center">
      <h2 className="text-2xl font-bold mb-4">You have been logged out.</h2>
    </section>
  );
}

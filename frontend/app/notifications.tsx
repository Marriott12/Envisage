import React, { useEffect, useState } from 'react';
import { getNotifications } from './api';

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState<any[]>([]);
  useEffect(() => {
    getNotifications().then(setNotifications);
  }, []);
  return (
    <section className="py-8 max-w-3xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Notifications</h2>
      <ul>
        {notifications.length > 0 ? notifications.map((n, idx) => (
          <li key={idx} className="border-b py-2">{n.message || JSON.stringify(n)}</li>
        )) : <li>No notifications yet.</li>}
      </ul>
    </section>
  );
}

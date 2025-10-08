import React from 'react';

export default function CheckoutPage() {
  return (
    <section className="py-8 max-w-2xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Checkout</h2>
      <form className="space-y-4">
        <input type="text" placeholder="Shipping Address" className="w-full border rounded px-4 py-2" />
        <input type="text" placeholder="Card Number" className="w-full border rounded px-4 py-2" />
        <input type="text" placeholder="Expiry" className="w-full border rounded px-4 py-2" />
        <input type="text" placeholder="CVV" className="w-full border rounded px-4 py-2" />
        <button className="bg-green-600 text-white px-6 py-2 rounded shadow hover:bg-green-700">Pay & Place Order</button>
      </form>
    </section>
  );
}

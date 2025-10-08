import React from 'react';
import { updateCartItem, removeCartItem } from './api';

export default function CartItem({ item, cartId, refresh }: { item: any, cartId: number, refresh: () => void }) {
  const handleUpdate = async (qty: number) => {
    await updateCartItem(cartId, item.id, qty);
    refresh();
  };
  const handleRemove = async () => {
    await removeCartItem(cartId, item.id);
    refresh();
  };
  return (
    <div className="flex justify-between items-center py-2 border-b">
      <span>{item.product?.name || 'Product'}</span>
      <input type="number" value={item.quantity} min={1} onChange={e => handleUpdate(Number(e.target.value))} className="w-16 border rounded px-2" />
      <span>${item.product?.price || 0}</span>
      <button className="text-red-600 ml-2" onClick={handleRemove}>Remove</button>
    </div>
  );
}

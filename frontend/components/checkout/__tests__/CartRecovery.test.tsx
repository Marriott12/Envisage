import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { CartRecovery } from '@/components/checkout/CartRecovery';

// Mock the stores
vi.mock('@/lib/store', () => ({
  useCartStore: () => ({
    items: [
      {
        id: '1',
        title: 'Test Product',
        price: 99.99,
        quantity: 1,
        image: '/test-image.jpg',
      },
    ],
    getTotalPrice: () => 99.99,
  }),
}));

describe('CartRecovery', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('shows modal after delay when cart has items', async () => {
    vi.useFakeTimers();
    
    render(<CartRecovery />);
    
    // Fast-forward time
    vi.advanceTimersByTime(5 * 60 * 1000);
    
    await waitFor(() => {
      const modal = screen.getByRole('dialog');
      expect(modal).toBeInTheDocument();
    });

    vi.useRealTimers();
  });

  it('sends email reminder when button clicked', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      })
    ) as any;

    render(<CartRecovery />);
    
    // Manually show modal for testing
    const button = screen.getByRole('button', { name: /send reminder/i });
    
    const emailInput = screen.getByPlaceholderText(/email/i);
    fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
    
    fireEvent.click(button);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        '/api/cart/email-reminder',
        expect.objectContaining({
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: expect.stringContaining('test@example.com'),
        })
      );
    });
  });

  it('saves cart for later', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ cartId: '123' }),
      })
    ) as any;

    render(<CartRecovery />);
    
    const saveButton = screen.getByRole('button', { name: /save for later/i });
    fireEvent.click(saveButton);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        '/api/cart/save',
        expect.objectContaining({
          method: 'POST',
        })
      );
    });
  });

  it('closes modal when close button clicked', () => {
    render(<CartRecovery />);
    
    const closeButton = screen.getByRole('button', { name: /close/i });
    fireEvent.click(closeButton);

    const modal = screen.queryByRole('dialog');
    expect(modal).not.toBeInTheDocument();
  });
});

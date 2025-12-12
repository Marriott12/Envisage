import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { useAnalytics } from '@/components/analytics/AnalyticsHooks';
import { analytics } from '@/lib/analytics/unified-analytics';

// Mock analytics - using the full analytics object structure
const mockAnalytics = {
  productViewed: vi.fn(),
  productAddedToCart: vi.fn(),
  productRemovedFromCart: vi.fn(),
  checkoutStarted: vi.fn(),
  orderCompleted: vi.fn(),
  customEvent: vi.fn(),
  searchPerformed: vi.fn(),
  categoryViewed: vi.fn(),
  buttonClicked: vi.fn(),
  formSubmitted: vi.fn(),
  errorOccurred: vi.fn(),
  performanceMetric: vi.fn(),
};

vi.mock('@/lib/analytics/unified-analytics', () => ({
  analytics: mockAnalytics,
}));

describe('Analytics Tracking', () => {
  it('tracks custom events', () => {
    const TestComponent = () => {
      const analytics = useAnalytics();
      
      return (
        <button onClick={() => analytics.customEvent('test_event', { foo: 'bar' })}>
          Track Event
        </button>
      );
    };

    render(<TestComponent />);
    
    const button = screen.getByRole('button');
    fireEvent.click(button);

    expect(analytics.customEvent).toHaveBeenCalledWith('test_event', { foo: 'bar' });
  });

  it('tracks product views', () => {
    const product = {
      id: '123',
      name: 'Test Product',
      price: 99.99,
      category: 'Electronics',
    };

    analytics.productViewed(product);

    expect(analytics.productViewed).toHaveBeenCalledWith(product);
  });

  it('tracks add to cart events', () => {
    const product = {
      id: '123',
      name: 'Test Product',
      price: 99.99,
      quantity: 1,
    };

    analytics.productAddedToCart(product);

    expect(analytics.productAddedToCart).toHaveBeenCalledWith(product);
  });

  it('tracks button clicks', () => {
    analytics.buttonClicked('test_button', 'header');

    expect(analytics.buttonClicked).toHaveBeenCalledWith('test_button', 'header');
  });
});

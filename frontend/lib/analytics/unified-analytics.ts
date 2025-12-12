import { trackEvent as posthogTrack, ecommerce as posthogEcommerce } from './posthog-config';
import { addBreadcrumb, captureException } from './sentry-config';

/**
 * Unified analytics wrapper that tracks to multiple services
 * Provides a single interface for tracking across Sentry and PostHog
 */

export interface AnalyticsEvent {
  name: string;
  properties?: Record<string, any>;
  category?: string;
}

export interface User {
  id: string;
  email?: string;
  name?: string;
  plan?: string;
  [key: string]: any;
}

// Core event tracking
export function track(eventName: string, properties?: Record<string, any>) {
  try {
    // PostHog analytics
    posthogTrack(eventName, properties);

    // Sentry breadcrumb for debugging
    addBreadcrumb({
      message: eventName,
      category: 'analytics',
      data: properties,
    });
  } catch (error) {
    console.error('Analytics tracking error:', error);
  }
}

// User tracking
export function identifyUser(user: User) {
  try {
    // PostHog
    const { identifyUser: posthogIdentify } = require('./posthog-config');
    posthogIdentify(user.id, {
      email: user.email,
      name: user.name,
      plan: user.plan,
      ...user,
    });

    // Sentry
    const { setUser: sentrySetUser } = require('./sentry-config');
    sentrySetUser({
      id: user.id,
      email: user.email,
      username: user.name,
    });
  } catch (error) {
    console.error('User identification error:', error);
  }
}

export function resetUser() {
  try {
    const { resetUser: posthogReset } = require('./posthog-config');
    const { clearUser: sentryClearUser } = require('./sentry-config');
    
    posthogReset();
    sentryClearUser();
  } catch (error) {
    console.error('User reset error:', error);
  }
}

// Page tracking
export function trackPageView(url?: string, properties?: Record<string, any>) {
  const { trackPageView: posthogPageView } = require('./posthog-config');
  
  try {
    posthogPageView(url);
    
    track('page_view', {
      url: url || (typeof window !== 'undefined' ? window.location.href : ''),
      ...properties,
    });
  } catch (error) {
    console.error('Page view tracking error:', error);
  }
}

// E-commerce tracking
export const analytics = {
  // Product events
  productViewed(product: {
    id: string;
    name: string;
    price: number;
    category?: string;
    brand?: string;
  }) {
    posthogEcommerce.viewProduct(product);
    track('product_viewed', product);
  },

  productAddedToCart(product: {
    id: string;
    name: string;
    price: number;
    quantity: number;
    category?: string;
  }) {
    posthogEcommerce.addToCart(product);
    track('add_to_cart', product);
  },

  productRemovedFromCart(product: {
    id: string;
    name: string;
    price: number;
    quantity: number;
  }) {
    posthogEcommerce.removeFromCart(product);
    track('remove_from_cart', product);
  },

  // Wishlist
  productAddedToWishlist(product: { id: string; name: string; price: number }) {
    track('add_to_wishlist', product);
  },

  productRemovedFromWishlist(product: { id: string; name: string }) {
    track('remove_from_wishlist', { product_id: product.id, product_name: product.name });
  },

  // Checkout events
  checkoutStarted(cart: {
    items: Array<{ id: string; name: string; price: number; quantity: number }>;
    total: number;
  }) {
    posthogEcommerce.initiateCheckout(cart);
    track('checkout_started', {
      cart_total: cart.total,
      items_count: cart.items.length,
    });
  },

  checkoutStepCompleted(step: number, stepName: string, properties?: Record<string, any>) {
    track('checkout_step_completed', {
      step,
      step_name: stepName,
      ...properties,
    });
  },

  orderCompleted(order: {
    orderId: string;
    total: number;
    items: Array<{ id: string; name: string; price: number; quantity: number }>;
    paymentMethod?: string;
    shippingMethod?: string;
  }) {
    posthogEcommerce.completeCheckout(order);
    track('order_completed', {
      order_id: order.orderId,
      revenue: order.total,
      items_count: order.items.length,
      payment_method: order.paymentMethod,
      shipping_method: order.shippingMethod,
    });
  },

  // Search events
  searchPerformed(query: string, resultsCount?: number, filters?: Record<string, any>) {
    posthogEcommerce.search(query, resultsCount);
    track('search', {
      search_query: query,
      results_count: resultsCount,
      filters,
    });
  },

  // Category browsing
  categoryViewed(category: string, productsCount?: number) {
    posthogEcommerce.viewCategory(category, productsCount);
    track('category_viewed', {
      category,
      products_count: productsCount,
    });
  },

  // User engagement
  buttonClicked(buttonName: string, location: string, properties?: Record<string, any>) {
    track('button_clicked', {
      button_name: buttonName,
      location,
      ...properties,
    });
  },

  formSubmitted(formName: string, success: boolean, properties?: Record<string, any>) {
    track('form_submitted', {
      form_name: formName,
      success,
      ...properties,
    });
  },

  filterApplied(filterType: string, filterValue: any) {
    track('filter_applied', {
      filter_type: filterType,
      filter_value: filterValue,
    });
  },

  sortChanged(sortType: string) {
    track('sort_changed', { sort_type: sortType });
  },

  // Social interactions
  productShared(product: { id: string; name: string }, platform: string) {
    track('product_shared', {
      product_id: product.id,
      product_name: product.name,
      platform,
    });
  },

  reviewSubmitted(product: { id: string; name: string }, rating: number) {
    track('review_submitted', {
      product_id: product.id,
      product_name: product.name,
      rating,
    });
  },

  // Error tracking
  errorOccurred(error: Error, context?: Record<string, any>) {
    captureException(error, context);
    track('error_occurred', {
      error_message: error.message,
      error_name: error.name,
      ...context,
    });
  },

  // Performance tracking
  performanceMetric(metricName: string, value: number, unit: string = 'ms') {
    track('performance_metric', {
      metric_name: metricName,
      value,
      unit,
    });
  },

  // Custom events
  customEvent(eventName: string, properties?: Record<string, any>) {
    track(eventName, properties);
  },
};

// Convenience exports
export { track as trackEvent, identifyUser, resetUser, trackPageView };

export default analytics;

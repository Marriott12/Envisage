import posthog from 'posthog-js';

// PostHog configuration for product analytics
export function initPostHog() {
  if (typeof window === 'undefined') return;

  const apiKey = process.env.NEXT_PUBLIC_POSTHOG_KEY;
  const host = process.env.NEXT_PUBLIC_POSTHOG_HOST || 'https://app.posthog.com';

  if (!apiKey) {
    console.warn('PostHog API key not configured. Analytics is disabled.');
    return;
  }

  posthog.init(apiKey, {
    api_host: host,
    
    // Automatically capture events
    autocapture: true,
    
    // Capture pageviews
    capture_pageview: true,
    
    // Capture performance events
    capture_pageleave: true,
    
    // Session recording
    session_recording: {
      enabled: true,
      maskAllInputs: true,
      maskTextSelector: '[data-private]',
      recordCrossOriginIframes: false,
    },

    // Feature flags
    bootstrap: {
      featureFlags: {},
    },

    // Disable in development (optional)
    loaded: (posthog) => {
      if (process.env.NODE_ENV === 'development' && !process.env.POSTHOG_DEV_MODE) {
        posthog.opt_out_capturing();
      }
    },

    // Person profiles
    person_profiles: 'identified_only',
  });
}

// Event tracking utilities
export function trackEvent(eventName: string, properties?: Record<string, any>) {
  if (typeof window === 'undefined') return;
  posthog.capture(eventName, properties);
}

export function trackPageView(url?: string) {
  if (typeof window === 'undefined') return;
  posthog.capture('$pageview', { $current_url: url || window.location.href });
}

// User identification
export function identifyUser(userId: string, properties?: Record<string, any>) {
  if (typeof window === 'undefined') return;
  posthog.identify(userId, properties);
}

export function resetUser() {
  if (typeof window === 'undefined') return;
  posthog.reset();
}

// User properties
export function setUserProperties(properties: Record<string, any>) {
  if (typeof window === 'undefined') return;
  posthog.people.set(properties);
}

// Group analytics
export function setGroup(groupType: string, groupKey: string, groupProperties?: Record<string, any>) {
  if (typeof window === 'undefined') return;
  posthog.group(groupType, groupKey, groupProperties);
}

// Feature flags
export function isFeatureEnabled(flagKey: string): boolean {
  if (typeof window === 'undefined') return false;
  return posthog.isFeatureEnabled(flagKey) || false;
}

export function getFeatureFlag(flagKey: string): string | boolean | undefined {
  if (typeof window === 'undefined') return undefined;
  return posthog.getFeatureFlag(flagKey);
}

export function onFeatureFlags(callback: (flags: string[], variants: Record<string, string | boolean>) => void) {
  if (typeof window === 'undefined') return;
  posthog.onFeatureFlags(callback);
}

// A/B Testing
export function getExperimentVariant(experimentKey: string): string | undefined {
  if (typeof window === 'undefined') return undefined;
  const variant = posthog.getFeatureFlag(experimentKey);
  return typeof variant === 'string' ? variant : undefined;
}

// E-commerce tracking
export const ecommerce = {
  viewProduct(product: {
    id: string;
    name: string;
    price: number;
    category?: string;
    brand?: string;
  }) {
    trackEvent('product_viewed', {
      product_id: product.id,
      product_name: product.name,
      product_price: product.price,
      product_category: product.category,
      product_brand: product.brand,
    });
  },

  addToCart(product: {
    id: string;
    name: string;
    price: number;
    quantity: number;
    category?: string;
  }) {
    trackEvent('product_added_to_cart', {
      product_id: product.id,
      product_name: product.name,
      product_price: product.price,
      quantity: product.quantity,
      product_category: product.category,
      cart_value: product.price * product.quantity,
    });
  },

  removeFromCart(product: {
    id: string;
    name: string;
    price: number;
    quantity: number;
  }) {
    trackEvent('product_removed_from_cart', {
      product_id: product.id,
      product_name: product.name,
      product_price: product.price,
      quantity: product.quantity,
    });
  },

  initiateCheckout(cart: {
    items: Array<{ id: string; name: string; price: number; quantity: number }>;
    total: number;
  }) {
    trackEvent('checkout_initiated', {
      cart_items: cart.items.length,
      cart_total: cart.total,
      product_ids: cart.items.map(i => i.id),
    });
  },

  completeCheckout(order: {
    orderId: string;
    total: number;
    items: Array<{ id: string; name: string; price: number; quantity: number }>;
    paymentMethod?: string;
    shippingMethod?: string;
  }) {
    trackEvent('checkout_completed', {
      order_id: order.orderId,
      order_total: order.total,
      order_items: order.items.length,
      payment_method: order.paymentMethod,
      shipping_method: order.shippingMethod,
      product_ids: order.items.map(i => i.id),
    });
  },

  viewCategory(category: string, productsCount?: number) {
    trackEvent('category_viewed', {
      category_name: category,
      products_count: productsCount,
    });
  },

  search(query: string, resultsCount?: number) {
    trackEvent('search_performed', {
      search_query: query,
      results_count: resultsCount,
    });
  },
};

// Custom funnel tracking
export function trackFunnelStep(funnelName: string, stepName: string, properties?: Record<string, any>) {
  trackEvent(`${funnelName}_${stepName}`, {
    funnel: funnelName,
    step: stepName,
    ...properties,
  });
}

// Session properties
export function setSessionProperty(key: string, value: any) {
  if (typeof window === 'undefined') return;
  posthog.register({ [key]: value });
}

export function clearSessionProperty(key: string) {
  if (typeof window === 'undefined') return;
  posthog.unregister(key);
}

export default posthog;

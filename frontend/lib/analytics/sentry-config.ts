import * as Sentry from '@sentry/nextjs';

// Sentry configuration for error tracking and performance monitoring
export function initSentry() {
  if (typeof window === 'undefined') return;

  const dsn = process.env.NEXT_PUBLIC_SENTRY_DSN;
  
  if (!dsn) {
    console.warn('Sentry DSN not configured. Error tracking is disabled.');
    return;
  }

  Sentry.init({
    dsn,
    
    // Environment configuration
    environment: process.env.NEXT_PUBLIC_VERCEL_ENV || process.env.NODE_ENV || 'development',
    
    // Performance Monitoring
    tracesSampleRate: process.env.NODE_ENV === 'production' ? 0.1 : 1.0,
    
    // Session Replay
    replaysSessionSampleRate: 0.1, // 10% of sessions
    replaysOnErrorSampleRate: 1.0, // 100% of sessions with errors
    
    // Integrations
    integrations: [
      new Sentry.BrowserTracing({
        // Track navigation timing
        tracingOrigins: ['localhost', /^\//],
        routingInstrumentation: Sentry.nextRouterInstrumentation,
      }),
      new Sentry.Replay({
        // Mask all text and images by default
        maskAllText: true,
        blockAllMedia: true,
        // Network details
        networkDetailAllowUrls: [/^\/api\//],
        networkCaptureBodies: true,
      }),
    ],

    // Before send hook for filtering
    beforeSend(event, hint) {
      // Don't send events in development (optional)
      if (process.env.NODE_ENV === 'development' && !process.env.SENTRY_DEV_MODE) {
        return null;
      }

      // Filter out certain errors
      if (event.exception) {
        const error = hint.originalException;
        
        // Ignore network errors that are expected
        if (error instanceof Error && error.message.includes('NetworkError')) {
          return null;
        }

        // Ignore ResizeObserver errors (common browser bug)
        if (error instanceof Error && error.message.includes('ResizeObserver')) {
          return null;
        }
      }

      return event;
    },

    // Ignore certain errors
    ignoreErrors: [
      // Browser extensions
      'top.GLOBALS',
      'fb_xd_fragment',
      // Random plugins/extensions
      'Can\'t find variable: ZiteReader',
      'jigsaw is not defined',
      'ComboSearch is not defined',
      // Network errors
      'NetworkError',
      'Failed to fetch',
      'Load failed',
    ],

    // Deny URLs from being sent
    denyUrls: [
      // Browser extensions
      /extensions\//i,
      /^chrome:\/\//i,
      /^moz-extension:\/\//i,
    ],
  });
}

// Custom error tracking utilities
export function captureException(error: Error, context?: Record<string, any>) {
  Sentry.captureException(error, {
    contexts: {
      custom: context,
    },
  });
}

export function captureMessage(message: string, level: Sentry.SeverityLevel = 'info') {
  Sentry.captureMessage(message, level);
}

export function setUser(user: { id: string; email?: string; username?: string }) {
  Sentry.setUser(user);
}

export function clearUser() {
  Sentry.setUser(null);
}

export function addBreadcrumb(breadcrumb: {
  message: string;
  category?: string;
  level?: Sentry.SeverityLevel;
  data?: Record<string, any>;
}) {
  Sentry.addBreadcrumb(breadcrumb);
}

// Performance monitoring
export function startTransaction(name: string, op: string) {
  return Sentry.startTransaction({ name, op });
}

export function measurePerformance(name: string, fn: () => void | Promise<void>) {
  const transaction = Sentry.startTransaction({
    name,
    op: 'custom',
  });

  try {
    const result = fn();
    
    if (result instanceof Promise) {
      return result.finally(() => transaction.finish());
    }
    
    transaction.finish();
    return result;
  } catch (error) {
    transaction.setStatus('internal_error');
    transaction.finish();
    throw error;
  }
}

// API error handler
export function handleApiError(error: any, endpoint: string) {
  addBreadcrumb({
    message: `API Error: ${endpoint}`,
    category: 'api',
    level: 'error',
    data: {
      endpoint,
      status: error.response?.status,
      statusText: error.response?.statusText,
    },
  });

  captureException(error, {
    endpoint,
    response: error.response?.data,
  });
}

// Custom span for measuring specific operations
export function withSpan<T>(
  name: string,
  op: string,
  fn: () => T | Promise<T>
): T | Promise<T> {
  const span = Sentry.getCurrentHub().getScope()?.getTransaction()?.startChild({
    op,
    description: name,
  });

  try {
    const result = fn();
    
    if (result instanceof Promise) {
      return result.finally(() => span?.finish()) as T;
    }
    
    span?.finish();
    return result;
  } catch (error) {
    span?.setStatus('internal_error');
    span?.finish();
    throw error;
  }
}

export default Sentry;

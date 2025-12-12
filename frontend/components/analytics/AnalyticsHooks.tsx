'use client';

import { useEffect } from 'react';
import { usePathname } from 'next/navigation';
import { analytics } from '@/lib/analytics/unified-analytics';

/**
 * Hook for tracking custom events
 */
export function useAnalytics() {
  return analytics;
}

/**
 * Hook for automatic product view tracking
 */
export function useProductTracking(product: {
  id: string;
  name: string;
  price: number;
  category?: string;
  brand?: string;
} | null) {
  useEffect(() => {
    if (product) {
      analytics.productViewed(product);
    }
  }, [product?.id]);
}

/**
 * Hook for tracking page engagement time
 */
export function usePageEngagement() {
  const pathname = usePathname();

  useEffect(() => {
    const startTime = Date.now();

    return () => {
      const engagementTime = Date.now() - startTime;
      analytics.customEvent('page_engagement', {
        page: pathname,
        engagement_time_ms: engagementTime,
        engagement_time_seconds: Math.round(engagementTime / 1000),
      });
    };
  }, [pathname]);
}

/**
 * Hook for tracking scroll depth
 */
export function useScrollTracking() {
  const pathname = usePathname();

  useEffect(() => {
    let maxScroll = 0;
    const milestones = [25, 50, 75, 100];
    const reached = new Set<number>();

    const handleScroll = () => {
      const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrolled = (window.scrollY / scrollHeight) * 100;
      
      maxScroll = Math.max(maxScroll, scrolled);

      milestones.forEach((milestone) => {
        if (scrolled >= milestone && !reached.has(milestone)) {
          reached.add(milestone);
          analytics.customEvent('scroll_depth', {
            page: pathname,
            depth_percentage: milestone,
          });
        }
      });
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    
    return () => {
      window.removeEventListener('scroll', handleScroll);
      
      // Track final scroll depth on unmount
      if (maxScroll > 0) {
        analytics.customEvent('max_scroll_depth', {
          page: pathname,
          max_depth_percentage: Math.round(maxScroll),
        });
      }
    };
  }, [pathname]);
}

/**
 * Hook for tracking clicks outside a specific element
 */
export function useClickTracking(elementId: string, eventName: string) {
  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      const element = document.getElementById(elementId);
      if (element && !element.contains(e.target as Node)) {
        analytics.customEvent(eventName, {
          element_id: elementId,
          click_outside: true,
        });
      }
    };

    document.addEventListener('click', handleClick);
    return () => document.removeEventListener('click', handleClick);
  }, [elementId, eventName]);
}

/**
 * Hook for tracking form abandonment
 */
export function useFormTracking(formId: string) {
  useEffect(() => {
    const form = document.getElementById(formId) as HTMLFormElement;
    if (!form) return;

    let hasInteracted = false;
    let formData: Record<string, any> = {};

    const handleInput = (e: Event) => {
      hasInteracted = true;
      const target = e.target as HTMLInputElement;
      formData[target.name] = target.value;
    };

    const handleSubmit = () => {
      analytics.formSubmitted(formId, true, {
        fields_filled: Object.keys(formData).length,
      });
      hasInteracted = false;
    };

    form.addEventListener('input', handleInput);
    form.addEventListener('submit', handleSubmit);

    return () => {
      if (hasInteracted) {
        analytics.customEvent('form_abandoned', {
          form_id: formId,
          fields_filled: Object.keys(formData).length,
          last_field: Object.keys(formData).pop(),
        });
      }

      form.removeEventListener('input', handleInput);
      form.removeEventListener('submit', handleSubmit);
    };
  }, [formId]);
}

/**
 * Hook for tracking visibility of an element
 */
export function useVisibilityTracking(elementId: string, eventName: string) {
  useEffect(() => {
    const element = document.getElementById(elementId);
    if (!element) return;

    let hasBeenVisible = false;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !hasBeenVisible) {
            hasBeenVisible = true;
            analytics.customEvent(eventName, {
              element_id: elementId,
              visible: true,
            });
          }
        });
      },
      { threshold: 0.5 }
    );

    observer.observe(element);

    return () => {
      observer.disconnect();
    };
  }, [elementId, eventName]);
}

/**
 * Hook for tracking errors in components
 */
export function useErrorTracking(componentName: string) {
  useEffect(() => {
    const handleError = (event: ErrorEvent) => {
      analytics.errorOccurred(event.error, {
        component: componentName,
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
      });
    };

    window.addEventListener('error', handleError);
    return () => window.removeEventListener('error', handleError);
  }, [componentName]);
}

/**
 * Hook for tracking API response times
 */
export function useApiTracking() {
  const trackApiCall = (endpoint: string, method: string, duration: number, status: number) => {
    analytics.performanceMetric(`api_${method.toLowerCase()}_${endpoint}`, duration);
    
    if (status >= 400) {
      analytics.customEvent('api_error', {
        endpoint,
        method,
        status,
        duration,
      });
    }
  };

  return { trackApiCall };
}

/**
 * Component for tracking button clicks
 */
interface TrackClickProps {
  eventName: string;
  properties?: Record<string, any>;
  children: React.ReactElement;
}

export function TrackClick({ eventName, properties, children }: TrackClickProps) {
  const handleClick = (e: React.MouseEvent) => {
    analytics.customEvent(eventName, properties);
    
    // Call original onClick if it exists
    if (children.props.onClick) {
      children.props.onClick(e);
    }
  };

  return <>{React.cloneElement(children, { onClick: handleClick })}</>;
}

/**
 * Component for tracking link clicks
 */
interface TrackLinkProps {
  href: string;
  eventName?: string;
  properties?: Record<string, any>;
  children: React.ReactNode;
  className?: string;
}

export function TrackLink({
  href,
  eventName = 'link_clicked',
  properties,
  children,
  className,
}: TrackLinkProps) {
  const handleClick = () => {
    analytics.customEvent(eventName, {
      href,
      ...properties,
    });
  };

  return (
    <a href={href} onClick={handleClick} className={className}>
      {children}
    </a>
  );
}

export default {
  useAnalytics,
  useProductTracking,
  usePageEngagement,
  useScrollTracking,
  useClickTracking,
  useFormTracking,
  useVisibilityTracking,
  useErrorTracking,
  useApiTracking,
  TrackClick,
  TrackLink,
};

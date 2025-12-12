'use client';

import { useEffect, useState } from 'react';
import { getFeatureFlag, getExperimentVariant, onFeatureFlags } from '@/lib/analytics/posthog-config';

/**
 * Hook for using feature flags
 */
export function useFeatureFlag(flagKey: string, defaultValue: boolean = false): boolean {
  const [isEnabled, setIsEnabled] = useState(defaultValue);

  useEffect(() => {
    const checkFlag = () => {
      const flagValue = getFeatureFlag(flagKey);
      setIsEnabled(flagValue === true);
    };

    checkFlag();

    // Listen for flag changes
    const cleanup = onFeatureFlags(() => {
      checkFlag();
    });

    return cleanup;
  }, [flagKey]);

  return isEnabled;
}

/**
 * Hook for A/B testing experiments
 */
export function useExperiment(experimentKey: string, variants: string[] = ['control', 'test']) {
  const [variant, setVariant] = useState<string>(variants[0]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const loadVariant = () => {
      const experimentVariant = getExperimentVariant(experimentKey);
      
      if (experimentVariant && variants.includes(experimentVariant)) {
        setVariant(experimentVariant);
      } else {
        // Fallback to control
        setVariant(variants[0]);
      }
      
      setIsLoading(false);
    };

    loadVariant();

    // Listen for flag changes
    const cleanup = onFeatureFlags(() => {
      loadVariant();
    });

    return cleanup;
  }, [experimentKey, variants]);

  return { variant, isLoading, isControl: variant === variants[0] };
}

/**
 * Component for A/B testing with render props
 */
interface ABTestProps {
  experimentKey: string;
  variants: {
    control: React.ReactNode;
    [key: string]: React.ReactNode;
  };
  defaultVariant?: string;
}

export function ABTest({ experimentKey, variants, defaultVariant = 'control' }: ABTestProps) {
  const { variant, isLoading } = useExperiment(experimentKey, Object.keys(variants));

  if (isLoading) {
    return variants[defaultVariant] || null;
  }

  return <>{variants[variant] || variants[defaultVariant]}</>;
}

/**
 * Feature flag component
 */
interface FeatureFlagProps {
  flag: string;
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

export function FeatureFlag({ flag, children, fallback = null }: FeatureFlagProps) {
  const isEnabled = useFeatureFlag(flag);

  if (!isEnabled) {
    return <>{fallback}</>;
  }

  return <>{children}</>;
}

/**
 * Multi-variant test component
 */
interface MultiVariantTestProps {
  experimentKey: string;
  children: (variant: string, isLoading: boolean) => React.ReactNode;
}

export function MultiVariantTest({ experimentKey, children }: MultiVariantTestProps) {
  const { variant, isLoading } = useExperiment(experimentKey);

  return <>{children(variant, isLoading)}</>;
}

/**
 * Example experiments configuration
 */
export const experiments = {
  // Homepage hero CTA
  HOMEPAGE_CTA: 'homepage_cta_test',
  
  // Product page layout
  PRODUCT_LAYOUT: 'product_page_layout',
  
  // Checkout flow
  CHECKOUT_FLOW: 'checkout_flow_test',
  
  // Search results
  SEARCH_RESULTS: 'search_results_test',
  
  // Pricing display
  PRICING_DISPLAY: 'pricing_display_test',
  
  // Recommendation algorithm
  RECOMMENDATIONS: 'recommendations_algorithm',
};

/**
 * Example feature flags
 */
export const features = {
  // New features
  NEW_SEARCH: 'new_search_feature',
  VISUAL_SEARCH: 'visual_search',
  VOICE_SEARCH: 'voice_search',
  
  // Social features
  SOCIAL_SHARING: 'social_sharing',
  USER_REVIEWS: 'user_reviews',
  
  // Payment methods
  APPLE_PAY: 'apple_pay_enabled',
  CRYPTO_PAYMENTS: 'crypto_payments',
  
  // Advanced features
  AR_PREVIEW: 'ar_preview',
  AI_ASSISTANT: 'ai_shopping_assistant',
  
  // Beta features
  BETA_DASHBOARD: 'beta_dashboard',
  EARLY_ACCESS: 'early_access_program',
};

export default { useFeatureFlag, useExperiment, ABTest, FeatureFlag, experiments, features };

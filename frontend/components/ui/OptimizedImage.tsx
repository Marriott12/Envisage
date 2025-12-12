import Image, { ImageProps } from 'next/image';
import { useState } from 'react';
import { useIntersectionObserver } from '@/hooks/usePerformance';

interface OptimizedImageProps extends Omit<ImageProps, 'onLoad'> {
  blurDataURL?: string;
  fallbackSrc?: string;
  aspectRatio?: string;
  showSkeleton?: boolean;
  onLoadComplete?: () => void;
}

export const OptimizedImage = ({
  src,
  alt,
  blurDataURL,
  fallbackSrc = '/images/placeholder.png',
  aspectRatio,
  showSkeleton = true,
  onLoadComplete,
  className = '',
  ...props
}: OptimizedImageProps) => {
  const [isLoaded, setIsLoaded] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [ref, isInView] = useIntersectionObserver({
    threshold: 0.01,
    rootMargin: '50px',
  });

  const handleLoad = () => {
    setIsLoaded(true);
    onLoadComplete?.();
  };

  const handleError = () => {
    setHasError(true);
    setIsLoaded(true);
  };

  const imageSrc = hasError ? fallbackSrc : src;

  return (
    <div
      ref={ref as (node: Element | null) => void}
      className={`relative overflow-hidden bg-gray-100 ${className}`}
      style={{ aspectRatio: aspectRatio || 'auto' }}
    >
      {showSkeleton && !isLoaded && (
        <div className="absolute inset-0 animate-pulse bg-gray-200">
          <div className="h-full w-full bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 bg-[length:200%_100%] animate-shimmer" />
        </div>
      )}

      {isInView && (
        <Image
          src={imageSrc}
          alt={alt}
          onLoad={handleLoad}
          onError={handleError}
          placeholder={blurDataURL ? 'blur' : 'empty'}
          blurDataURL={blurDataURL}
          className={`transition-opacity duration-300 ${
            isLoaded ? 'opacity-100' : 'opacity-0'
          }`}
          {...props}
        />
      )}
    </div>
  );
};

// Shimmer animation utility
export const shimmerKeyframes = `
  @keyframes shimmer {
    0% {
      background-position: -200% 0;
    }
    100% {
      background-position: 200% 0;
    }
  }
`;

import React from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { 
  MapPinIcon, 
  ClockIcon, 
  EyeIcon,
  HeartIcon,
  StarIcon
} from '@heroicons/react/24/outline';
import { HeartIcon as HeartSolid } from '@heroicons/react/24/solid';
import { formatPrice, formatRelativeTime, getConditionBadgeColor, capitalizeFirst, cn } from '@/lib/utils';
import type { Listing } from '@/lib/api';

interface ListingCardProps {
  listing: Listing;
  className?: string;
  showFavorite?: boolean;
  onFavoriteToggle?: (listingId: number, isFavorite: boolean) => void;
  isFavorite?: boolean;
}

export default function ListingCard({ 
  listing, 
  className,
  showFavorite = true,
  onFavoriteToggle,
  isFavorite = false
}: ListingCardProps) {
  const [imageError, setImageError] = React.useState(false);
  const [isLoading, setIsLoading] = React.useState(true);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (onFavoriteToggle) {
      onFavoriteToggle(listing.id, !isFavorite);
    }
  };

  const primaryImage = listing.images && listing.images.length > 0 
    ? listing.images[0] 
    : '/images/placeholder.jpg';

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
      className={cn('group', className)}
    >
      <Link href={`/marketplace/${listing.id}`}>
        <div className="card-hover overflow-hidden">
          {/* Image Section */}
          <div className="relative aspect-[4/3] overflow-hidden">
            {!imageError ? (
              <Image
                src={primaryImage}
                alt={listing.title}
                fill
                className={cn(
                  'object-cover transition-all duration-300 group-hover:scale-105',
                  isLoading ? 'scale-110 blur-sm' : 'scale-100 blur-0'
                )}
                onLoad={() => setIsLoading(false)}
                onError={() => {
                  setImageError(true);
                  setIsLoading(false);
                }}
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
              />
            ) : (
              <div className="w-full h-full bg-gray-200 flex items-center justify-center">
                <div className="text-gray-400 text-center">
                  <div className="w-16 h-16 mx-auto mb-2 bg-gray-300 rounded-lg flex items-center justify-center">
                    <svg className="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <p className="text-sm">No image available</p>
                </div>
              </div>
            )}

            {/* Loading overlay */}
            {isLoading && (
              <div className="absolute inset-0 bg-gray-200 animate-pulse" />
            )}

            {/* Status Badge */}
            {listing.status !== 'active' && (
              <div className="absolute top-2 left-2">
                <span className={cn(
                  'badge',
                  listing.status === 'sold' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                )}>
                  {capitalizeFirst(listing.status)}
                </span>
              </div>
            )}

            {/* Condition Badge */}
            <div className="absolute top-2 right-2">
              <span className={cn('badge', getConditionBadgeColor(listing.condition_type || ''))}>
                {capitalizeFirst((listing.condition_type || 'new').replace('_', ' '))}
              </span>
            </div>

            {/* Favorite Button */}
            {showFavorite && (
              <button
                onClick={handleFavoriteClick}
                className="absolute bottom-2 right-2 p-2 rounded-full bg-white/80 hover:bg-white transition-colors duration-200 shadow-md"
                aria-label={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
              >
                {isFavorite ? (
                  <HeartSolid className="w-5 h-5 text-red-500" />
                ) : (
                  <HeartIcon className="w-5 h-5 text-gray-600 hover:text-red-500" />
                )}
              </button>
            )}

            {/* Multiple Images Indicator */}
            {listing.images && listing.images.length > 1 && (
              <div className="absolute bottom-2 left-2 flex space-x-1">
                {listing.images.slice(0, 3).map((_, index) => (
                  <div
                    key={index}
                    className={cn(
                      'w-2 h-2 rounded-full',
                      index === 0 ? 'bg-white' : 'bg-white/60'
                    )}
                  />
                ))}
                {listing.images.length > 3 && (
                  <div className="text-white text-xs bg-black/50 rounded px-1">
                    +{listing.images.length - 3}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Content Section */}
          <div className="p-4">
            {/* Title and Price */}
            <div className="mb-2">
              <h3 className="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-primary-600 transition-colors">
                {listing.title}
              </h3>
              <div className="flex items-center justify-between">
                <span className="text-2xl font-bold text-primary-600">
                  {formatPrice(listing.price, listing.currency)}
                </span>
                {listing.seller_rating && (
                  <div className="flex items-center space-x-1">
                    <StarIcon className="w-4 h-4 fill-yellow-400 text-yellow-400" />
                    <span className="text-sm text-gray-600">
                      {listing.seller_rating.toFixed(1)}
                    </span>
                  </div>
                )}
              </div>
            </div>

            {/* Description */}
            <p className="text-gray-600 text-sm mb-3 line-clamp-2">
              {listing.description}
            </p>

            {/* Meta Information */}
            <div className="space-y-2">
              {/* Location and Time */}
              <div className="flex items-center justify-between text-sm text-gray-500">
                <div className="flex items-center space-x-1">
                  <MapPinIcon className="w-4 h-4" />
                  <span>{listing.location}</span>
                </div>
                <div className="flex items-center space-x-1">
                  <ClockIcon className="w-4 h-4" />
                  <span>{formatRelativeTime(listing.created_at)}</span>
                </div>
              </div>

              {/* Seller and Views */}
              <div className="flex items-center justify-between text-sm text-gray-500">
                <div className="flex items-center space-x-1">
                  <div className="w-4 h-4 bg-gray-300 rounded-full flex items-center justify-center">
                    <span className="text-xs text-white font-medium">
                      {(listing.seller_name || listing.seller?.name || 'S').charAt(0).toUpperCase()}
                    </span>
                  </div>
                  <span className="truncate max-w-[100px]">
                    {listing.seller_name || listing.seller?.name || 'Seller'}
                  </span>
                </div>
                <div className="flex items-center space-x-1">
                  <EyeIcon className="w-4 h-4" />
                  <span>{listing.views || 0}</span>
                </div>
              </div>
            </div>

            {/* Category */}
            <div className="mt-3">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                {capitalizeFirst(typeof listing.category === 'string' ? listing.category : listing.category?.name || 'Other')}
              </span>
            </div>
          </div>
        </div>
      </Link>
    </motion.div>
  );
}

// Loading skeleton component
export function ListingCardSkeleton({ className }: { className?: string }) {
  return (
    <div className={cn('card overflow-hidden', className)}>
      <div className="aspect-[4/3] bg-gray-200 animate-pulse" />
      <div className="p-4">
        <div className="h-4 bg-gray-200 animate-pulse rounded mb-2" />
        <div className="h-6 bg-gray-200 animate-pulse rounded mb-2 w-3/4" />
        <div className="h-3 bg-gray-200 animate-pulse rounded mb-1" />
        <div className="h-3 bg-gray-200 animate-pulse rounded mb-3 w-2/3" />
        <div className="flex justify-between">
          <div className="h-3 bg-gray-200 animate-pulse rounded w-1/4" />
          <div className="h-3 bg-gray-200 animate-pulse rounded w-1/4" />
        </div>
      </div>
    </div>
  );
}

// Grid wrapper for consistent spacing
export function ListingGrid({ 
  children, 
  className 
}: { 
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div className={cn(
      'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6',
      className
    )}>
      {children}
    </div>
  );
}

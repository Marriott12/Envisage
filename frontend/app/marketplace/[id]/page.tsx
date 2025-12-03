'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { toast } from 'react-hot-toast';
import {
  ChevronLeftIcon,
  HeartIcon,
  ShareIcon,
  StarIcon,
  MapPinIcon,
  ClockIcon,
  TagIcon,
  UserIcon,
  ShieldCheckIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
  ChevronDownIcon,
} from '@heroicons/react/24/outline';
import { HeartIcon as HeartSolid } from '@heroicons/react/24/solid';
import { formatPrice, formatDate, getConditionColor, getConditionLabel } from '@/lib/utils';
import { marketplaceApi, type Listing } from '@/lib/api';
import { useAuth } from '@/hooks/useAuth';
import { useCartStore, useAuthStore } from '@/lib/store';
import CheckoutModal from '@/components/CheckoutModal';
import ProductReviews from '@/components/ProductReviews';
import RecentlyViewedProducts, { addToRecentlyViewed } from '@/components/RecentlyViewedProducts';
import Header from '@/components/Header';

export default function ListingDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [listing, setListing] = useState<Listing | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [isFavorited, setIsFavorited] = useState(false);
  const [showFullDescription, setShowFullDescription] = useState(false);
  
  const { user, isAuthenticated } = useAuth();
  const { addItem } = useCartStore();

  const listingId = params.id as string;

  useEffect(() => {
    if (listingId) {
      fetchListing();
    }
  }, [listingId]);

  const fetchListing = async () => {
    try {
      setIsLoading(true);
      setError(null);
      const response = await marketplaceApi.getListing(parseInt(listingId));
      
      if (response.status === 'success') {
        // Transform backend data to match frontend expectations
        const product = response.data.listing || response.data;
        const transformedListing = {
          ...product,
          price: typeof product.price === 'string' ? parseFloat(product.price) : product.price,
          currency: product.currency || 'ZMW',
          images: product.images_urls || product.images || [],
          seller_name: product.seller?.name || product.seller_name,
          seller_email: product.seller?.email || product.seller_email,
        };
        setListing(transformedListing);
        
        // Add to recently viewed
        addToRecentlyViewed({
          id: transformedListing.id,
          title: transformedListing.title,
          price: transformedListing.price,
          currency: transformedListing.currency,
          image: transformedListing.images[0],
          rating: transformedListing.rating,
        });
      } else {
        setError(response.message || 'Failed to load listing');
      }
    } catch (err: any) {
      console.error('Error fetching listing:', err);
      setError(err.message || 'Failed to load listing');
    } finally {
      setIsLoading(false);
    }
  };

  const handleBuyNow = () => {
    if (!isAuthenticated) {
      toast.error('Please login to make a purchase');
      router.push('/login');
      return;
    }
    setIsCheckoutOpen(true);
  };

  const handleAddToCart = () => {
    if (!listing) return;
    
    if (!isAuthenticated) {
      toast.error('Please login to add items to cart');
      router.push('/login');
      return;
    }

    addItem({
      id: listing.id,
      listing_id: listing.id,
      title: listing.title,
      price: listing.price,
      currency: listing.currency,
      image: listing.images?.[0] || '',
      seller_name: listing.seller_name || 'Unknown Seller',
    });
    
    toast.success('Added to cart!');
  };

  const handleToggleFavorite = () => {
    if (!isAuthenticated) {
      toast.error('Please login to save favorites');
      return;
    }
    
    setIsFavorited(!isFavorited);
    toast.success(isFavorited ? 'Removed from favorites' : 'Added to favorites');
  };

  const handleShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: listing?.title,
          text: listing?.description,
          url: window.location.href,
        });
      } catch (err) {
        console.log('Share cancelled');
      }
    } else {
      await navigator.clipboard.writeText(window.location.href);
      toast.success('Link copied to clipboard!');
    }
  };

  const handleOrderSuccess = (orderId: number, paymentUrl?: string) => {
    if (paymentUrl) {
      window.location.href = paymentUrl;
    } else {
      toast.success('Order placed successfully!');
      router.push(`/orders/${orderId}`);
    }
  };

  const nextImage = () => {
    if (listing?.images) {
      setCurrentImageIndex((prev) => 
        prev === listing.images!.length - 1 ? 0 : prev + 1
      );
    }
  };

  const prevImage = () => {
    if (listing?.images) {
      setCurrentImageIndex((prev) => 
        prev === 0 ? listing.images!.length - 1 : prev - 1
      );
    }
  };

  if (isLoading) {
    return (
      <>
        <Header />
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <div className="loading-spinner w-8 h-8 mx-auto mb-4" />
            <p className="text-gray-600">Loading listing...</p>
          </div>
        </div>
      </>
    );
  }

  if (error || !listing) {
    return (
      <>
        <Header />
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <p className="text-red-600 mb-4">{error || 'Listing not found'}</p>
            <Link href="/marketplace" className="btn-primary">
              Back to Marketplace
            </Link>
          </div>
        </div>
      </>
    );
  }

  const images = listing.images || [];
  const hasMultipleImages = images.length > 1;

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <div className="bg-white border-b sticky top-0 z-40">
          <div className="container mx-auto px-4">
            <div className="flex items-center justify-between h-16">
              <div className="flex items-center gap-4">
                <button
                  onClick={() => router.back()}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ChevronLeftIcon className="h-6 w-6" />
                </button>
                <h1 className="font-semibold text-gray-900 truncate max-w-md">
                  {listing.title}
                </h1>
              </div>
              
              <div className="flex items-center gap-2">
                <button
                  onClick={handleToggleFavorite}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  {isFavorited ? (
                    <HeartSolid className="h-6 w-6 text-red-500" />
                  ) : (
                    <HeartIcon className="h-6 w-6" />
                  )}
                </button>
                <button
                  onClick={handleShare}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ShareIcon className="h-6 w-6" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="container mx-auto px-4 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Images */}
            <div className="lg:col-span-2">
              <div className="bg-white rounded-2xl overflow-hidden shadow-sm">
                {/* Main Image */}
                <div className="relative aspect-square bg-gray-100">
                  {images.length > 0 ? (
                    <>
                      <Image
                        src={images[currentImageIndex]}
                        alt={listing.title}
                        fill
                        className="object-cover"
                        priority
                      />
                      
                      {hasMultipleImages && (
                        <>
                          <button
                            onClick={prevImage}
                            className="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-black/50 text-white rounded-full hover:bg-black/70 transition-colors"
                          >
                            <ArrowLeftIcon className="h-5 w-5" />
                          </button>
                          <button
                            onClick={nextImage}
                            className="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-black/50 text-white rounded-full hover:bg-black/70 transition-colors"
                          >
                            <ArrowRightIcon className="h-5 w-5" />
                          </button>
                          
                          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
                            {currentImageIndex + 1} / {images.length}
                          </div>
                        </>
                      )}
                      
                      <div className="absolute top-4 left-4">
                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${getConditionColor(listing.condition || '')}`}>
                          {getConditionLabel(listing.condition || '')}
                        </span>
                      </div>
                    </>
                  ) : (
                    <div className="flex items-center justify-center h-full">
                      <div className="text-center text-gray-400">
                        <TagIcon className="h-16 w-16 mx-auto mb-2" />
                        <p>No image available</p>
                      </div>
                    </div>
                  )}
                </div>

                {/* Image Thumbnails */}
                {hasMultipleImages && (
                  <div className="p-4 border-t">
                    <div className="flex gap-2 overflow-x-auto">
                      {images.map((image, index) => (
                        <button
                          key={index}
                          onClick={() => setCurrentImageIndex(index)}
                          className={`flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-colors ${
                            currentImageIndex === index
                              ? 'border-primary-500'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <Image
                            src={image}
                            alt={`${listing.title} - Image ${index + 1}`}
                            width={80}
                            height={80}
                            className="w-full h-full object-cover"
                          />
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Description */}
              <div className="bg-white rounded-2xl p-6 shadow-sm mt-6">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Description</h2>
                <div className="prose prose-gray max-w-none">
                  <p className={`text-gray-600 leading-relaxed ${
                    showFullDescription ? '' : 'line-clamp-3'
                  }`}>
                    {listing.description}
                  </p>
                  {listing.description && listing.description.length > 200 && (
                    <button
                      onClick={() => setShowFullDescription(!showFullDescription)}
                      className="inline-flex items-center gap-1 text-primary-600 hover:text-primary-700 mt-2 font-medium"
                    >
                      {showFullDescription ? 'Show less' : 'Show more'}
                      <ChevronDownIcon className={`h-4 w-4 transition-transform ${
                        showFullDescription ? 'rotate-180' : ''
                      }`} />
                    </button>
                  )}
                </div>
              </div>

              {/* Seller Info */}
              <div className="bg-white rounded-2xl p-6 shadow-sm mt-6">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Seller Information</h2>
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                    <UserIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      <h3 className="font-medium text-gray-900">{listing.seller_name}</h3>
                      {listing.seller_rating && (
                        <div className="flex items-center gap-1">
                          <StarIcon className="h-4 w-4 text-yellow-400 fill-current" />
                          <span className="text-sm text-gray-600">{listing.seller_rating}</span>
                        </div>
                      )}
                    </div>
                    {listing.seller_location && (
                      <div className="flex items-center gap-1 text-sm text-gray-600 mb-2">
                        <MapPinIcon className="h-4 w-4" />
                        {listing.seller_location}
                      </div>
                    )}
                    <div className="flex items-center gap-1 text-sm text-gray-600">
                      <ClockIcon className="h-4 w-4" />
                      Member since {formatDate(listing.created_at)}
                    </div>
                  </div>
                </div>
                
                <div className="flex gap-3 mt-4">
                  <button className="btn-secondary flex-1">
                    Contact Seller
                  </button>
                  <Link
                    href={`/seller/${listing.seller_id}`}
                    className="btn-ghost flex-1 text-center"
                  >
                    View Profile
                  </Link>
                </div>
              </div>
            </div>

            {/* Purchase Panel */}
            <div className="lg:col-span-1">
              <div className="sticky top-24">
                <div className="bg-white rounded-2xl p-6 shadow-sm">
                  {/* Price */}
                  <div className="mb-6">
                    <div className="flex items-baseline gap-2 mb-2">
                      <span className="text-3xl font-bold text-gray-900">
                        {formatPrice(listing.price, listing.currency)}
                      </span>
                      {listing.original_price && listing.original_price > listing.price && (
                        <span className="text-lg text-gray-500 line-through">
                          {formatPrice(listing.original_price, listing.currency)}
                        </span>
                      )}
                    </div>
                    <p className="text-sm text-gray-600">Free shipping included</p>
                  </div>

                  {/* Key Details */}
                  <div className="space-y-3 mb-6">
                    <div className="flex items-center justify-between">
                      <span className="text-gray-600">Condition</span>
                      <span className={`px-2 py-1 rounded text-xs font-medium ${getConditionColor(listing.condition || '')}`}>
                        {getConditionLabel(listing.condition || '')}
                      </span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-gray-600">Category</span>
                      <span className="text-gray-900 capitalize">
                        {typeof listing.category === 'string' 
                          ? listing.category 
                          : listing.category?.name || 'Other'}
                      </span>
                    </div>
                    {listing.location && (
                      <div className="flex items-center justify-between">
                        <span className="text-gray-600">Location</span>
                        <span className="text-gray-900">{listing.location}</span>
                      </div>
                    )}
                    <div className="flex items-center justify-between">
                      <span className="text-gray-600">Listed</span>
                      <span className="text-gray-900">{formatDate(listing.created_at)}</span>
                    </div>
                  </div>

                  {/* Action Buttons */}
                  <div className="space-y-3">
                    <button
                      onClick={handleBuyNow}
                      className="btn-primary w-full py-3 text-lg font-semibold"
                    >
                      Buy Now
                    </button>
                    <button
                      onClick={handleAddToCart}
                      className="btn-secondary w-full py-3"
                    >
                      Add to Cart
                    </button>
                  </div>

                  {/* Security Notice */}
                  <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div className="flex items-start gap-3">
                      <ShieldCheckIcon className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                      <div>
                        <h4 className="font-medium text-green-800 mb-1">Secure Purchase</h4>
                        <p className="text-sm text-green-700">
                          Your payment is protected with escrow until you confirm receipt of the item.
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Additional Info */}
                  <div className="mt-4 space-y-2 text-sm text-gray-600">
                    <div className="flex items-center justify-between">
                      <span>Processing time</span>
                      <span>1-2 business days</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span>Shipping time</span>
                      <span>3-7 business days</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span>Return policy</span>
                      <span>30 days</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Recently Viewed Products */}
          <div className="mt-12">
            <RecentlyViewedProducts maxItems={6} />
          </div>

          {/* Product Reviews Section */}
          <div className="mt-12">
            <ProductReviews 
              productId={parseInt(listingId)} 
              canReview={isAuthenticated}
              onReviewSubmitted={fetchListing}
            />
          </div>
        </div>

        {/* Checkout Modal */}
        <CheckoutModal
          isOpen={isCheckoutOpen}
          onClose={() => setIsCheckoutOpen(false)}
          listing={listing}
          onOrderSuccess={handleOrderSuccess}
        />
      </div>
    </>
  );
}

'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Image from 'next/image';
import Header from '@/components/Header';
import PreOrderButton from '@/components/preorder/PreOrderButton';
import VideoReviewUpload from '@/components/reviews/VideoReviewUpload';
import BNPLCheckout from '@/components/bnpl/BNPLCheckout';
import api from '@/lib/api';
import { toast } from 'react-hot-toast';
import { 
  StarIcon, 
  HeartIcon, 
  ShareIcon,
  ShoppingCartIcon,
  PlayCircleIcon,
  CheckCircleIcon
} from '@heroicons/react/24/outline';
import { HeartIcon as HeartSolidIcon } from '@heroicons/react/24/solid';

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  images: string[];
  stock_quantity: number;
  rating: number;
  reviews_count: number;
  is_preorder: boolean;
  expected_ship_date?: string;
  preorder_limit?: number;
  orders_count?: number;
  charge_now?: boolean;
  deposit_amount?: number;
  category: string;
  seller: {
    id: number;
    name: string;
    rating: number;
  };
}

interface Review {
  id: number;
  user: {
    name: string;
    avatar?: string;
  };
  rating: number;
  comment: string;
  video_url?: string;
  created_at: string;
  helpful_count: number;
}

export default function ProductDetailPage() {
  const params = useParams();
  const router = useRouter();
  const productId = params?.id as string;

  const [product, setProduct] = useState<Product | null>(null);
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedImage, setSelectedImage] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [isInWishlist, setIsInWishlist] = useState(false);
  const [showBNPL, setShowBNPL] = useState(false);
  const [showVideoUpload, setShowVideoUpload] = useState(false);

  useEffect(() => {
    fetchProduct();
    fetchReviews();
    checkWishlist();
  }, [productId]);

  const fetchProduct = async () => {
    try {
      const response = await api.get(`/products/${productId}`);
      setProduct(response.data);
    } catch (error) {
      console.error('Failed to fetch product:', error);
      toast.error('Failed to load product');
    } finally {
      setLoading(false);
    }
  };

  const fetchReviews = async () => {
    try {
      const response = await api.get(`/products/${productId}/reviews`);
      setReviews(response.data.data || []);
    } catch (error) {
      console.error('Failed to fetch reviews:', error);
    }
  };

  const checkWishlist = async () => {
    try {
      const response = await api.get('/wishlist');
      const inWishlist = response.data.some((item: any) => item.product_id === parseInt(productId));
      setIsInWishlist(inWishlist);
    } catch (error) {
      console.error('Failed to check wishlist:', error);
    }
  };

  const handleAddToCart = async () => {
    try {
      await api.post('/cart/add', {
        product_id: productId,
        quantity
      });
      toast.success('Added to cart!');
    } catch (error) {
      console.error('Failed to add to cart:', error);
      toast.error('Failed to add to cart');
    }
  };

  const handleToggleWishlist = async () => {
    try {
      if (isInWishlist) {
        await api.delete(`/wishlist/${productId}`);
        toast.success('Removed from wishlist');
      } else {
        await api.post('/wishlist', { product_id: productId });
        toast.success('Added to wishlist!');
      }
      setIsInWishlist(!isInWishlist);
    } catch (error) {
      console.error('Failed to toggle wishlist:', error);
      toast.error('Failed to update wishlist');
    }
  };

  const handlePreOrder = async (planId?: number) => {
    try {
      await api.post('/preorders', {
        product_id: productId,
        quantity,
        bnpl_plan_id: planId
      });
      toast.success('Pre-order placed successfully!');
      router.push('/orders');
    } catch (error) {
      console.error('Failed to place pre-order:', error);
      toast.error('Failed to place pre-order');
    }
  };

  const handleBNPLSelected = async (planId: number) => {
    handlePreOrder(planId);
  };

  const handleVideoUploadComplete = (videoUrl: string) => {
    toast.success('Video uploaded! It will appear after review.');
    setShowVideoUpload(false);
    fetchReviews();
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-7xl mx-auto px-4 py-8 animate-pulse">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div className="aspect-square bg-gray-200 rounded-lg"></div>
            <div className="space-y-4">
              <div className="h-8 bg-gray-200 rounded w-3/4"></div>
              <div className="h-4 bg-gray-200 rounded w-1/4"></div>
              <div className="h-32 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-7xl mx-auto px-4 py-16 text-center">
          <h2 className="text-2xl font-bold text-gray-900">Product not found</h2>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      
      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
          {/* Image Gallery */}
          <div className="space-y-4">
            <div className="aspect-square relative bg-white rounded-lg overflow-hidden">
              <Image
                src={product.images[selectedImage] || '/placeholder.jpg'}
                alt={product.name}
                fill
                className="object-cover"
              />
            </div>
            <div className="grid grid-cols-4 gap-2">
              {product.images.slice(0, 4).map((image, index) => (
                <button
                  key={index}
                  onClick={() => setSelectedImage(index)}
                  className={`aspect-square relative rounded-lg overflow-hidden ${
                    selectedImage === index ? 'ring-2 ring-primary-600' : ''
                  }`}
                >
                  <Image
                    src={image}
                    alt={`${product.name} ${index + 1}`}
                    fill
                    className="object-cover"
                  />
                </button>
              ))}
            </div>
          </div>

          {/* Product Info */}
          <div className="space-y-6">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 mb-2">
                {product.name}
              </h1>
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-1">
                  {[...Array(5)].map((_, i) => (
                    <StarIcon
                      key={i}
                      className={`w-5 h-5 ${
                        i < Math.floor(product.rating)
                          ? 'text-yellow-400 fill-current'
                          : 'text-gray-300'
                      }`}
                    />
                  ))}
                  <span className="text-gray-600 ml-2">
                    {product.rating.toFixed(1)} ({product.reviews_count} reviews)
                  </span>
                </div>
              </div>
            </div>

            <div className="text-3xl font-bold text-primary-600">
              ${product.price.toFixed(2)}
            </div>

            <p className="text-gray-600 leading-relaxed">
              {product.description}
            </p>

            {product.is_preorder ? (
              <PreOrderButton
                product={product as any}
                onPreOrder={handlePreOrder}
              />
            ) : (
              <div className="space-y-4">
                {product.stock_quantity > 0 ? (
                  <>
                    <div className="flex items-center gap-4">
                      <label className="text-gray-700 font-medium">Quantity:</label>
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => setQuantity(Math.max(1, quantity - 1))}
                          className="w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-100"
                        >
                          -
                        </button>
                        <span className="w-12 text-center font-semibold">{quantity}</span>
                        <button
                          onClick={() => setQuantity(Math.min(product.stock_quantity, quantity + 1))}
                          className="w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-100"
                        >
                          +
                        </button>
                      </div>
                      <span className="text-sm text-gray-500">
                        {product.stock_quantity} available
                      </span>
                    </div>

                    <div className="flex gap-3">
                      <button
                        onClick={handleAddToCart}
                        className="flex-1 btn-primary flex items-center justify-center gap-2"
                      >
                        <ShoppingCartIcon className="w-5 h-5" />
                        Add to Cart
                      </button>
                      <button
                        onClick={handleToggleWishlist}
                        className="btn-secondary p-3"
                      >
                        {isInWishlist ? (
                          <HeartSolidIcon className="w-6 h-6 text-red-500" />
                        ) : (
                          <HeartIcon className="w-6 h-6" />
                        )}
                      </button>
                    </div>

                    <button
                      onClick={() => setShowBNPL(true)}
                      className="w-full btn-secondary"
                    >
                      Pay in Installments with BNPL
                    </button>
                  </>
                ) : (
                  <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <p className="text-red-600 font-semibold">Out of Stock</p>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* BNPL Modal */}
        {showBNPL && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
              <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900">Choose Payment Plan</h2>
                <button onClick={() => setShowBNPL(false)} className="text-gray-500 hover:text-gray-700">
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <div className="p-6">
                <BNPLCheckout
                  orderAmount={product.price * quantity}
                  onPlanSelected={handleBNPLSelected}
                  onCancel={() => setShowBNPL(false)}
                />
              </div>
            </div>
          </div>
        )}

        {/* Reviews Section */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-gray-900">Customer Reviews</h2>
            <button
              onClick={() => setShowVideoUpload(!showVideoUpload)}
              className="btn-primary flex items-center gap-2"
            >
              <PlayCircleIcon className="w-5 h-5" />
              Add Video Review
            </button>
          </div>

          {showVideoUpload && (
            <div className="mb-6 p-6 bg-gray-50 rounded-lg">
              <VideoReviewUpload
                productId={parseInt(productId)}
                onUploadComplete={handleVideoUploadComplete}
              />
            </div>
          )}

          <div className="space-y-6">
            {reviews.map((review) => (
              <div key={review.id} className="border-b border-gray-200 pb-6 last:border-0">
                <div className="flex items-start gap-4">
                  <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                    <span className="font-bold text-primary-600">
                      {review.user.name.charAt(0).toUpperCase()}
                    </span>
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <div>
                        <p className="font-semibold text-gray-900">{review.user.name}</p>
                        <div className="flex items-center gap-2 mt-1">
                          <div className="flex">
                            {[...Array(5)].map((_, i) => (
                              <StarIcon
                                key={i}
                                className={`w-4 h-4 ${
                                  i < review.rating
                                    ? 'text-yellow-400 fill-current'
                                    : 'text-gray-300'
                                }`}
                              />
                            ))}
                          </div>
                          <span className="text-sm text-gray-500">
                            {new Date(review.created_at).toLocaleDateString()}
                          </span>
                        </div>
                      </div>
                      {review.video_url && (
                        <span className="flex items-center gap-1 text-sm text-primary-600">
                          <PlayCircleIcon className="w-4 h-4" />
                          Video Review
                        </span>
                      )}
                    </div>
                    <p className="text-gray-700 mb-3">{review.comment}</p>
                    {review.video_url && (
                      <div className="aspect-video relative bg-black rounded-lg overflow-hidden max-w-md">
                        <video
                          src={review.video_url}
                          controls
                          className="w-full h-full"
                        />
                      </div>
                    )}
                    <div className="flex items-center gap-4 mt-3 text-sm">
                      <button className="text-gray-600 hover:text-gray-900 flex items-center gap-1">
                        <CheckCircleIcon className="w-4 h-4" />
                        Helpful ({review.helpful_count})
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))}
            {reviews.length === 0 && (
              <p className="text-center text-gray-500 py-8">
                No reviews yet. Be the first to review this product!
              </p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

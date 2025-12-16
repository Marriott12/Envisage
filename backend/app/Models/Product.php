<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'title',
        'description',
        'price',
        'stock',
        'primary_image',
        'images',
        'thumbnail',
        'status',
        'condition',
        'brand',
        'weight',
        'dimensions',
        'featured',
        'views',
        'sold',
        'is_preorder',
        'expected_ship_date',
        'preorder_limit',
        'charge_now'
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'featured' => 'boolean',
        'is_preorder' => 'boolean',
        'expected_ship_date' => 'date',
        'preorder_limit' => 'integer',
        'charge_now' => 'boolean'
    ];

    protected $appends = ['primary_image_url', 'thumbnail_url', 'images_urls'];

    /**
     * Get the seller/owner of the product
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get favorites for this product
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get cart items for this product
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get order items for this product
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if product is favorited by user
     */
    public function isFavoritedBy($userId)
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    /**
     * Get pre-orders for this product
     */
    public function preOrders()
    {
        return $this->hasMany(PreOrder::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('stock', '>', 0);
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Get full URL for primary image
     */
    public function getPrimaryImageUrlAttribute()
    {
        return $this->primary_image ? url('storage/' . $this->primary_image) : null;
    }

    /**
     * Get full URL for thumbnail
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? url('storage/' . $this->thumbnail) : null;
    }

    /**
     * Get full URLs for all images
     */
    public function getImagesUrlsAttribute()
    {
        if (!$this->images || !is_array($this->images)) {
            return [];
        }

        return array_map(function ($image) {
            return url('storage/' . $image);
        }, $this->images);
    }
}

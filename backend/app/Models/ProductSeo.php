<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSeo extends Model
{
    use HasFactory;

    protected $table = 'product_seo';

    protected $fillable = [
        'product_id', 'meta_title', 'meta_description', 'meta_keywords',
        'slug', 'canonical_url', 'og_title', 'og_description',
        'og_image', 'twitter_title', 'twitter_description', 'twitter_image'
    ];

    protected $casts = [
        'meta_keywords' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

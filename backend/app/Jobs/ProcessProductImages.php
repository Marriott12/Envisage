<?php

namespace App\Jobs;

use App\Services\CloudinaryService;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProductImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;
    protected $images;

    public function __construct(Product $product, $images)
    {
        $this->product = $product;
        $this->images = $images;
    }

    public function handle(CloudinaryService $cloudinary)
    {
        $uploadedImages = $cloudinary->uploadProductImages(
            $this->images,
            $this->product->id
        );

        // Update product with Cloudinary URLs
        foreach ($uploadedImages as $index => $imageData) {
            \App\Models\ProductImage::create([
                'product_id' => $this->product->id,
                'image_url' => $imageData['url'],
                'cloudinary_public_id' => $imageData['public_id'],
                'thumbnail_url' => $imageData['thumbnail'],
                'order' => $index,
            ]);
        }
    }
}

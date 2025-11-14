<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Upload and process product images
     *
     * @param UploadedFile|array $images
     * @param string $folder
     * @return array|string
     */
    public function uploadProductImages($images, $folder = 'products')
    {
        if (is_array($images)) {
            $uploadedImages = [];
            foreach ($images as $image) {
                $uploadedImages[] = $this->processImage($image, $folder);
            }
            return $uploadedImages;
        }
        
        return $this->processImage($images, $folder);
    }

    /**
     * Process a single image: resize, optimize, and save
     *
     * @param UploadedFile $image
     * @param string $folder
     * @return string Path to saved image
     */
    private function processImage(UploadedFile $image, $folder)
    {
        // Validate image
        $this->validateImage($image);
        
        // Generate unique filename
        $filename = Str::random(40) . '.' . $image->getClientOriginalExtension();
        $path = "{$folder}/{$filename}";
        
        // Process and save original (max 1200x1200)
        $img = Image::make($image);
        $img->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $img->encode($image->getClientOriginalExtension(), 85); // 85% quality
        
        Storage::disk('public')->put($path, $img);
        
        return $path;
    }

    /**
     * Create thumbnail from image
     *
     * @param string $imagePath
     * @param int $width
     * @param int $height
     * @return string Path to thumbnail
     */
    public function createThumbnail($imagePath, $width = 300, $height = 300)
    {
        $fullPath = storage_path('app/public/' . $imagePath);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Image not found: ' . $imagePath);
        }
        
        $img = Image::make($fullPath);
        $img->fit($width, $height);
        
        $thumbnailPath = str_replace(
            basename($imagePath),
            'thumb_' . basename($imagePath),
            $imagePath
        );
        
        Storage::disk('public')->put($thumbnailPath, $img->encode());
        
        return $thumbnailPath;
    }

    /**
     * Delete image from storage
     *
     * @param string|array $paths
     * @return bool
     */
    public function deleteImage($paths)
    {
        if (is_array($paths)) {
            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }
            return true;
        }
        
        return Storage::disk('public')->delete($paths);
    }

    /**
     * Validate uploaded image
     *
     * @param UploadedFile $image
     * @throws \Exception
     */
    private function validateImage(UploadedFile $image)
    {
        // Check file size (max 5MB)
        if ($image->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Image size must not exceed 5MB');
        }
        
        // Check mime type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($image->getMimeType(), $allowedMimes)) {
            throw new \Exception('Image must be JPEG, PNG, or WebP format');
        }
    }

    /**
     * Get full URL for image path
     *
     * @param string $path
     * @return string
     */
    public static function getImageUrl($path)
    {
        if (!$path) {
            return null;
        }
        
        return url('storage/' . $path);
    }
}

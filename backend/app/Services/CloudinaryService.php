<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
        ]);
    }

    /**
     * Upload image to Cloudinary
     */
    public function uploadImage($file, $folder = 'products', $options = [])
    {
        try {
            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ],
            ];

            $uploadOptions = array_merge($defaultOptions, $options);

            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $uploadOptions
            );

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'resource_type' => $result['resource_type'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple($files, $folder = 'products', $options = [])
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->uploadImage($file, $folder, $options);
        }

        return $results;
    }

    /**
     * Delete image from Cloudinary
     */
    public function deleteImage($publicId)
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);

            return [
                'success' => $result['result'] === 'ok',
                'result' => $result['result'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get optimized image URL with transformations
     */
    public function getOptimizedUrl($publicId, $width = null, $height = null, $crop = 'fill')
    {
        $transformation = [
            'quality' => 'auto',
            'fetch_format' => 'auto',
        ];

        if ($width) {
            $transformation['width'] = $width;
        }

        if ($height) {
            $transformation['height'] = $height;
        }

        if ($width || $height) {
            $transformation['crop'] = $crop;
        }

        return $this->cloudinary->image($publicId)
            ->resize($transformation)
            ->toUrl();
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnail($publicId, $size = 200)
    {
        return $this->getOptimizedUrl($publicId, $size, $size, 'thumb');
    }

    /**
     * Generate responsive image URLs
     */
    public function getResponsiveUrls($publicId, $sizes = [300, 600, 900, 1200])
    {
        $urls = [];

        foreach ($sizes as $size) {
            $urls[$size] = $this->getOptimizedUrl($publicId, $size, null, 'scale');
        }

        return $urls;
    }

    /**
     * Batch upload with transformations
     */
    public function uploadProductImages($files, $productId)
    {
        $folder = "products/{$productId}";
        $results = [];

        foreach ($files as $index => $file) {
            $options = [
                'public_id' => "product_{$productId}_{$index}_" . time(),
                'transformation' => [
                    ['width' => 1200, 'height' => 1200, 'crop' => 'limit'],
                    ['quality' => 'auto:good'],
                    ['fetch_format' => 'auto'],
                ],
                'eager' => [
                    ['width' => 300, 'height' => 300, 'crop' => 'thumb'],
                    ['width' => 600, 'height' => 600, 'crop' => 'fill'],
                ],
            ];

            $result = $this->uploadImage($file, $folder, $options);
            
            if ($result['success']) {
                $results[] = [
                    'url' => $result['url'],
                    'public_id' => $result['public_id'],
                    'thumbnail' => $this->getThumbnail($result['public_id']),
                ];
            }
        }

        return $results;
    }
}

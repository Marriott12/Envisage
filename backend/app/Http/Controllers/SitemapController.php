<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Generate XML sitemap
     */
    public function generate()
    {
        $baseUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        // Static pages
        $staticPages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/marketplace', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/about', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/blog', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        // Get all active products
        $products = Product::where('status', 'active')
            ->select('id', 'updated_at')
            ->get();

        // Get all categories
        $categories = Category::select('id', 'updated_at')->get();

        // Get all blog posts
        $blogPosts = BlogPost::where('status', 'published')
            ->select('id', 'slug', 'updated_at')
            ->get();

        // Build XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Add static pages
        foreach ($staticPages as $page) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . $page['url'] . '</loc>';
            $xml .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $page['priority'] . '</priority>';
            $xml .= '</url>';
        }

        // Add products
        foreach ($products as $product) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/marketplace/' . $product->id . '</loc>';
            $xml .= '<lastmod>' . $product->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        // Add categories
        foreach ($categories as $category) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/marketplace?category=' . $category->id . '</loc>';
            $xml .= '<lastmod>' . $category->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        // Add blog posts
        foreach ($blogPosts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/blog/' . ($post->slug ?? $post->id) . '</loc>';
            $xml .= '<lastmod>' . $post->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}

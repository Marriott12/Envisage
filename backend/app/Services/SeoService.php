<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Product;
use Illuminate\Support\Facades\URL;

class SeoService
{
    /**
     * Generate meta tags for a page
     */
    public static function getMeta($type = 'home', $data = null)
    {
        $siteName = Setting::get('site_name', 'Envisage Marketplace');
        $siteDescription = Setting::get('site_description', 'Buy and sell quality products online');
        $siteKeywords = Setting::get('site_keywords', 'marketplace, ecommerce, shop');
        $defaultImage = Setting::get('meta_image', URL::to('/images/default-og.jpg'));

        $meta = [
            'title' => $siteName,
            'description' => $siteDescription,
            'keywords' => $siteKeywords,
            'image' => $defaultImage,
            'url' => URL::current(),
            'type' => 'website'
        ];

        switch ($type) {
            case 'product':
                if ($data) {
                    $meta['title'] = $data->title . ' - ' . $siteName;
                    $meta['description'] = substr(strip_tags($data->description), 0, 160);
                    $meta['image'] = $data->primary_image ? URL::to('storage/' . $data->primary_image) : $defaultImage;
                    $meta['type'] = 'product';
                    $meta['product:price'] = $data->price;
                    $meta['product:currency'] = Setting::get('payment_currency', 'USD');
                }
                break;

            case 'category':
                if ($data) {
                    $meta['title'] = $data->name . ' - ' . $siteName;
                    $meta['description'] = $data->description ?? $siteDescription;
                }
                break;

            case 'blog':
                if ($data) {
                    $meta['title'] = $data->title . ' - ' . $siteName;
                    $meta['description'] = substr(strip_tags($data->content), 0, 160);
                    $meta['image'] = $data->image ? URL::to('storage/' . $data->image) : $defaultImage;
                    $meta['type'] = 'article';
                }
                break;
        }

        return $meta;
    }

    /**
     * Generate sitemap XML
     */
    public static function generateSitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= '<url>';
        $xml .= '<loc>' . URL::to('/') . '</loc>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';

        // Products
        $products = Product::where('status', 'active')->get();
        foreach ($products as $product) {
            $xml .= '<url>';
            $xml .= '<loc>' . URL::to('/products/' . $product->id) . '</loc>';
            $xml .= '<lastmod>' . $product->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate robots.txt content
     */
    public static function getRobotsTxt()
    {
        $frontendUrl = Setting::get('frontend_url', config('app.url'));
        
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /cart/\n";
        $robots .= "Disallow: /checkout/\n";
        $robots .= "\n";
        $robots .= "Sitemap: {$frontendUrl}/sitemap.xml\n";

        return $robots;
    }

    /**
     * Generate structured data (JSON-LD)
     */
    public static function getStructuredData($type = 'website', $data = null)
    {
        $siteName = Setting::get('site_name', 'Envisage Marketplace');
        $siteUrl = Setting::get('frontend_url', config('app.url'));

        switch ($type) {
            case 'product':
                if ($data) {
                    return [
                        '@context' => 'https://schema.org/',
                        '@type' => 'Product',
                        'name' => $data->title,
                        'description' => strip_tags($data->description),
                        'image' => URL::to('storage/' . $data->primary_image),
                        'brand' => [
                            '@type' => 'Brand',
                            'name' => $data->brand ?? $siteName
                        ],
                        'offers' => [
                            '@type' => 'Offer',
                            'url' => URL::to('/products/' . $data->id),
                            'priceCurrency' => Setting::get('payment_currency', 'USD'),
                            'price' => $data->price,
                            'availability' => $data->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                        ]
                    ];
                }
                break;

            case 'website':
            default:
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'url' => $siteUrl,
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => $siteUrl . '/search?q={search_term_string}',
                        'query-input' => 'required name=search_term_string'
                    ]
                ];
        }

        return [];
    }
}

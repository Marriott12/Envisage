<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    /**
     * Get meta tags for a specific page
     */
    public function meta(Request $request)
    {
        $type = $request->get('type', 'home');
        $id = $request->get('id');
        $data = null;

        // Fetch data based on type and id if needed
        if ($id) {
            switch ($type) {
                case 'product':
                    $data = \App\Models\Product::find($id);
                    break;
                case 'category':
                    $data = \App\Models\Category::find($id);
                    break;
            }
        }

        $meta = SeoService::getMeta($type, $data);
        
        return response()->json($meta);
    }

    /**
     * Generate sitemap.xml
     */
    public function sitemap()
    {
        $xml = SeoService::generateSitemap();
        
        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate robots.txt
     */
    public function robots()
    {
        $robots = SeoService::getRobotsTxt();
        
        return response($robots, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Get structured data
     */
    public function structuredData(Request $request)
    {
        $type = $request->get('type', 'website');
        $id = $request->get('id');
        $data = null;

        if ($id && $type === 'product') {
            $data = \App\Models\Product::find($id);
        }

        $structuredData = SeoService::getStructuredData($type, $data);
        
        return response()->json($structuredData);
    }
}


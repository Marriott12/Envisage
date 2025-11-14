<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index(Request $request)
    {
        $query = Category::where('is_active', true)
            ->withCount('products');
        
        // Include parent categories hierarchy
        if ($request->has('with_parents')) {
            $query->with('parent');
        }
        
        // Include child categories
        if ($request->has('with_children')) {
            $query->with('children');
        }
        
        // Only root categories (no parent)
        if ($request->has('root_only')) {
            $query->whereNull('parent_id');
        }
        
        $categories = $query->orderBy('name')->get();
        
        return response()->json($categories);
    }
    
    /**
     * Get single category with products
     */
    public function show($id)
    {
        $category = Category::with([
            'products' => function($query) {
                $query->where('status', 'active')
                    ->with('seller:id,name')
                    ->latest()
                    ->take(20);
            },
            'children.products' // Include products from subcategories
        ])->findOrFail($id);
        
        return response()->json($category);
    }
}

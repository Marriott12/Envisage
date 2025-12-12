<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrequentlyBoughtTogether extends Model
{
    use HasFactory;

    protected $table = 'frequently_bought_together';

    protected $fillable = [
        'product_id',
        'bought_with_product_id',
        'co_occurrence_count',
        'confidence',
        'lift',
        'last_calculated_at',
    ];

    protected $casts = [
        'co_occurrence_count' => 'integer',
        'confidence' => 'decimal:4',
        'lift' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function boughtWithProduct()
    {
        return $this->belongsTo(Product::class, 'bought_with_product_id');
    }

    // Helper methods
    public static function getFrequentlyBoughtWith($productId, $limit = 5)
    {
        $fbtIds = self::where('product_id', $productId)
            ->where('confidence', '>=', 0.1) // Minimum 10% confidence
            ->orderByDesc('confidence')
            ->limit($limit)
            ->pluck('bought_with_product_id');

        return Product::whereIn('id', $fbtIds)->get();
    }

    public static function calculateAssociations()
    {
        // Get all products
        $products = \DB::table('products')->pluck('id');

        foreach ($products as $productA) {
            // Count orders containing product A
            $ordersWithA = \DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productA)
                ->where('orders.status', 'completed')
                ->distinct('orders.id')
                ->count('orders.id');

            if ($ordersWithA < 2) continue; // Need at least 2 orders

            // Find products bought with A
            $coProducts = \DB::table('order_items as oi1')
                ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
                ->join('orders', 'oi1.order_id', '=', 'orders.id')
                ->where('oi1.product_id', $productA)
                ->where('oi2.product_id', '!=', $productA)
                ->where('orders.status', 'completed')
                ->selectRaw('oi2.product_id, COUNT(DISTINCT oi1.order_id) as co_count')
                ->groupBy('oi2.product_id')
                ->having('co_count', '>=', 2) // At least 2 co-occurrences
                ->get();

            foreach ($coProducts as $coProduct) {
                // Calculate confidence: P(B|A) = orders(A and B) / orders(A)
                $confidence = $coProduct->co_count / $ordersWithA;

                // Calculate lift: confidence / P(B)
                $ordersWithB = \DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('order_items.product_id', $coProduct->product_id)
                    ->where('orders.status', 'completed')
                    ->distinct('orders.id')
                    ->count('orders.id');

                $totalOrders = \DB::table('orders')->where('status', 'completed')->count();
                $probB = $totalOrders > 0 ? $ordersWithB / $totalOrders : 0;
                $lift = $probB > 0 ? $confidence / $probB : 0;

                self::updateOrCreate(
                    [
                        'product_id' => $productA,
                        'bought_with_product_id' => $coProduct->product_id,
                    ],
                    [
                        'co_occurrence_count' => $coProduct->co_count,
                        'confidence' => round($confidence, 4),
                        'lift' => round($lift, 2),
                        'last_calculated_at' => now(),
                    ]
                );
            }
        }
    }
}

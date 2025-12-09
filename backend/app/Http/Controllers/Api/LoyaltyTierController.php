<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyTierController extends Controller
{
    /**
     * Get all loyalty tiers
     */
    public function index()
    {
        // Mock tier data - in production, use actual loyalty_tiers table
        $tiers = [
            [
                'id' => 1,
                'name' => 'Bronze',
                'slug' => 'bronze',
                'min_points' => 0,
                'max_points' => 4999,
                'benefits' => ['Earn 1 point per $1 spent', 'Birthday bonus points', 'Exclusive member-only sales'],
                'point_multiplier' => 1.0,
                'discount_percentage' => 0,
                'color' => '#CD7F32',
                'icon' => '🥉',
                'welcome_bonus' => 100,
                'birthday_bonus' => 50,
                'referral_bonus' => 200,
            ],
            [
                'id' => 2,
                'name' => 'Silver',
                'slug' => 'silver',
                'min_points' => 5000,
                'max_points' => 9999,
                'benefits' => ['Earn 1.2x points per $1 spent', '5% discount on all purchases', 'Free shipping on orders $50+', 'Priority customer support'],
                'point_multiplier' => 1.2,
                'discount_percentage' => 5,
                'color' => '#C0C0C0',
                'icon' => '🥈',
                'welcome_bonus' => 0,
                'birthday_bonus' => 100,
                'referral_bonus' => 300,
            ],
            [
                'id' => 3,
                'name' => 'Gold',
                'slug' => 'gold',
                'min_points' => 10000,
                'max_points' => 24999,
                'benefits' => ['Earn 1.5x points per $1 spent', '10% discount on all purchases', 'Free shipping on all orders', 'Dedicated account manager'],
                'point_multiplier' => 1.5,
                'discount_percentage' => 10,
                'color' => '#FFD700',
                'icon' => '🥇',
                'welcome_bonus' => 0,
                'birthday_bonus' => 200,
                'referral_bonus' => 500,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $tiers,
        ]);
    }

    /**
     * Create a new tier
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'min_points' => 'required|integer|min:0',
            'max_points' => 'nullable|integer|min:0',
            'benefits' => 'nullable|array',
            'point_multiplier' => 'required|numeric|min:0',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'color' => 'required|string',
            'icon' => 'required|string|max:10',
        ]);

        // In production, save to database
        return response()->json([
            'success' => true,
            'message' => 'Tier created successfully',
            'data' => $request->all(),
        ]);
    }

    /**
     * Update a tier
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'min_points' => 'sometimes|integer|min:0',
            'max_points' => 'nullable|integer|min:0',
            'benefits' => 'nullable|array',
            'point_multiplier' => 'sometimes|numeric|min:0',
            'discount_percentage' => 'sometimes|integer|min:0|max:100',
        ]);

        // In production, update in database
        return response()->json([
            'success' => true,
            'message' => 'Tier updated successfully',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    /**
     * Delete a tier
     */
    public function destroy($id)
    {
        // In production, delete from database
        return response()->json([
            'success' => true,
            'message' => 'Tier deleted successfully',
        ]);
    }

    /**
     * Get loyalty tier statistics
     */
    public function stats()
    {
        // Mock stats data
        return response()->json([
            'success' => true,
            'data' => [
                'bronze_members' => 1234,
                'silver_members' => 567,
                'gold_members' => 234,
                'platinum_members' => 89,
                'diamond_members' => 23,
            ],
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\User;
use App\Models\AutomationRule;
use App\Models\AutomationExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckAbandonedCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Get carts abandoned more than 1 hour ago
            $abandonedCarts = DB::table('carts')
                ->join('users', 'carts.user_id', '=', 'users.id')
                ->where('carts.updated_at', '<=', now()->subHour())
                ->whereNotNull('carts.user_id')
                ->select('carts.*', 'users.email', 'users.name')
                ->get();

            foreach ($abandonedCarts as $cart) {
                // Check if already tracked
                $existingAbandonedCart = AbandonedCart::where('user_id', $cart->user_id)
                    ->where('recovered', false)
                    ->first();

                if (!$existingAbandonedCart) {
                    // Create abandoned cart record
                    $cartItems = DB::table('cart_items')
                        ->where('cart_id', $cart->id)
                        ->get();

                    $total = $cartItems->sum(fn($item) => $item->price * $item->quantity);

                    $abandonedCart = AbandonedCart::create([
                        'user_id' => $cart->user_id,
                        'cart_data' => $cartItems->toArray(),
                        'total_amount' => $total,
                        'abandoned_at' => $cart->updated_at,
                    ]);

                    // Trigger automation rule
                    $this->triggerAbandonedCartAutomation($cart->user_id, $abandonedCart);

                    Log::info("Abandoned cart tracked", [
                        'user_id' => $cart->user_id,
                        'cart_id' => $cart->id,
                        'total' => $total,
                    ]);
                }
            }

            Log::info("Abandoned carts check completed", [
                'processed' => $abandonedCarts->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to check abandoned carts", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Trigger automation rule for abandoned cart
     */
    protected function triggerAbandonedCartAutomation($userId, $abandonedCart)
    {
        // Find active automation rule for cart abandonment
        $rule = AutomationRule::where('trigger', 'cart_abandoned')
            ->where('is_active', true)
            ->first();

        if ($rule) {
            // Create automation execution
            AutomationExecution::create([
                'rule_id' => $rule->id,
                'user_id' => $userId,
                'status' => 'pending',
                'data' => [
                    'abandoned_cart_id' => $abandonedCart->id,
                    'cart_data' => $abandonedCart->cart_data,
                    'total_amount' => $abandonedCart->total_amount,
                ],
                'scheduled_at' => now()->addMinutes($rule->delay_minutes),
            ]);

            Log::info("Abandoned cart automation triggered", [
                'rule_id' => $rule->id,
                'user_id' => $userId,
                'cart_id' => $abandonedCart->id,
            ]);
        }
    }
}

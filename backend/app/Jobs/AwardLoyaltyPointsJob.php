<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\UserLoyaltyPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AwardLoyaltyPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        $userId = $this->order->buyer_id;
        
        $loyaltyPoints = UserLoyaltyPoint::firstOrCreate(
            ['user_id' => $userId],
            ['total_points' => 0, 'lifetime_points' => 0, 'tier' => 'bronze']
        );

        // Award 1 point per dollar spent
        $points = floor($this->order->total_amount);

        $loyaltyPoints->addPoints(
            $points,
            'purchase',
            'Purchase order #' . $this->order->order_number,
            $this->order->id
        );

        // TODO: Send loyalty points earned email
    }
}

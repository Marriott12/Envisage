<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_order_updates',
        'email_promotions',
        'email_price_alerts',
        'email_new_messages',
        'push_order_updates',
        'push_promotions',
        'push_price_alerts',
        'push_new_messages',
        'sms_order_updates',
    ];

    protected $casts = [
        'email_order_updates' => 'boolean',
        'email_promotions' => 'boolean',
        'email_price_alerts' => 'boolean',
        'email_new_messages' => 'boolean',
        'push_order_updates' => 'boolean',
        'push_promotions' => 'boolean',
        'push_price_alerts' => 'boolean',
        'push_new_messages' => 'boolean',
        'sms_order_updates' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

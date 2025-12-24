<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'intent',
        'message_count',
        'resolved',
        'resolution_time_seconds',
        'satisfaction_score',
        'escalated_to_human',
        'tokens_used',
        'cost_usd',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'escalated_to_human' => 'boolean',
        'satisfaction_score' => 'decimal:2',
        'cost_usd' => 'decimal:4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByIntent($query, $intent)
    {
        return $query->where('intent', $intent);
    }

    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalated_to_human', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'user_id',
        'status',
        'data',
        'scheduled_at',
        'executed_at',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsExecuted()
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }

    public function markAsFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'executed_at' => now(),
            'error_message' => $error,
        ]);
    }
}

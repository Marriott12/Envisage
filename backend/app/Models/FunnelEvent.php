<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FunnelEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'funnel_id',
        'user_id',
        'session_id',
        'step_index',
        'step_name',
        'completed',
        'entered_at',
        'completed_at',
        'time_to_complete',
        'dropped_off',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'dropped_off' => 'boolean',
        'entered_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_to_complete' => 'integer',
    ];

    // Relationships
    public function funnel()
    {
        return $this->belongsTo(ConversionFunnel::class, 'funnel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeDroppedOff($query)
    {
        return $query->where('dropped_off', true);
    }

    public function scopeByFunnel($query, $funnelId)
    {
        return $query->where('funnel_id', $funnelId);
    }

    public function scopeByStep($query, $stepIndex)
    {
        return $query->where('step_index', $stepIndex);
    }

    // Helper Methods
    public function markAsCompleted()
    {
        $this->update([
            'completed' => true,
            'completed_at' => now(),
            'time_to_complete' => now()->diffInSeconds($this->entered_at),
        ]);
    }

    public function markAsDroppedOff()
    {
        $this->update(['dropped_off' => true]);
    }
}

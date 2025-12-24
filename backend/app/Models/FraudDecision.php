<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'fraud_alert_id',
        'reviewed_by',
        'decision',
        'notes',
        'was_correct',
    ];

    protected $casts = [
        'was_correct' => 'boolean',
    ];

    public function fraudAlert()
    {
        return $this->belongsTo(FraudAlert::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeByDecision($query, $decision)
    {
        return $query->where('decision', $decision);
    }

    public function scopeCorrect($query)
    {
        return $query->where('was_correct', true);
    }
}

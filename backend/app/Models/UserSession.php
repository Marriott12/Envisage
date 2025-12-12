<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'page_views',
        'events_count',
        'entry_page',
        'exit_page',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device_type',
        'browser',
        'os',
        'country',
        'converted',
        'revenue',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'converted' => 'boolean',
        'revenue' => 'decimal:2',
        'duration_seconds' => 'integer',
        'page_views' => 'integer',
        'events_count' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(AnalyticEvent::class, 'session_id', 'session_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeEnded($query)
    {
        return $query->whereNotNull('ended_at');
    }

    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    public function scopeByDevice($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    // Helper Methods
    public function endSession()
    {
        if (!$this->ended_at) {
            $this->update([
                'ended_at' => now(),
                'duration_seconds' => now()->diffInSeconds($this->started_at),
            ]);
        }
    }

    public function incrementPageViews()
    {
        $this->increment('page_views');
    }

    public function incrementEvents()
    {
        $this->increment('events_count');
    }

    public function markAsConverted($revenue = 0)
    {
        $this->update([
            'converted' => true,
            'revenue' => $revenue,
        ]);
    }

    public static function startSession($data)
    {
        return self::create(array_merge([
            'started_at' => now(),
            'page_views' => 1,
            'events_count' => 0,
        ], $data));
    }

    // Computed Attributes
    public function getConversionRateAttribute()
    {
        return $this->converted ? 100 : 0;
    }

    public function getDurationMinutesAttribute()
    {
        return round($this->duration_seconds / 60, 2);
    }
}

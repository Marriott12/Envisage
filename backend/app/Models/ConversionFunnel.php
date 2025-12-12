<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionFunnel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'steps',
        'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function events()
    {
        return $this->hasMany(FunnelEvent::class, 'funnel_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper Methods
    public function getStepByIndex($index)
    {
        return $this->steps[$index] ?? null;
    }

    public function getTotalSteps()
    {
        return count($this->steps);
    }

    public function getConversionRate()
    {
        $firstStepCount = $this->events()
            ->where('step_index', 0)
            ->distinct('session_id')
            ->count('session_id');

        if ($firstStepCount === 0) {
            return 0;
        }

        $lastStepCount = $this->events()
            ->where('step_index', $this->getTotalSteps() - 1)
            ->where('completed', true)
            ->distinct('session_id')
            ->count('session_id');

        return round(($lastStepCount / $firstStepCount) * 100, 2);
    }

    public function getDropOffRates()
    {
        $dropOffRates = [];
        $totalSteps = $this->getTotalSteps();

        for ($i = 0; $i < $totalSteps; $i++) {
            $stepEntries = $this->events()
                ->where('step_index', $i)
                ->distinct('session_id')
                ->count('session_id');

            $dropOffs = $this->events()
                ->where('step_index', $i)
                ->where('dropped_off', true)
                ->distinct('session_id')
                ->count('session_id');

            $dropOffRates[$this->steps[$i]] = [
                'entries' => $stepEntries,
                'drop_offs' => $dropOffs,
                'drop_off_rate' => $stepEntries > 0 ? round(($dropOffs / $stepEntries) * 100, 2) : 0,
            ];
        }

        return $dropOffRates;
    }

    public function getAverageTimePerStep()
    {
        $averageTimes = [];
        $totalSteps = $this->getTotalSteps();

        for ($i = 0; $i < $totalSteps; $i++) {
            $avgTime = $this->events()
                ->where('step_index', $i)
                ->where('completed', true)
                ->avg('time_to_complete');

            $averageTimes[$this->steps[$i]] = round($avgTime ?? 0, 2);
        }

        return $averageTimes;
    }
}

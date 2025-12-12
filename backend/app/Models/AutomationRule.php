<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'trigger',
        'conditions',
        'actions',
        'delay_minutes',
        'is_active',
        'executions_count',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'delay_minutes' => 'integer',
        'executions_count' => 'integer',
    ];

    public function executions()
    {
        return $this->hasMany(AutomationExecution::class, 'rule_id');
    }

    public function incrementExecutions()
    {
        $this->increment('executions_count');
    }
}

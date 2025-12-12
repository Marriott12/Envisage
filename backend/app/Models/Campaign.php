<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'template_id',
        'status',
        'target_audience',
        'scheduled_at',
        'sent_at',
        'total_sent',
        'opened',
        'clicked',
        'converted',
        'conversion_rate',
    ];

    protected $casts = [
        'target_audience' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_sent' => 'integer',
        'opened' => 'integer',
        'clicked' => 'integer',
        'converted' => 'integer',
        'conversion_rate' => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function logs()
    {
        return $this->hasMany(CampaignLog::class);
    }

    public function getOpenRateAttribute()
    {
        return $this->total_sent > 0 ? ($this->opened / $this->total_sent) * 100 : 0;
    }

    public function getClickRateAttribute()
    {
        return $this->total_sent > 0 ? ($this->clicked / $this->total_sent) * 100 : 0;
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled' && $this->scheduled_at && $this->scheduled_at > now();
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}

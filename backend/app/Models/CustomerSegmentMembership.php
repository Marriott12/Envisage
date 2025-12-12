<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSegmentMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'segment_id',
        'segment_data',
        'joined_at'
    ];

    protected $casts = [
        'segment_data' => 'array',
        'joined_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function segment()
    {
        return $this->belongsTo(CustomerSegment::class);
    }
}

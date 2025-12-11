<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'format',
        'file_path',
        'file_size',
        'generated_by',
        'filters',
        'data',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'filters' => 'array',
        'data' => 'array',
        'download_count' => 'integer',
        'file_size' => 'integer',
        'last_downloaded_at' => 'datetime',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

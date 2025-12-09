<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'filename', 'total_rows', 'processed_rows',
        'successful_rows', 'failed_rows', 'status', 'error_log',
        'started_at', 'completed_at'
    ];

    protected $casts = [
        'error_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsProcessing()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($error)
    {
        $errors = $this->error_log ?? [];
        $errors[] = $error;
        
        $this->update([
            'status' => 'failed',
            'error_log' => $errors,
            'completed_at' => now(),
        ]);
    }

    public function addError($row, $error)
    {
        $errors = $this->error_log ?? [];
        $errors[] = [
            'row' => $row,
            'error' => $error,
        ];
        
        $this->update(['error_log' => $errors]);
    }
}

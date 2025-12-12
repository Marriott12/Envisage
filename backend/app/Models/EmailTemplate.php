<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'type',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }

    public function render($variables = [])
    {
        $body = $this->body;
        $subject = $this->subject;

        foreach ($variables as $key => $value) {
            $body = str_replace("{{" . $key . "}}", $value, $body);
            $subject = str_replace("{{" . $key . "}}", $value, $subject);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}

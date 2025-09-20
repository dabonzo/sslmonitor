<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeCheck extends Model
{
    /** @use HasFactory<\Database\Factories\UptimeCheckFactory> */
    use HasFactory;

    protected $fillable = [
        'website_id',
        'status',
        'http_status_code',
        'response_time_ms',
        'response_size_bytes',
        'content_check_passed',
        'content_check_error',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'content_check_passed' => 'boolean',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}

<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtWebhookDelivery extends Model
{
    protected $table = 'socialprofile_court_webhook_deliveries';

    protected $fillable = [
        'webhook_id',
        'case_id',
        'event',
        'status',
        'attempts',
        'response_code',
        'response_body',
        'error',
        'last_attempt_at',
        'next_attempt_at',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(CourtWebhook::class, 'webhook_id');
    }

    public function courtCase(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }
}

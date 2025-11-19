<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtWebhook extends Model
{
    protected $table = 'socialprofile_court_webhooks';

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(CourtWebhookDelivery::class, 'webhook_id');
    }
}

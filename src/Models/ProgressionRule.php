<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\ProgressionRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressionRule extends Model
{
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal_event';
    public const SOURCE_EXTERNAL = 'external_api';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SCHEDULED = 'scheduled';

    protected $table = 'socialprofile_rating_rules';

    protected $fillable = [
        'rating_id',
        'name',
        'trigger_key',
        'source_type',
        'conditions',
        'options',
        'delta',
        'is_active',
        'cooldown_seconds',
    ];

    protected $casts = [
        'conditions' => 'array',
        'options' => 'array',
        'delta' => 'integer',
        'is_active' => 'boolean',
        'cooldown_seconds' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(ProgressionRating::class, 'rating_id');
    }

    protected static function newFactory(): ProgressionRuleFactory
    {
        return ProgressionRuleFactory::new();
    }
}

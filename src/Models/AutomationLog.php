<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    protected $table = 'socialprofile_automation_logs';

    protected $fillable = [
        'rule_id',
        'trigger_type',
        'status',
        'payload',
        'actions',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'actions' => 'array',
    ];

    /**
     * @return BelongsTo<AutomationRule, self>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }
}

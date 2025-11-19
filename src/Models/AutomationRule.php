<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\AutomationRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use HasFactory;

    public const TRIGGER_ROLE_CHANGED = 'role_changed';
    public const TRIGGER_TRUST_LEVEL_CHANGED = 'trust_level_changed';
    public const TRIGGER_ACTIVITY_CHANGED = 'activity_changed';
    public const TRIGGER_COINS_CHANGED = 'coins_changed';
    public const TRIGGER_SOCIAL_STATS_UPDATED = 'social_stats_updated';
    public const TRIGGER_VIOLATION_ADDED = 'violation_added';
    public const TRIGGER_COURT_DECISION_CHANGED = 'court_decision_changed';
    public const TRIGGER_MONTHLY_TOP = 'monthly_top';

    protected $table = 'socialprofile_automation_rules';

    protected $fillable = [
        'name',
        'trigger_type',
        'enabled',
        'priority',
        'conditions',
        'actions',
        'description',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * @return Builder<Model>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * @return Builder<Model>
     */
    public function scopeTrigger(Builder $query, string $trigger): Builder
    {
        return $query->where('trigger_type', $trigger);
    }

    /**
     * @return HasMany<AutomationLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'rule_id');
    }

    protected static function newFactory(): AutomationRuleFactory
    {
        return AutomationRuleFactory::new();
    }
}

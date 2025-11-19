<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\ProgressionThresholdActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressionThresholdAction extends Model
{
    use HasFactory;

    public const ACTION_ROLE_GRANT = 'azuriom_role_grant';
    public const ACTION_ROLE_REVOKE = 'azuriom_role_revoke';
    public const ACTION_PERMISSION_GRANT = 'azuriom_permission_grant';
    public const ACTION_PERMISSION_REVOKE = 'azuriom_permission_revoke';
    public const ACTION_FEATURE_ENABLE = 'plugin_feature_enable';
    public const ACTION_FEATURE_DISABLE = 'plugin_feature_disable';
    public const ACTION_EXTERNAL_WEBHOOK = 'external_webhook';
    public const ACTION_AUTOMATION_RCON = 'automation_rcon';
    public const ACTION_AUTOMATION_BOT = 'automation_bot';

    protected $table = 'socialprofile_rating_threshold_actions';

    protected $fillable = [
        'threshold_id',
        'action',
        'config',
        'auto_revert',
    ];

    protected $casts = [
        'config' => 'array',
        'auto_revert' => 'boolean',
    ];

    public function threshold(): BelongsTo
    {
        return $this->belongsTo(ProgressionThreshold::class, 'threshold_id');
    }

    protected static function newFactory(): ProgressionThresholdActionFactory
    {
        return ProgressionThresholdActionFactory::new();
    }
}

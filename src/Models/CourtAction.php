<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtAction extends Model
{
    public const TYPE_BAN = 'ban';
    public const TYPE_MUTE = 'mute';
    public const TYPE_METRIC = 'metric';
    public const TYPE_ROLE = 'role';
    public const TYPE_NOTE = 'note';

    protected $table = 'socialprofile_court_actions';

    protected $fillable = [
        'case_id',
        'type',
        'metric_key',
        'delta',
        'currency',
        'role_id',
        'duration_minutes',
        'allow_zero_cancel',
        'status',
        'executed_at',
        'expires_at',
        'reverted_at',
        'meta',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'expires_at' => 'datetime',
        'reverted_at' => 'datetime',
        'meta' => 'array',
        'allow_zero_cancel' => 'boolean',
    ];

    public function courtCase(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function shouldScheduleRevert(): bool
    {
        return in_array($this->type, [self::TYPE_BAN, self::TYPE_MUTE, self::TYPE_ROLE], true)
            && $this->duration_minutes !== null
            && $this->duration_minutes > 0;
    }

    public function expiresAt(): ?CarbonInterface
    {
        return $this->expires_at;
    }
}

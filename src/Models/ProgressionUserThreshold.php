<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Database\Factories\ProgressionUserThresholdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressionUserThreshold extends Model
{
    use HasFactory;

    protected $table = 'socialprofile_rating_user_thresholds';

    protected $fillable = [
        'threshold_id',
        'user_id',
        'direction',
        'action_state',
        'reached_at',
        'reverted_at',
        'context',
    ];

    protected $casts = [
        'reached_at' => 'datetime',
        'reverted_at' => 'datetime',
        'context' => 'array',
    ];

    public function threshold(): BelongsTo
    {
        return $this->belongsTo(ProgressionThreshold::class, 'threshold_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): ProgressionUserThresholdFactory
    {
        return ProgressionUserThresholdFactory::new();
    }
}

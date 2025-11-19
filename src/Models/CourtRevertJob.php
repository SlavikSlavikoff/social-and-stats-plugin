<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtRevertJob extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'socialprofile_court_revert_jobs';

    protected $fillable = [
        'action_id',
        'run_at',
        'status',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'run_at' => 'datetime',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(CourtAction::class, 'action_id');
    }
}

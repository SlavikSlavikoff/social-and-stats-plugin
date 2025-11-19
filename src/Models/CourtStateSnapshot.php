<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtStateSnapshot extends Model
{
    protected $table = 'socialprofile_court_state_snapshots';

    protected $fillable = [
        'action_id',
        'user_id',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(CourtAction::class, 'action_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

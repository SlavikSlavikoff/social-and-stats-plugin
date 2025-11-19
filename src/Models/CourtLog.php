<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtLog extends Model
{
    protected $table = 'socialprofile_court_logs';

    protected $fillable = [
        'case_id',
        'event',
        'channel',
        'actor_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function courtCase(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

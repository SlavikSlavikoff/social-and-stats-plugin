<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Database\Factories\ProgressionEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressionEvent extends Model
{
    use HasFactory;

    protected $table = 'socialprofile_rating_events';

    protected $fillable = [
        'rating_id',
        'user_id',
        'rule_id',
        'source',
        'amount',
        'value_before',
        'value_after',
        'payload',
        'triggered_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'value_before' => 'integer',
        'value_after' => 'integer',
        'payload' => 'array',
        'triggered_at' => 'datetime',
    ];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(ProgressionRating::class, 'rating_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ProgressionRule::class, 'rule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): ProgressionEventFactory
    {
        return ProgressionEventFactory::new();
    }
}

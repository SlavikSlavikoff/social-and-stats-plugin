<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;

class GameStatistic extends Model
{
    protected $table = 'socialprofile_game_statistics';

    protected $fillable = [
        'user_id',
        'played_minutes',
        'kills',
        'deaths',
        'extra_metrics',
    ];

    protected $casts = [
        'played_minutes' => 'integer',
        'kills' => 'integer',
        'deaths' => 'integer',
        'extra_metrics' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

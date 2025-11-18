<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityPoint extends Model
{
    protected $table = 'socialprofile_activity_points';

    protected $fillable = [
        'user_id',
        'points',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace Azuriom\Plugin\SocialProfile\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;

class SocialScore extends Model
{
    protected $table = 'socialprofile_social_scores';

    protected $fillable = [
        'user_id',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

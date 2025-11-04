<?php

namespace Azuriom\Plugin\SocialProfile\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Verification extends Model
{
    use SoftDeletes;

    protected $table = 'socialprofile_verifications';

    protected $fillable = [
        'user_id',
        'status',
        'method',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

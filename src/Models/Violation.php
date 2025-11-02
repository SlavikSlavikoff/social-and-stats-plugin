<?php

namespace Azuriom\Plugin\SocialProfile\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Violation extends Model
{
    use SoftDeletes;

    protected $table = 'socialprofile_violations';

    protected $fillable = [
        'user_id',
        'type',
        'reason',
        'points',
        'issued_by',
        'evidence_url',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}

<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthIdentity extends Model
{
    use HasFactory;

    protected $table = 'socialprofile_oauth_identities';

    protected $fillable = [
        'provider',
        'provider_user_id',
        'user_id',
        'access_token',
        'refresh_token',
        'id_token',
        'expires_at',
        'data',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

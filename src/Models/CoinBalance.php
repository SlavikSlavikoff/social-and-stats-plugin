<?php

namespace Azuriom\Plugin\SocialProfile\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;

class CoinBalance extends Model
{
    protected $table = 'socialprofile_coin_balances';

    protected $fillable = [
        'user_id',
        'balance',
        'hold',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'hold' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

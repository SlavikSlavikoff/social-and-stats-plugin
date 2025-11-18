<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrustLevel extends Model
{
    use SoftDeletes;

    public const LEVELS = ['newbie', 'verified', 'trusted', 'partner', 'staff'];

    protected $table = 'socialprofile_trust_levels';

    protected $fillable = [
        'user_id',
        'level',
        'granted_by',
        'note',
    ];

    protected $casts = [
        'level' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}

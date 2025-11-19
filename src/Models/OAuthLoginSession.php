<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;

class OAuthLoginSession extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $table = 'socialprofile_oauth_login_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'provider',
        'status',
        'user_id',
        'error_code',
        'result_payload',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'result_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markSuccess(User $user, array $payload): self
    {
        $this->user()->associate($user);
        $this->status = self::STATUS_SUCCESS;
        $this->error_code = null;
        $this->result_payload = $payload;
        $this->save();

        return $this;
    }

    public function markFailed(string $errorCode): self
    {
        $this->status = self::STATUS_FAILED;
        $this->error_code = $errorCode;
        $this->save();

        return $this;
    }

    public function markExpired(): self
    {
        $this->status = self::STATUS_EXPIRED;
        $this->save();

        return $this;
    }
}

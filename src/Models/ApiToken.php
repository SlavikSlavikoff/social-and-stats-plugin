<?php

namespace Azuriom\Plugin\SocialProfile\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ApiToken extends Model
{
    protected $table = 'socialprofile_api_tokens';

    protected $fillable = [
        'name',
        'token_hash',
        'scopes',
        'allowed_ips',
        'rate_limit',
        'created_by',
    ];

    protected $casts = [
        'scopes' => 'array',
        'allowed_ips' => 'array',
        'rate_limit' => 'array',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allowsScope(string $scope): bool
    {
        $scopes = $this->scopes ?? [];

        if (in_array('*', $scopes, true)) {
            return true;
        }

        if (in_array($scope, $scopes, true)) {
            return true;
        }

        [$domain] = explode(':', $scope.'');
        if (in_array($domain.':*', $scopes, true)) {
            return true;
        }

        return false;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findFromRequest(Request $request): ?self
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return null;
        }

        return static::where('token_hash', static::hash($token))->first();
    }

    public function withinIpRange(?string $ip): bool
    {
        if ($this->allowed_ips === null || $ip === null) {
            return true;
        }

        return in_array($ip, $this->allowed_ips, true);
    }
}

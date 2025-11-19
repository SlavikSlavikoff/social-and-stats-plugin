<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Concerns;

use Azuriom\Models\Role;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ApiToken;
use Illuminate\Support\Str;

trait CreatesUser
{
    protected function createBasicUser(array $attributes = []): User
    {
        $role = $attributes['role'] ?? Role::factory()->create();
        unset($attributes['role']);

        return User::factory()
            ->for($role)
            ->create($attributes);
    }

    protected function createUser(array $attributes = []): User
    {
        return $this->createBasicUser($attributes);
    }

    protected function createAdminUser(array $attributes = []): User
    {
        $role = Role::factory()->admin()->create();

        return $this->createBasicUser(array_merge($attributes, [
            'role' => $role,
        ]));
    }

    protected function issueToken(array $overrides = []): array
    {
        $plain = $overrides['plain'] ?? Str::random(32);

        $token = ApiToken::create([
            'name' => $overrides['name'] ?? 'Test Token',
            'token_hash' => ApiToken::hash($plain),
            'scopes' => $overrides['scopes'] ?? ['stats:read', 'stats:write', 'coins:read', 'coins:write'],
            'allowed_ips' => $overrides['allowed_ips'] ?? ['127.0.0.1'],
            'rate_limit' => $overrides['rate_limit'] ?? null,
            'created_by' => $overrides['created_by'] ?? null,
        ]);

        return [$plain, $token];
    }
}

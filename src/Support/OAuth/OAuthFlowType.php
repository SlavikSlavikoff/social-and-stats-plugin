<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth;

final class OAuthFlowType
{
    public const LINK = 'link';
    public const WEB_LOGIN = 'web_login';
    public const LAUNCHER_LOGIN = 'launcher_login';

    public static function assertValid(string $flowType): void
    {
        if (! in_array($flowType, self::all(), true)) {
            throw new \InvalidArgumentException("Unsupported OAuth flow type [{$flowType}].");
        }
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::LINK,
            self::WEB_LOGIN,
            self::LAUNCHER_LOGIN,
        ];
    }
}

<?php

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers\VkProvider;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Providers\YandexProvider;

return [
    'state_ttl' => env('SOCIALPROFILE_OAUTH_STATE_TTL', 300),

    'launcher' => [
        'session_ttl' => env('SOCIALPROFILE_OAUTH_LAUNCHER_SESSION_TTL', 600),
        'result_view' => 'socialprofile::oauth.launcher-result',
    ],

    'providers' => [
        'yandex' => [
            'driver' => YandexProvider::class,
            'client_id' => env('SOCIALPROFILE_YANDEX_CLIENT_ID'),
            'client_secret' => env('SOCIALPROFILE_YANDEX_CLIENT_SECRET'),
            'redirect_uri' => env('SOCIALPROFILE_YANDEX_REDIRECT_URI', env('APP_URL', 'http://localhost').'/oauth/callback/yandex'),
            'authorization_endpoint' => 'https://oauth.yandex.ru/authorize',
            'token_endpoint' => 'https://oauth.yandex.ru/token',
            'userinfo_endpoint' => 'https://login.yandex.ru/info',
            'scopes' => [
                'login:email',
                'login:info',
            ],
        ],
        'vk' => [
            'driver' => VkProvider::class,
            'client_id' => env('SOCIALPROFILE_VK_CLIENT_ID'),
            'client_secret' => env('SOCIALPROFILE_VK_CLIENT_SECRET'),
            'redirect_uri' => env('SOCIALPROFILE_VK_REDIRECT_URI', env('APP_URL', 'http://localhost').'/oauth/callback/vk'),
            'authorization_endpoint' => 'https://id.vk.com/authorize',
            'token_endpoint' => 'https://id.vk.com/oauth2/token',
            'userinfo_endpoint' => 'https://id.vk.com/oauth2/userinfo',
            'jwks_endpoint' => 'https://id.vk.com/oauth2/jwks',
            'scopes' => [
                'openid',
                'email',
            ],
            'api_version' => '5.131',
        ],
    ],
];

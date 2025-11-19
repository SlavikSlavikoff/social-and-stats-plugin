<?php

return [
    'default_visibility' => 'judges',
    'default_executor' => 'site',
    'limits' => [
        'comment_max' => 5000,
        'ban_minutes_min' => 5,
        'ban_minutes_max' => 2628000, // 5 years
        'mute_minutes_min' => 5,
        'mute_minutes_max' => 2628000,
        'metric_delta_min' => -10000,
        'metric_delta_max' => 10000,
        'per_user_daily_limit' => 3,
        'per_judge_hour_limit' => 30,
    ],
    'rate_limits' => [
        'public_api_per_minute' => 60,
        'internal_api_per_minute' => 120,
    ],
    'webhook' => [
        'retry_after_seconds' => 120,
        'max_attempts' => 5,
    ],
    'templates' => [
        [
            'key' => 'toxicity',
            'name' => 'Токсичность',
            'base_comment' => 'Мут за токсичность. Наказание: время на подумать на 3 часа',
            'payload' => [
                'punishment' => [
                    'socialrating' => -30,
                    'activity' => -100,
                ],
                'mute' => ['duration' => '3h'],
            ],
        ],
        [
            'key' => 'griefing',
            'name' => 'Гриферство',
            'base_comment' => 'Бан за гриферство. Наказание: изгнание с проекта на 6 месяцев',
            'payload' => [
                'punishment' => [
                    'socialrating' => -1000,
                    'activity' => -1000,
                ],
                'ban' => ['duration' => '6m'],
            ],
        ],
        [
            'key' => 'verify_fraud',
            'name' => 'Обман при верификации',
            'base_comment' => 'Обман при верификации! Наказание: снятие верификации',
            'payload' => [
                'punishment' => [],
                'unverify' => true,
            ],
        ],
    ],
];

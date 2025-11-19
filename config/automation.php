<?php

return [
    'triggers' => [
        'role_changed' => [
            'label' => 'Смена роли пользователя',
            'description' => 'Запускается при любом изменении роли игрока в панели Azuriom.',
        ],
        'trust_level_changed' => [
            'label' => 'Смена уровня доверия',
            'description' => 'Срабатывает при обновлении кастомного уровня доверия плагина.',
        ],
        'activity_changed' => [
            'label' => 'Изменение активности',
            'description' => 'Отправляется после пересчёта очков активности игрока.',
        ],
        'coins_changed' => [
            'label' => 'Изменение монет',
            'description' => 'Срабатывает при изменении баланса монет пользователя.',
        ],
        'social_stats_updated' => [
            'label' => 'Обновление игровых метрик',
            'description' => 'Получает новые значения сыгранных минут, убийств и смертей.',
        ],
        'violation_added' => [
            'label' => 'Нарушение добавлено',
            'description' => 'Запускается сразу после появления новой записи о нарушении.',
        ],
        'court_decision_changed' => [
            'label' => 'Изменение решения суда',
            'description' => 'Срабатывает когда CourtService выносит или обновляет дело.',
        ],
        'monthly_top' => [
            'label' => 'Ежемесячная выдача наград',
            'description' => 'Планировщик, который запускается по расписанию и выдаёт награды топам.',
        ],
    ],
    'actions' => [
        'minecraft_rcon_command' => [
            'label' => 'Команда RCON',
            'description' => 'Отправка команды на Minecraft-сервер по RCON.',
        ],
        'minecraft_db_query' => [
            'label' => 'SQL-запрос к сторонней БД',
            'description' => 'Например, для whitelists/blacklists.',
        ],
        'social_bot_request' => [
            'label' => 'HTTP-запрос боту/интеграции',
            'description' => 'Можно отправить payload в Discord, Telegram и т. д.',
        ],
        'internal_reward' => [
            'label' => 'Встроенная награда',
            'description' => 'Изменение соц. рейтинга, монет, активности непосредственно в плагине.',
        ],
        'assign_role' => [
            'label' => 'Смена роли Azuriom',
            'description' => 'Автоматически присваивает выбранную роль пользователю.',
        ],
    ],
    'placeholders' => [
        '{user_id}' => 'ID пользователя в Azuriom',
        '{username}' => 'Ник игрока',
        '{uuid}' => 'UUID (если хранится в статистике)',
        '{role_id}' => 'Текущая роль',
        '{old_role_id}' => 'Старая роль',
        '{new_role_id}' => 'Новая роль',
        '{old_trust_level}' => 'Предыдущий уровень доверия',
        '{new_trust_level}' => 'Новый уровень доверия',
        '{trust_level}' => 'Текущий уровень доверия',
        '{activity_points}' => 'Текущее значение активности',
        '{coin_balance}' => 'Доступный баланс монет',
        '{coin_hold}' => 'Замороженные монеты',
        '{played_minutes}' => 'Обновлённые сыгранные минуты',
        '{kills}' => 'Количество убийств',
        '{deaths}' => 'Количество смертей',
        '{violation_type}' => 'Тип нарушения',
        '{violation_points}' => 'Штрафные баллы',
        '{violation_reason}' => 'Причина нарушения',
        '{position}' => 'Позиция в топе',
        '{source_metric}' => 'Метрика, по которой игрок оказался в топе',
        '{case_number}' => 'Номер дела суда',
        '{case_status}' => 'Статус судебного дела',
        '{case_action}' => 'Последнее событие суда',
        '{case_executor}' => 'Исполнитель из суда',
    ],
    'monthly_rewards' => [
        'enabled' => true,
        'day' => 1,
        'hour' => 12,
        'top_limit' => 5,
        'sources' => ['social_score', 'activity'],
        'reward' => [
            'social_score' => 25,
            'coins' => 100,
            'activity' => 50,
        ],
    ],
    'documentation' => [
        'path' => dirname(__DIR__).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'AUTOMATION.md',
    ],
];

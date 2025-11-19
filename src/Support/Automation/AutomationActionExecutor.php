<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Automation;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Support\Automation\Clients\MinecraftRconClient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AutomationActionExecutor
{
    /**
     * @var array<int, AutomationIntegration|null>
     */
    protected array $integrationCache = [];

    /**
     * @var array<int, array{client: MinecraftRconClient, hash: string}>
     */
    protected array $rconClients = [];

    /**
     * @var array<int, array{connection: ConnectionInterface, hash: string, name: string}>
     */
    protected array $databaseConnections = [];

    /**
     * @var array<int, \Illuminate\Http\Client\PendingRequest>
     */
    protected array $httpClients = [];

    /**
     * @param array<int, array<string, mixed>> $actions
     * @return array<int, array<string, mixed>>
     */
    public function executeMany(array $actions, array $payload, array $context = []): array
    {
        $results = [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? null;

            if ($type === null) {
                continue;
            }

            try {
                $result = $this->executeSingle($action, $payload, $context);
                $result['status'] = 'success';
                $result['type'] = $type;
            } catch (\Throwable $e) {
                $result = [
                    'type' => $type,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];

                if (! ($action['continue_on_error'] ?? false)) {
                    $results[] = $result;
                    throw $e;
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    public function testIntegration(AutomationIntegration $integration): array
    {
        return match ($integration->type) {
            AutomationIntegration::TYPE_RCON => $this->testRcon($integration),
            AutomationIntegration::TYPE_DATABASE => $this->testDatabase($integration),
            AutomationIntegration::TYPE_SOCIAL_BOT => $this->testSocialBot($integration),
            default => ['message' => 'Проверка не требуется для этого типа.'],
        };
    }

    public function rewardUser(User $user, array $config, array $meta = []): array
    {
        $direction = $config['direction'] ?? 'increase';
        $changes = [];

        $scoreDelta = $this->calculateDelta($config['social_score'] ?? 0, $direction);
        $coinDelta = $this->calculateDelta($config['coins'] ?? 0, $direction);
        $activityDelta = $this->calculateDelta($config['activity'] ?? 0, $direction);

        if ($scoreDelta !== 0.0) {
            $record = SocialScore::firstOrCreate(['user_id' => $user->id]);
            $record->increment('score', (int) $scoreDelta);
            $changes['social_score'] = (int) $scoreDelta;
        }

        if ($coinDelta !== 0.0) {
            $record = CoinBalance::firstOrCreate(['user_id' => $user->id]);
            $record->increment('balance', $coinDelta);
            $changes['coins'] = $coinDelta;
        }

        if ($activityDelta !== 0.0) {
            $record = ActivityPoint::firstOrCreate(['user_id' => $user->id]);
            $record->increment('points', (int) $activityDelta);
            $changes['activity'] = (int) $activityDelta;
        }

        if ($changes === []) {
            throw new RuntimeException('Не задано ни одного значения награды.');
        }

        return [
            'summary' => 'Внутренняя награда обновила показатели.',
            'changes' => $changes,
            'meta' => $meta,
        ];
    }

    protected function executeSingle(array $action, array $payload, array $context): array
    {
        return match ($action['type']) {
            'minecraft_rcon_command' => $this->runRconCommand($action, $payload),
            'minecraft_db_query' => $this->runDatabaseQuery($action, $payload),
            'social_bot_request' => $this->sendBotRequest($action, $payload),
            'internal_reward' => $this->handleInternalReward($action, $payload, $context),
            'assign_role' => $this->assignRole($action, $payload, $context),
            default => throw new RuntimeException(sprintf('Неизвестный тип действия: %s', $action['type'])),
        };
    }

    protected function runRconCommand(array $action, array $payload): array
    {
        $integration = $this->resolveIntegration($action, AutomationIntegration::TYPE_RCON);
        $commandTemplate = Arr::get($action, 'config.command');

        if (! $commandTemplate) {
            throw new RuntimeException('Команда RCON не задана.');
        }

        $command = $this->renderTemplate($commandTemplate, $payload);
        $client = $this->rconClient($integration);

        $response = $client->sendCommand($command);

        return [
            'summary' => sprintf(
                'Команда "%s" отправлена: %s:%s',
                $command,
                $integration->configValue('host'),
                $integration->configValue('port', 25575)
            ),
            'response' => Str::limit($response ?? '', 200),
        ];
    }

    protected function runDatabaseQuery(array $action, array $payload): array
    {
        $integration = $this->resolveIntegration($action, AutomationIntegration::TYPE_DATABASE);
        $queryTemplate = Arr::get($action, 'config.query');

        if (! $queryTemplate) {
            throw new RuntimeException('SQL-запрос не задан.');
        }

        $query = $this->renderTemplate($queryTemplate, $payload);
        $connection = $this->databaseConnection($integration);
        $affected = $connection->affectingStatement($query);

        return [
            'summary' => sprintf('SQL выполнен, изменено строк: %d', $affected),
        ];
    }

    protected function sendBotRequest(array $action, array $payload): array
    {
        $integration = $this->resolveIntegration($action, AutomationIntegration::TYPE_SOCIAL_BOT, false);
        $config = $action['config'] ?? [];
        $method = strtoupper($config['method'] ?? 'POST');
        $baseUrl = $integration?->configValue('base_url');
        $url = $config['url'] ?? $baseUrl;

        if (! $url) {
            throw new RuntimeException('URL для отправки запроса не задан.');
        }

        $bodyTemplate = $config['body'] ?? null;
        $format = $config['format'] ?? 'json';
        $timeout = (int) ($config['timeout'] ?? 10);
        $headers = $this->buildHeaders($integration, $config);
        $request = $this->httpClient($integration, ['timeout' => $timeout]);

        if ($headers !== []) {
            $request = $request->withHeaders($headers);
        }

        if (($config['token'] ?? null) !== null) {
            $request = $request->withToken($config['token']);
        }

        $options = [];
        if ($format === 'json') {
            $json = $bodyTemplate ? $this->decodeJson($this->renderTemplate($bodyTemplate, $payload)) : [];
            $options['json'] = $json;
        } else {
            $options['body'] = $bodyTemplate ? $this->renderTemplate($bodyTemplate, $payload) : '';
        }

        $response = $request->send($method, $url, $options);

        return [
            'summary' => sprintf('%s %s → %s', $method, $url, $response->status()),
            'status_code' => $response->status(),
        ];
    }

    protected function handleInternalReward(array $action, array $payload, array $context): array
    {
        $user = $this->resolveUser($payload, $context);

        if ($user === null) {
            throw new RuntimeException('Пользователь не найден для награды.');
        }

        $config = $action['config'] ?? [];

        return $this->rewardUser($user, $config, ['trigger' => 'automation_rule']);
    }

    protected function assignRole(array $action, array $payload, array $context): array
    {
        $user = $this->resolveUser($payload, $context);

        if ($user === null) {
            throw new RuntimeException('Пользователь не найден для смены роли.');
        }

        $roleId = (int) ($action['config']['role_id'] ?? 0);

        if ($roleId <= 0) {
            throw new RuntimeException('Не выбрана роль для назначения.');
        }

        $oldRole = $user->role_id;

        if ($oldRole === $roleId) {
            return [
                'summary' => 'Роль уже установлена.',
                'old_role_id' => $oldRole,
                'new_role_id' => $roleId,
            ];
        }

        $user->role_id = $roleId;
        $user->save();

        return [
            'summary' => 'Роль пользователя обновлена автоматизацией.',
            'old_role_id' => $oldRole,
            'new_role_id' => $roleId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeJson(string $value): ?array
    {
        $decoded = json_decode($value, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Некорректный JSON в теле запроса.');
        }

        return $decoded ?? [];
    }

    protected function resolveIntegration(array $action, ?string $expectedType = null, bool $required = true): ?AutomationIntegration
    {
        $integrationId = $action['integration_id'] ?? null;

        if ($integrationId === null) {
            if ($required) {
                throw new RuntimeException('Без интеграции действие выполнить нельзя.');
            }

            return null;
        }

        if (! array_key_exists($integrationId, $this->integrationCache)) {
            $this->integrationCache[$integrationId] = AutomationIntegration::find($integrationId);
        }

        $integration = $this->integrationCache[$integrationId];

        if ($integration === null) {
            throw new RuntimeException(sprintf('Интеграция #%s не найдена.', $integrationId));
        }

        if ($expectedType !== null && $integration->type !== $expectedType) {
            throw new RuntimeException('Тип интеграции не соответствует действию.');
        }

        return $integration;
    }

    protected function renderTemplate(string $template, array $payload): string
    {
        $replacements = [
            '{user_id}' => (string) ($payload['user_id'] ?? ''),
            '{username}' => (string) ($payload['username'] ?? ''),
            '{uuid}' => (string) ($payload['uuid'] ?? ''),
            '{role_id}' => (string) ($payload['role_id'] ?? ''),
            '{old_role_id}' => (string) ($payload['old_role_id'] ?? ''),
            '{new_role_id}' => (string) ($payload['new_role_id'] ?? ''),
            '{old_trust_level}' => (string) ($payload['old_trust_level'] ?? ''),
            '{new_trust_level}' => (string) ($payload['new_trust_level'] ?? ''),
            '{trust_level}' => (string) ($payload['trust_level'] ?? ''),
            '{position}' => (string) ($payload['position'] ?? ''),
            '{source_metric}' => (string) ($payload['source_metric'] ?? ''),
            '{actor_id}' => (string) ($payload['actor_id'] ?? ''),
        ];

        return strtr($template, $replacements);
    }

    protected function rconClient(AutomationIntegration $integration): MinecraftRconClient
    {
        $config = [
            'host' => (string) $integration->configValue('host', ''),
            'port' => (int) $integration->configValue('port', 25575),
            'password' => (string) $integration->configValue('password', ''),
            'timeout' => (int) $integration->configValue('timeout', 5),
        ];
        $hash = md5(json_encode($config));
        $key = $integration->id;

        if (! isset($this->rconClients[$key]) || $this->rconClients[$key]['hash'] !== $hash) {
            $this->rconClients[$key] = [
                'hash' => $hash,
                'client' => new MinecraftRconClient(
                    $config['host'],
                    $config['port'],
                    $config['password'],
                    $config['timeout']
                ),
            ];
        }

        return $this->rconClients[$key]['client'];
    }

    protected function databaseConnection(AutomationIntegration $integration): ConnectionInterface
    {
        $config = $integration->config ?? [];
        $hash = md5(json_encode($config));
        $key = $integration->id;

        if (! isset($this->databaseConnections[$key]) || $this->databaseConnections[$key]['hash'] !== $hash) {
            $connectionName = 'automation_'.$integration->id;

            config([
                'database.connections.'.$connectionName => [
                    'driver' => $config['driver'] ?? 'mysql',
                    'host' => $config['host'] ?? '127.0.0.1',
                    'port' => $config['port'] ?? 3306,
                    'database' => $config['database'] ?? '',
                    'username' => $config['username'] ?? '',
                    'password' => $config['password'] ?? '',
                    'charset' => $config['charset'] ?? 'utf8mb4',
                    'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
                    'prefix' => $config['prefix'] ?? '',
                ],
            ]);

            DB::purge($connectionName);
            $connection = DB::connection($connectionName);
            $connection->getPdo();

            $this->databaseConnections[$key] = [
                'connection' => $connection,
                'hash' => $hash,
                'name' => $connectionName,
            ];
        }

        return $this->databaseConnections[$key]['connection'];
    }

    protected function httpClient(?AutomationIntegration $integration, array $config = []): PendingRequest
    {
        $timeout = (int) ($config['timeout'] ?? 10);

        if ($integration === null) {
            return Http::timeout($timeout);
        }

        $key = $integration->id;

        if (! isset($this->httpClients[$key])) {
            $base = Http::timeout((int) $integration->configValue('timeout', $timeout));

            if ($token = $integration->configValue('token')) {
                $base = $base->withToken($token);
            }

            if ($baseUrl = $integration->configValue('base_url')) {
                $base = $base->baseUrl($baseUrl);
            }

            $this->httpClients[$key] = $base;
        }

        $client = clone $this->httpClients[$key];

        return $client->timeout($timeout);
    }

    /**
     * @return array<string, string>
     */
    protected function buildHeaders(?AutomationIntegration $integration, array $config): array
    {
        $headers = [];
        $defaultHeaders = $integration?->configValue('default_headers', []);

        if (is_array($defaultHeaders)) {
            $headers = array_merge($headers, $defaultHeaders);
        }

        if (isset($config['headers']) && is_array($config['headers'])) {
            $headers = array_merge($headers, $config['headers']);
        }

        return $headers;
    }

    protected function resolveUser(array $payload, array $context): ?User
    {
        if (($context['user'] ?? null) instanceof User) {
            return $context['user'];
        }

        $userId = $payload['user_id'] ?? null;

        if ($userId === null) {
            return null;
        }

        return User::find($userId);
    }

    protected function testRcon(AutomationIntegration $integration): array
    {
        $client = $this->rconClient($integration);
        $client->sendCommand('list');

        return ['message' => 'Соединение RCON успешно.'];
    }

    protected function testDatabase(AutomationIntegration $integration): array
    {
        $connection = $this->databaseConnection($integration);
        $result = $connection->select('SELECT 1 as ping');

        return ['message' => 'Подключение к базе установлено.', 'result' => $result];
    }

    protected function testSocialBot(AutomationIntegration $integration): array
    {
        $baseUrl = $integration->configValue('base_url');

        if (! $baseUrl) {
            throw new RuntimeException('Укажите URL бота для проверки.');
        }

        $response = $this->httpClient($integration, ['timeout' => 5])->get($baseUrl);

        return ['message' => 'Запрос отправлен.', 'status_code' => $response->status()];
    }

    protected function calculateDelta(int|float $value, string $direction): float
    {
        $value = (float) $value;

        if ($value === 0.0) {
            return 0.0;
        }

        if ($value > 0 && $direction === 'decrease') {
            return -$value;
        }

        return $value;
    }
}

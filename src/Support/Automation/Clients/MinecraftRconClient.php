<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Automation\Clients;

use RuntimeException;

class MinecraftRconClient
{
    private const TYPE_COMMAND = 2;
    private const TYPE_LOGIN = 3;

    private $socket;
    private int $requestId = 0;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $password,
        private readonly int $timeout = 5
    ) {
    }

    public function sendCommand(string $command): string
    {
        $this->connectIfNeeded();
        $this->writePacket(self::TYPE_COMMAND, $command);

        $response = $this->readPacket();

        return $response['body'] ?? '';
    }

    protected function connectIfNeeded(): void
    {
        if (is_resource($this->socket)) {
            return;
        }

        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (! is_resource($this->socket)) {
            throw new RuntimeException(sprintf('Не удалось подключиться к RCON (%s:%s): %s', $this->host, $this->port, $errstr));
        }

        stream_set_timeout($this->socket, $this->timeout);
        $this->authenticate();
    }

    protected function authenticate(): void
    {
        $this->writePacket(self::TYPE_LOGIN, $this->password);
        $response = $this->readPacket();

        if (($response['id'] ?? -1) === -1) {
            throw new RuntimeException('Ошибка авторизации RCON. Проверьте пароль.');
        }
    }

    protected function writePacket(int $type, string $body): void
    {
        $payload = pack('VV', ++$this->requestId, $type).$body."\x00\x00";
        $packet = pack('V', strlen($payload)).$payload;
        $written = fwrite($this->socket, $packet);

        if ($written === false) {
            throw new RuntimeException('Не удалось отправить пакет RCON.');
        }
    }

    /**
     * @return array{id: int, type: int, body: string}
     */
    protected function readPacket(): array
    {
        $lengthData = fread($this->socket, 4);

        if ($lengthData === false || strlen($lengthData) !== 4) {
            throw new RuntimeException('RCON вернул пустой ответ.');
        }

        $length = unpack('V1length', $lengthData)['length'];
        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($this->socket, $length - strlen($buffer));

            if ($chunk === false) {
                break;
            }

            $buffer .= $chunk;
        }

        if (strlen($buffer) < $length) {
            throw new RuntimeException('Ответ RCON повреждён или неполный.');
        }

        $header = unpack('V1id/V1type', substr($buffer, 0, 8));
        $body = substr($buffer, 8, -2);

        return [
            'id' => $header['id'],
            'type' => $header['type'],
            'body' => $body,
        ];
    }

    public function __destruct()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }
}

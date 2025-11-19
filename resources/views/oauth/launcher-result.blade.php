<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация через OAuth</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap">
    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
            background: #0f172a;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            padding: 32px;
            width: min(420px, 90%);
            text-align: center;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.4);
        }
        h1 {
            margin-bottom: 12px;
            font-size: 1.4rem;
        }
        p {
            margin: 0 0 8px;
        }
        .status {
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-size: 0.8rem;
            margin-bottom: 18px;
        }
        .status--success { color: #4ade80; }
        .status--failed { color: #f87171; }
        .status--pending { color: #fde047; }
    </style>
</head>
<body>
    <div class="card">
        <div class="status status--{{ $session->status }}">
            {{ strtoupper($session->status) }}
        </div>
        @if($session->status === \Azuriom\Plugin\InspiratoStats\Models\OAuthLoginSession::STATUS_SUCCESS)
            <h1>OAuth завершён</h1>
            <p>Вы можете вернуться в лаунчер. Авторизация завершится автоматически.</p>
        @elseif($session->status === \Azuriom\Plugin\InspiratoStats\Models\OAuthLoginSession::STATUS_FAILED)
            <h1>Не удалось войти</h1>
            <p>Аккаунт ещё не привязан или запрос устарел.</p>
        @else
            <h1>Почти готово</h1>
            <p>Ожидаем подтверждения лаунчером...</p>
        @endif
    </div>
</body>
</html>

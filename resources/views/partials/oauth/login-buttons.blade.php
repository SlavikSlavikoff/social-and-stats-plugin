@php
    $configuredProviders = collect(config('socialprofile.oauth.providers', []))
        ->filter(fn ($provider) => filled($provider['client_id'] ?? null) && filled($provider['client_secret'] ?? null));

    $meta = [
        'vk' => [
            'label' => 'VK ID',
            'description' => __('socialprofile::messages.oauth.provider_description.vk'),
            'class' => 'oauth-button--vk',
            'icon' => '<svg viewBox="0 0 40 40" role="img" aria-hidden="true"><rect width="40" height="40" rx="12"></rect><path d="M27.45 13.63c-.16-.46-.35-.63-.88-.63h-2.91c-.68 0-.99.22-.99.73 0 .69 1.02.86 1.14 2.82-.01.27-.06.54-.13.8-.06.25-.21.48-.43.64-.23.17-.53.26-.86.26-1.48 0-2.65-1.69-3.48-3.38-.72-1.48-1.28-2.5-1.28-2.5-.28-.44-.43-.63-.9-.63h-2.93c-.58 0-.87.27-.87.69 0 .66.97.85 1.41 2.09 1.41 3.91 3.45 6.56 5.86 7.43.71.25 1.26.32 1.72.32.57 0 .99-.13 1.12-.19l-.06 1.88c0 .63.23.75.56.75h2.44c.56 0 .83-.27.94-.8.14-.72.18-1.9.18-1.9.03-.83.36-.95.73-.95h2.56c.62 0 .78-.3.78-.77 0-.3-.12-.64-.54-1.09-.26-.29-.73-.71-1.31-1.21-.71-.63-.75-.72-.21-1.45.63-.86 1.23-1.81 1.5-2.44.06-.13.11-.25.15-.37.34-.95.03-1.17-.55-1.17h-2.59c-.6 0-.85.42-1.04.9-.62 1.55-1.64 3.09-2 3.09-.32 0-.52-.52-.57-.72-.14-.53-.52-1.86-.52-1.86z"></path></svg>',
        ],
        'yandex' => [
            'label' => 'Yandex ID',
            'description' => __('socialprofile::messages.oauth.provider_description.yandex'),
            'class' => 'oauth-button--yandex',
            'icon' => '<svg viewBox="0 0 40 40" role="img" aria-hidden="true"><rect width="40" height="40" rx="12"></rect><path d="M21.64 10h-3.28c-.44 0-.71.24-.71.66v18.68c0 .42.27.66.71.66h3.17c.47 0 .72-.24.72-.66V22.4l3.69 7.27c.21.37.39.53.88.53h3.44c.58 0 .74-.32.49-.78l-4.52-7.83 4.43-10.35c.21-.51.03-.92-.55-.92h-3.21c-.46 0-.68.2-.81.55l-3.64 8.96V10.66c0-.42-.26-.66-.72-.66z"></path></svg>',
        ],
    ];

    $providers = $configuredProviders
        ->filter(fn ($config, $key) => array_key_exists($key, $meta))
        ->map(fn ($config, $key) => [
            'key' => $key,
            'label' => $meta[$key]['label'],
            'description' => $meta[$key]['description'],
            'class' => $meta[$key]['class'],
            'icon' => $meta[$key]['icon'],
            'url' => route('socialprofile.oauth.login', $key).'?'.http_build_query([
                'redirect' => request()->get('redirect', url()->previous() ?: url('/')),
            ]),
        ]);
@endphp

@if($providers->isNotEmpty())
    @once
        @push('styles')
            <style>
                .oauth-login {
                    background: var(--bs-body-bg);
                    border: 1px solid rgba(0, 0, 0, 0.08);
                    border-radius: 1rem;
                    padding: 1.5rem;
                    box-shadow: 0 1rem 3rem rgba(15, 23, 42, 0.08);
                }

                .oauth-login__title {
                    font-weight: 600;
                }

                .oauth-login__buttons {
                    display: grid;
                    gap: 0.75rem;
                    margin-top: 1rem;
                }

                .oauth-button {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0.65rem 0.95rem;
                    border-radius: 0.75rem;
                    border: 1px solid transparent;
                    transition: transform .15s ease, box-shadow .15s ease;
                }

                .oauth-button svg {
                    width: 40px;
                    height: 40px;
                    margin-right: 0.75rem;
                }

                .oauth-button rect {
                    fill: currentColor;
                }

                .oauth-button:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 0.5rem 1rem rgba(15, 23, 42, 0.18);
                }

                .oauth-button__info {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    color: inherit;
                }

                .oauth-button__info strong {
                    font-size: 0.95rem;
                }

                .oauth-button__hint {
                    font-size: 0.85rem;
                    opacity: 0.85;
                }

                .oauth-button--vk {
                    background: #fff;
                    border-color: rgba(0, 119, 255, 0.25);
                    color: #0077ff;
                }

                .oauth-button--vk svg path {
                    fill: #fff;
                }

                .oauth-button--vk svg rect {
                    fill: #0077ff;
                }

                .oauth-button--yandex {
                    background: #fff;
                    border-color: rgba(0, 0, 0, 0.08);
                    color: #000;
                }

                .oauth-button--yandex svg rect {
                    fill: #000;
                }

                .oauth-button--yandex svg path {
                    fill: #fff;
                }
            </style>
        @endpush
    @endonce

    <div class="oauth-login mt-4">
        <p class="oauth-login__title mb-1">
            {{ __('socialprofile::messages.oauth.login_with') }}
        </p>
        <p class="text-muted small mb-3">
            {{ __('socialprofile::messages.oauth.description') }}
        </p>

        <div class="oauth-login__buttons">
            @foreach($providers as $provider)
                <a href="{{ $provider['url'] }}" class="oauth-button {{ $provider['class'] }}">
                    <div class="oauth-button__info">
                        {!! $provider['icon'] !!}
                        <div>
                            <strong>{{ $provider['label'] }}</strong>
                            <div class="oauth-button__hint">{{ $provider['description'] }}</div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right"></i>
                </a>
            @endforeach
        </div>
    </div>
@endif

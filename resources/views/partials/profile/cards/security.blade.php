@php
    $providers = collect($providers ?? []);
@endphp

@if($providers->isNotEmpty())
    <div class="card h-100 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="card-title mb-1">
                        {{ __('socialprofile::messages.profile.cards.security.title') }}
                    </h5>
                    <p class="text-muted small mb-0">
                        {{ __('socialprofile::messages.profile.cards.security.description') }}
                    </p>
                </div>
                <i class="bi bi-shield-lock text-primary fs-3"></i>
            </div>

            <div class="list-group list-group-flush">
                @foreach($providers as $provider)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="oauth-provider-icon oauth-provider-icon--{{ $provider['key'] }}">
                                    {!! $provider['icon'] !!}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $provider['label'] }}</div>
                                    <div class="small text-muted">
                                        @if($provider['linked'])
                                            <span class="text-success">
                                                {{ __('socialprofile::messages.profile.cards.security.linked') }}
                                            </span>
                                        @else
                                            <span class="text-danger">
                                                {{ __('socialprofile::messages.profile.cards.security.not_linked') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                @if($provider['linked'])
                                    <form method="POST" action="{{ $provider['unlink_url'] }}">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger btn-sm">
                                            {{ __('socialprofile::messages.profile.cards.security.unlink') }}
                                        </button>
                                    </form>
                                @else
                                    <a class="btn btn-primary btn-sm" href="{{ $provider['link_url'] }}">
                                        {{ __('socialprofile::messages.profile.cards.security.link') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @once
        @push('styles')
            <style>
                .oauth-provider-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 12px;
                    display: grid;
                    place-items: center;
                    color: #fff;
                    font-weight: 600;
                }

                .oauth-provider-icon--vk {
                    background: #0077ff;
                }

                .oauth-provider-icon--yandex {
                    background: #000;
                }

                .oauth-provider-icon svg {
                    width: 22px;
                    height: 22px;
                    fill: currentColor;
                }
            </style>
        @endpush
    @endonce
@endif

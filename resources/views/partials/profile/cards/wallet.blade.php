@php
    $coins = $coins ?? null;
@endphp

<div class="socialprofile-card profile-card">
    <div class="d-flex align-items-center mb-3">
        <i class="fas fa-wallet text-warning me-2"></i>
        <div>
            <h3 class="h5 mb-0">{{ __('socialprofile::messages.profile.cards.wallet.title') }}</h3>
            <small class="text-muted">{{ __('socialprofile::messages.profile.cards.wallet.balance') }}</small>
        </div>
    </div>

    <div class="wallet-balance display-5 fw-semibold text-primary">
        {{ number_format($coins->balance ?? 0, 2) }}
    </div>
    <p class="text-muted mb-0">
        {{ __('socialprofile::messages.metrics.hold') }}: {{ number_format($coins->hold ?? 0, 2) }}
    </p>
</div>

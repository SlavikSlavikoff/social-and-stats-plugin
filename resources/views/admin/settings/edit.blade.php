@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.settings.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="socialprofile-card">
        <h3>{{ __('socialprofile::messages.admin.settings.title') }}</h3>
        <form method="POST" action="{{ route('socialprofile.admin.settings.update') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('socialprofile::messages.admin.settings.public_rate_limit') }}</label>
                    <input type="number" name="public_rate_limit" class="form-control" min="1" value="{{ $publicRateLimit }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('socialprofile::messages.admin.settings.token_rate_limit') }}</label>
                    <input type="number" name="token_rate_limit" class="form-control" min="1" value="{{ $tokenRateLimit }}">
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="show_coins_public" id="show_coins_public" value="1" @checked($showCoinsPublic)>
                        <label class="form-check-label" for="show_coins_public">
                            {{ __('socialprofile::messages.admin.settings.show_coins_public') }}
                        </label>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_hmac" id="enable_hmac" value="1" @checked($enableHmac)>
                        <label class="form-check-label" for="enable_hmac">
                            {{ __('socialprofile::messages.admin.settings.enable_hmac') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('socialprofile::messages.admin.settings.hmac_secret') }}</label>
                    <input type="text" name="hmac_secret" class="form-control" value="{{ $hmacSecret }}">
                    <small class="text-muted">{{ __('socialprofile::messages.admin.settings.hmac_hint') }}</small>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.settings.save_button') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

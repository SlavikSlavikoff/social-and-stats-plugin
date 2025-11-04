@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.tokens.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($generatedToken)
        <div class="alert alert-info">
            <strong>{{ __('socialprofile::messages.admin.tokens.copy_token') }}</strong>
            <code class="d-block mt-2">{{ $generatedToken }}</code>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.admin.tokens.create') }}</h3>
                <form method="POST" action="{{ route('socialprofile.admin.tokens.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.admin.tokens.name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.admin.tokens.scopes') }}</label>
                        <div class="token-scope-grid">
                            @foreach($availableScopes as $scope)
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input" name="scopes[]" value="{{ $scope }}" checked>
                                    <span class="form-check-label">{{ $scope }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.admin.tokens.allowed_ips') }}</label>
                        <textarea name="allowed_ips" class="form-control" rows="2" placeholder="127.0.0.1, 10.0.0.0/24"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('socialprofile::messages.admin.tokens.rate_limit') }}</label>
                        <input type="number" name="rate_limit" class="form-control" min="1" value="120">
                        <small class="text-muted">{{ __('socialprofile::messages.admin.tokens.rate_limit_hint') }}</small>
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.tokens.create_button') }}</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.admin.tokens.existing') }}</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>{{ __('socialprofile::messages.admin.tokens.name') }}</th>
                            <th>{{ __('socialprofile::messages.admin.tokens.scopes') }}</th>
                            <th>{{ __('socialprofile::messages.admin.tokens.rate_limit') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tokens as $token)
                            <tr>
                                <td>{{ $token->name }}</td>
                                <td>
                                    @foreach($token->scopes as $scope)
                                        <span class="badge bg-secondary me-1">{{ $scope }}</span>
                                    @endforeach
                                    @if($token->allowed_ips)
                                        <div class="small text-muted mt-1">{{ implode(', ', $token->allowed_ips) }}</div>
                                    @endif
                                </td>
                                <td>{{ $token->rate_limit['per_minute'] ?? __('socialprofile::messages.admin.tokens.inherit') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('socialprofile.admin.tokens.rotate', $token) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">{{ __('socialprofile::messages.admin.tokens.rotate') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('socialprofile.admin.tokens.destroy', $token) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('socialprofile::messages.admin.tokens.confirm_delete') }}')">&times;</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

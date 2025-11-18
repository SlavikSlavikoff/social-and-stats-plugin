@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.violations.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="socialprofile-card mb-4">
        <h3>{{ __('socialprofile::messages.admin.violations.create_title') }}</h3>
        <form method="POST" action="{{ route('socialprofile.admin.violations.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.admin.violations.player_id') }}</label>
                <input type="number" class="form-control" name="user_id" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('socialprofile::messages.profile.type') }}</label>
                <select name="type" class="form-select">
                    @foreach(['warning','mute','ban','other'] as $type)
                        <option value="{{ $type }}">{{ __('socialprofile::messages.violations.types.' . $type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('socialprofile::messages.profile.points') }}</label>
                <input type="number" class="form-control" name="points" min="0" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('socialprofile::messages.profile.reason') }}</label>
                <input type="text" class="form-control" name="reason" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('socialprofile::messages.admin.users.evidence') }}</label>
                <input type="url" class="form-control" name="evidence_url">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-danger">{{ __('socialprofile::messages.admin.violations.create_button') }}</button>
            </div>
        </form>
    </div>

    <div class="socialprofile-card">
        <h3>{{ __('socialprofile::messages.admin.violations.list_title') }}</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('socialprofile::messages.admin.violations.player') }}</th>
                    <th>{{ __('socialprofile::messages.profile.type') }}</th>
                    <th>{{ __('socialprofile::messages.profile.points') }}</th>
                    <th>{{ __('socialprofile::messages.profile.date') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($violations as $violation)
                    <tr>
                        <td>{{ $violation->id }}</td>
                        <td>{{ $violation->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }}</td>
                        <td>{{ __('socialprofile::messages.violations.types.' . $violation->type) }}</td>
                        <td>{{ $violation->points }}</td>
                        <td>{{ $violation->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('socialprofile.admin.violations.destroy', $violation) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('socialprofile::messages.admin.violations.confirm_delete') }}')">&times;</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $violations->links() }}
    </div>
</div>
@endsection

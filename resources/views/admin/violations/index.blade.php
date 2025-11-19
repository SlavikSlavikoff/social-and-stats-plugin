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

    <div class="alert alert-info mb-4">
        {{ __('socialprofile::messages.admin.violations.redirect_notice') }}
        <a href="{{ route('socialprofile.court.judge') }}" class="btn btn-sm btn-outline-primary ms-2">
            <i class="fas fa-gavel me-1"></i>{{ __('socialprofile::messages.court.judge.cta') }}
        </a>
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

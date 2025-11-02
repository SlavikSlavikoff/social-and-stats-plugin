@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.users.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="socialprofile-admin">
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="query" class="form-control" placeholder="{{ __('socialprofile::messages.admin.users.search') }}" value="{{ $query }}">
            <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.users.search_button') }}</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>{{ __('socialprofile::messages.admin.users.player') }}</th>
                <th>{{ __('socialprofile::messages.metrics.social_score') }}</th>
                <th>{{ __('socialprofile::messages.metrics.activity') }}</th>
                <th>{{ __('socialprofile::messages.metrics.coins') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                @php($score = $scores[$user->id] ?? null)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ optional($score)->score ?? '—' }}</td>
                    <td>{{ optional($activities[$user->id] ?? null)->points ?? '—' }}</td>
                    <td>{{ optional($coins[$user->id] ?? null)->balance ?? '—' }}</td>
                    <td>
                        <a href="{{ route('socialprofile.admin.users.show', $user) }}" class="btn btn-sm btn-secondary">{{ __('socialprofile::messages.admin.users.view') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection

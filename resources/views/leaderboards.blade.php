@extends('layouts.app')

@section('title', __('socialprofile::messages.leaderboards.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
@endpush

@section('content')
<div class="container socialprofile-container">
    <h1 class="mb-4">{{ __('socialprofile::messages.leaderboards.title') }}</h1>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h2>{{ __('socialprofile::messages.leaderboards.activity') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('socialprofile::messages.leaderboards.player') }}</th>
                            <th>{{ __('socialprofile::messages.metrics.activity') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($activityLeaders as $index => $entry)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $entry->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }}</td>
                                <td>{{ $entry->points }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="socialprofile-card">
                <h2>{{ __('socialprofile::messages.leaderboards.social_score') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('socialprofile::messages.leaderboards.player') }}</th>
                            <th>{{ __('socialprofile::messages.metrics.social_score') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($scoreLeaders as $index => $entry)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $entry->user?->name ?? __('socialprofile::messages.leaderboards.unknown') }}</td>
                                <td>{{ $entry->score }}</td>
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

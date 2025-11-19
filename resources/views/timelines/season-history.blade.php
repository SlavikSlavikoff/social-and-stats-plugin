@extends('layouts.app')

@section('title', $timeline?->meta_title ?? __('socialprofile::messages.timelines.season_history.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/timelines.css') }}">
@endpush

@push('scripts')
<script src="{{ plugin_asset('socialprofile', 'js/timeline-slider.js') }}" defer></script>
@endpush

@section('content')
<div class="container socialprofile-timeline-page">
    <h1 class="mb-2">{{ $timeline->title ?? __('socialprofile::messages.timelines.season_history.title') }}</h1>
    <p class="lead text-muted">{{ $timeline->subtitle ?? __('socialprofile::messages.timelines.season_history.subtitle') }}</p>

    @if($timeline?->intro_text)
        <div class="timeline-intro mb-4">
            {!! nl2br(e($timeline->intro_text)) !!}
        </div>
    @endif

    @if($timeline && $timeline->periods->isNotEmpty())
        @include('socialprofile::timelines._slider', ['timeline' => $timeline])
    @else
        <div class="alert alert-info">
            {{ __('socialprofile::messages.timelines.season_history.empty') }}
        </div>
    @endif
</div>
@endsection

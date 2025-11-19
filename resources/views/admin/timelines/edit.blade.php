@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.timelines.manage_title', ['title' => $timeline->title]))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/timelines.css') }}">
@endpush

@push('scripts')
<script src="{{ plugin_asset('socialprofile', 'js/timeline-admin.js') }}" defer></script>
@endpush

@section('content')
<div class="socialprofile-timelines-admin">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h3 class="card-title mb-0">{{ $timeline->title }}</h3>
                <small class="text-muted text-uppercase">{{ __('socialprofile::messages.admin.timelines.types.'.$timeline->type) }}</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('socialprofile.admin.timelines.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> {{ __('socialprofile::messages.admin.timelines.actions.back') }}
                </a>
                <form action="{{ route('socialprofile.admin.timelines.destroy', $timeline) }}" method="POST" onsubmit="return confirm('{{ __('socialprofile::messages.admin.timelines.confirm_delete') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash me-1"></i> {{ __('socialprofile::messages.admin.timelines.actions.delete') }}
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <nav class="nav nav-pills mb-4 gap-2">
                <a class="nav-link {{ $tab === 'settings' ? 'active' : '' }}" href="{{ route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'settings']) }}">
                    {{ __('socialprofile::messages.admin.timelines.tabs.settings') }}
                </a>
                <a class="nav-link {{ $tab === 'periods' ? 'active' : '' }}" href="{{ route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'periods']) }}">
                    {{ __('socialprofile::messages.admin.timelines.tabs.periods') }}
                </a>
                <a class="nav-link {{ $tab === 'cards' ? 'active' : '' }}" href="{{ route('socialprofile.admin.timelines.edit', ['timeline' => $timeline, 'tab' => 'cards']) }}">
                    {{ __('socialprofile::messages.admin.timelines.tabs.cards') }}
                </a>
            </nav>

            @if($tab === 'settings')
                <form method="POST" action="{{ route('socialprofile.admin.timelines.update', $timeline) }}">
                    @csrf
                    @method('PUT')

                    <div class="alert alert-secondary">
                        {{ __('socialprofile::messages.admin.timelines.settings_description') }}
                    </div>

                    @include('socialprofile::admin.timelines._settings_fields', ['timeline' => $timeline])

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            {{ __('socialprofile::messages.admin.timelines.actions.save') }}
                        </button>
                    </div>
                </form>
            @elseif($tab === 'periods')
                @include('socialprofile::admin.timelines.periods.index', [
                    'timeline' => $timeline,
                    'periods' => $periods,
                ])
            @elseif($tab === 'cards')
                @include('socialprofile::admin.timelines.cards.index', [
                    'timeline' => $timeline,
                    'periods' => $periods,
                    'cards' => $cards,
                    'filters' => $filters,
                ])
            @endif
        </div>
    </div>
</div>
@endsection

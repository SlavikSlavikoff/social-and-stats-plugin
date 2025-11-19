@extends('layouts.app')

@section('title', __('socialprofile::messages.court.title'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="mb-0">{{ __('socialprofile::messages.court.title') }}</h2>
                    <span class="badge bg-secondary">{{ __('socialprofile::messages.court.visibility_label') }}</span>
                </div>
                @can('social.court.judge')
                    <a href="{{ route('socialprofile.court.judge') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-gavel me-1"></i> {{ __('socialprofile::messages.court.judge.cta') }}
                    </a>
                @endcan
            </div>
            <form method="GET" class="mb-3">
                <input type="text" class="form-control" name="search" placeholder="{{ __('socialprofile::messages.court.history.search_placeholder') }}" value="{{ request('search') }}">
            </form>
            <div class="row">
                @foreach($cases as $case)
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary">{{ $case->case_number }}</span>
                                <span class="text-muted small">{{ $case->issued_at?->diffForHumans() }}</span>
                            </div>
                            <h5 class="mt-2">{{ $case->subject->name ?? '—' }}</h5>
                            <p class="text-muted">{{ \Illuminate\Support\Str::limit($case->comment, 140) }}</p>
                            <div class="d-flex flex-wrap gap-2 small">
                                <span class="badge bg-info">{{ __('socialprofile::messages.court.badges.judge') }} {{ $case->judge->name ?? '—' }}</span>
                                <span class="badge bg-light text-dark">{{ __('socialprofile::messages.court.badges.status') }} {{ $case->status }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                {{ $cases->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.court.history.title'))

@section('content')
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('socialprofile::messages.court.history.title') }}</h5>
    </div>
    <div class="card-body">
        <form class="row g-2 mb-4" method="GET">
            <div class="col-md-5">
                <input type="text" class="form-control" name="search" placeholder="{{ __('socialprofile::messages.court.history.search_placeholder') }}" value="{{ $filters['search'] }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">{{ __('socialprofile::messages.court.history.status_any') }}</option>
                    @foreach(['draft','issued','active','awaiting_revert','completed','cancelled','revoked'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">{{ __('socialprofile::messages.court.history.search') }}</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('socialprofile.admin.court.archive') }}" class="btn btn-outline-secondary w-100">{{ __('socialprofile::messages.court.history.reset') }}</a>
            </div>
        </form>

        <div class="row">
            @forelse($cases as $case)
                <div class="col-md-6 mb-3">
                    <div class="border rounded h-100 p-3 news-card">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-light text-dark">{{ $case->case_number }}</span>
                            <span class="text-muted small">{{ $case->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <h6 class="mt-2 mb-1">{{ $case->subject->name ?? '—' }}</h6>
                        <p class="mb-1 text-muted">{{ \Illuminate\Support\Str::limit($case->comment, 120) }}</p>
                        <div class="d-flex flex-wrap gap-2 small">
                            <span class="badge bg-primary">{{ __('socialprofile::messages.court.badges.judge') }} {{ $case->judge->name ?? '—' }}</span>
                            <span class="badge bg-secondary">{{ __('socialprofile::messages.court.badges.status') }} {{ $case->status }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">{{ __('socialprofile::messages.court.history.empty') }}</div>
                </div>
            @endforelse
        </div>

        <div class="mt-3">
            {{ $cases->links() }}
        </div>
    </div>
</div>
@endsection

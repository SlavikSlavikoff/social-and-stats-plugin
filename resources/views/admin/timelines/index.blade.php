@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.timelines.title'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0">{{ __('socialprofile::messages.admin.timelines.list_title') }}</h3>
                @if(count($types) > $timelines->count())
                    <a href="{{ route('socialprofile.admin.timelines.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> {{ __('socialprofile::messages.admin.timelines.actions.create') }}
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('socialprofile::messages.admin.timelines.fields.type') }}</th>
                                <th>{{ __('socialprofile::messages.admin.timelines.fields.title') }}</th>
                                <th>{{ __('socialprofile::messages.admin.timelines.fields.status') }}</th>
                                <th>{{ __('socialprofile::messages.admin.timelines.fields.periods') }}</th>
                                <th>{{ __('socialprofile::messages.admin.timelines.fields.cards') }}</th>
                                <th class="text-end">{{ __('socialprofile::messages.admin.timelines.fields.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($timelines as $timeline)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase">{{ $timeline->type }}</span>
                                    </td>
                                    <td>{{ $timeline->title }}</td>
                                    <td>
                                        @if($timeline->is_active)
                                            <span class="badge bg-success">{{ __('socialprofile::messages.admin.timelines.status.active') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('socialprofile::messages.admin.timelines.status.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $timeline->periods_count }}</td>
                                    <td>{{ $timeline->cards_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('socialprofile.admin.timelines.edit', $timeline) }}" class="btn btn-sm btn-outline-primary">
                                            {{ __('socialprofile::messages.admin.timelines.actions.manage') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">
                                        {{ __('socialprofile::messages.admin.timelines.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

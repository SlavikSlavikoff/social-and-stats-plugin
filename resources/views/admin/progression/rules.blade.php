@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.progression.rules.title', ['rating' => $rating->name]))

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <a href="{{ route('socialprofile.admin.progression.index') }}" class="btn btn-link mb-3">&larr; {{ __('socialprofile::messages.progression.back_to_ratings') }}</a>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="socialprofile-card">
                <h3>{{ __('socialprofile::messages.progression.rules.create') }}</h3>
                <form method="POST" action="{{ route('socialprofile.admin.progression.rules.store', $rating) }}">
                    @csrf
                    @include('socialprofile::admin.progression.partials.rule-form', ['rule' => null, 'triggers' => $triggers])
                    <button class="btn btn-primary mt-3">{{ __('socialprofile::messages.actions.create') }}</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            @forelse($rules as $rule)
                <div class="socialprofile-card mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $rule->name }}</h4>
                        <form method="POST" action="{{ route('socialprofile.admin.progression.rules.destroy', $rule) }}" onsubmit="return confirm('{{ __('socialprofile::messages.progression.rules.confirm_delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">{{ __('socialprofile::messages.actions.delete') }}</button>
                        </form>
                    </div>
                    <small class="text-muted">{{ __('socialprofile::messages.progression.rules.trigger') }}: {{ $rule->trigger_key }}</small>
                    <form method="POST" action="{{ route('socialprofile.admin.progression.rules.update', $rule) }}" class="mt-3">
                        @csrf
                        @method('PUT')
                        @include('socialprofile::admin.progression.partials.rule-form', ['rule' => $rule, 'triggers' => $triggers])
                        <button class="btn btn-primary mt-3">{{ __('socialprofile::messages.actions.update') }}</button>
                    </form>
                </div>
            @empty
                <div class="alert alert-info">{{ __('socialprofile::messages.progression.rules.empty') }}</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

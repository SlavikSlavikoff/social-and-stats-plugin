@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.timelines.create_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('socialprofile::messages.admin.timelines.create_title') }}</h3>
            </div>
            <div class="card-body">
                @if(empty($availableTypes))
                    <div class="alert alert-info mb-0">
                        {{ __('socialprofile::messages.admin.timelines.no_types_available') }}
                    </div>
                @else
                    <form method="POST" action="{{ route('socialprofile.admin.timelines.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="timeline-type" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.type') }}</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="timeline-type" name="type" required>
                                <option value="">{{ __('socialprofile::messages.admin.timelines.actions.select_type') }}</option>
                                @foreach($availableTypes as $type)
                                    <option value="{{ $type }}" @selected(old('type') === $type)>{{ __('socialprofile::messages.admin.timelines.types.'.$type) }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @include('socialprofile::admin.timelines._settings_fields', ['timeline' => $timeline])

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('socialprofile::messages.admin.timelines.actions.save') }}
                            </button>
                            <a href="{{ route('socialprofile.admin.timelines.index') }}" class="btn btn-outline-secondary">
                                {{ __('messages.actions.cancel') }}
                            </a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

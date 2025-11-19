<div class="mb-3">
    <label for="timeline-title" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.title') }}</label>
    <input type="text" class="form-control @error('title') is-invalid @enderror" id="timeline-title" name="title" value="{{ old('title', $timeline->title) }}" required placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.title') }}">
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="timeline-subtitle" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.subtitle') }}</label>
    <input type="text" class="form-control @error('subtitle') is-invalid @enderror" id="timeline-subtitle" name="subtitle" value="{{ old('subtitle', $timeline->subtitle) }}" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.subtitle') }}">
    @error('subtitle')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="timeline-intro" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.intro') }}</label>
    <textarea class="form-control @error('intro_text') is-invalid @enderror" id="timeline-intro" name="intro_text" rows="4" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.intro') }}">{{ old('intro_text', $timeline->intro_text) }}</textarea>
    @error('intro_text')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="timeline-active" name="is_active" value="1" {{ old('is_active', $timeline->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="timeline-active">{{ __('socialprofile::messages.admin.timelines.fields.is_active') }}</label>
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="timeline-period-labels" name="show_period_labels" value="1" {{ old('show_period_labels', $timeline->show_period_labels ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="timeline-period-labels">{{ __('socialprofile::messages.admin.timelines.fields.show_period_labels') }}</label>
</div>

<div class="mb-3">
    <label for="timeline-meta-title" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.meta_title') }}</label>
    <input type="text" class="form-control @error('meta_title') is-invalid @enderror" id="timeline-meta-title" name="meta_title" value="{{ old('meta_title', $timeline->meta_title) }}" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.meta_title') }}">
    @error('meta_title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="timeline-meta-description" class="form-label">{{ __('socialprofile::messages.admin.timelines.fields.meta_description') }}</label>
    <textarea class="form-control @error('meta_description') is-invalid @enderror" id="timeline-meta-description" name="meta_description" rows="3" placeholder="{{ __('socialprofile::messages.admin.timelines.placeholders.meta_description') }}">{{ old('meta_description', $timeline->meta_description) }}</textarea>
    @error('meta_description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

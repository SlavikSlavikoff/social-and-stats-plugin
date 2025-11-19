@extends('admin.layouts.admin')

@section('title', __('socialprofile::messages.admin.automation.title'))

@push('styles')
<link rel="stylesheet" href="{{ plugin_asset('socialprofile', 'css/style.css') }}">
<style>
    .automation-conditions .condition-panel { margin-bottom: 1.5rem; }
    .automation-action textarea { font-family: monospace; }
    .automation-tabs .nav-link { cursor: pointer; }
    .automation-doc pre { background: #f8f9fa; padding: 0.75rem; border-radius: 0.35rem; }
</style>
@endpush

@section('content')
<div class="socialprofile-admin">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <ul class="nav nav-tabs automation-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link @if($tab === 'rules') active @endif" href="{{ route('socialprofile.admin.automation.index', ['tab' => 'rules']) }}">
                {{ __('socialprofile::messages.admin.automation.tabs.rules') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($tab === 'integrations') active @endif" href="{{ route('socialprofile.admin.automation.index', ['tab' => 'integrations']) }}">
                {{ __('socialprofile::messages.admin.automation.tabs.integrations') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($tab === 'scheduler') active @endif" href="{{ route('socialprofile.admin.automation.index', ['tab' => 'scheduler']) }}">
                {{ __('socialprofile::messages.admin.automation.tabs.scheduler') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($tab === 'logs') active @endif" href="{{ route('socialprofile.admin.automation.index', ['tab' => 'logs']) }}">
                {{ __('socialprofile::messages.admin.automation.tabs.logs') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($tab === 'docs') active @endif" href="{{ route('socialprofile.admin.automation.index', ['tab' => 'docs']) }}">
                {{ __('socialprofile::messages.admin.automation.tabs.docs') }}
            </a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 bg-white p-4 rounded-bottom">
        <div class="tab-pane fade @if($tab === 'rules') show active @endif" id="automation-rules">
            <div class="socialprofile-card mb-4">
                <h4>{{ __('socialprofile::messages.admin.automation.rules.create_title') }}</h4>
                <form method="POST" action="{{ route('socialprofile.admin.automation.rules.store') }}" class="automation-rule-form">
                    @csrf
                    @include('socialprofile::admin.automation.partials.rule-form', [
                        'rule' => null,
                        'formId' => 'automation-rule-create',
                        'roles' => $roles,
                        'trustLevels' => $trustLevels,
                        'triggers' => $triggers,
                        'actionTypes' => $actionTypes,
                        'integrations' => $integrations,
                    ])
                </form>
            </div>
            @forelse($rules as $rule)
                <div class="socialprofile-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">{{ $rule->name }}</h5>
                        <form method="POST" action="{{ route('socialprofile.admin.automation.rules.destroy', $rule) }}" onsubmit="return confirm('{{ __('socialprofile::messages.admin.automation.rules.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link link-danger p-0">{{ __('socialprofile::messages.admin.automation.rules.delete') }}</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('socialprofile.admin.automation.rules.update', $rule) }}" class="automation-rule-form">
                        @csrf
                        @method('PUT')
                        @include('socialprofile::admin.automation.partials.rule-form', [
                            'rule' => $rule,
                            'formId' => 'automation-rule-'.$rule->id,
                            'roles' => $roles,
                            'trustLevels' => $trustLevels,
                            'triggers' => $triggers,
                            'actionTypes' => $actionTypes,
                            'integrations' => $integrations,
                        ])
                    </form>
                </div>
            @empty
                <div class="alert alert-info mb-0">{{ __('socialprofile::messages.admin.automation.rules.empty') }}</div>
            @endforelse
        </div>

        <div class="tab-pane fade @if($tab === 'integrations') show active @endif" id="automation-integrations">
            @php
                $integrationTypes = [
                    'minecraft_rcon' => __('socialprofile::messages.admin.automation.integrations.types.rcon'),
                    'minecraft_db' => __('socialprofile::messages.admin.automation.integrations.types.database'),
                    'social_bot' => __('socialprofile::messages.admin.automation.integrations.types.bot'),
                ];
            @endphp
            <div class="socialprofile-card mb-4">
                <h4>{{ __('socialprofile::messages.admin.automation.integrations.create_title') }}</h4>
                <form method="POST" action="{{ route('socialprofile.admin.automation.integrations.store') }}" class="automation-integration-form">
                    @csrf
                    <input type="hidden" name="form_id" value="automation-integration-create">
                    @include('socialprofile::admin.automation.partials.integration-form', [
                        'integration' => null,
                        'formId' => 'automation-integration-create',
                        'integrationTypes' => $integrationTypes,
                    ])
                    <div class="mt-3 text-end">
                        <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.automation.integrations.save') }}</button>
                    </div>
                </form>
            </div>
            @forelse($integrations as $integration)
                <div class="socialprofile-card mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h5 class="mb-0">{{ $integration->name }} <small class="text-muted">({{ $integration->type }})</small></h5>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('socialprofile.admin.automation.integrations.test', $integration) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" type="submit">
                                    {{ __('socialprofile::messages.admin.automation.integrations.test_button') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('socialprofile.admin.automation.integrations.destroy', $integration) }}" onsubmit="return confirm('{{ __('socialprofile::messages.admin.automation.integrations.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    {{ __('socialprofile::messages.admin.automation.integrations.delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('socialprofile.admin.automation.integrations.update', $integration) }}" class="automation-integration-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_id" value="automation-integration-{{ $integration->id }}">
                        @include('socialprofile::admin.automation.partials.integration-form', [
                            'integration' => $integration,
                            'formId' => 'automation-integration-'.$integration->id,
                            'integrationTypes' => $integrationTypes,
                        ])
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary">{{ __('socialprofile::messages.admin.automation.integrations.save') }}</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="alert alert-info mb-0">{{ __('socialprofile::messages.admin.automation.integrations.empty') }}</div>
            @endforelse
        </div>

        <div class="tab-pane fade @if($tab === 'scheduler') show active @endif" id="automation-scheduler">
            <div class="socialprofile-card">
                <h4>{{ __('socialprofile::messages.admin.automation.scheduler.title') }}</h4>
                <form method="POST" action="{{ route('socialprofile.admin.automation.scheduler.update') }}">
                    @csrf
                    <input type="hidden" name="form_id" value="automation-scheduler">
                    <div class="row g-3">
                        <div class="col-md-3 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" name="enabled" value="1" @checked($scheduler['enabled'])>
                                <label class="form-check-label">{{ __('socialprofile::messages.admin.automation.scheduler.enabled') }}</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('socialprofile::messages.admin.automation.scheduler.day') }}</label>
                            <input type="number" min="1" max="28" class="form-control" name="day" value="{{ $scheduler['day'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('socialprofile::messages.admin.automation.scheduler.hour') }}</label>
                            <input type="number" min="0" max="23" class="form-control" name="hour" value="{{ $scheduler['hour'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('socialprofile::messages.admin.automation.scheduler.top_limit') }}</label>
                            <input type="number" min="1" max="50" class="form-control" name="top_limit" value="{{ $scheduler['top_limit'] }}">
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('socialprofile::messages.admin.automation.scheduler.sources') }}</label>
                            <select class="form-select" name="sources[]" multiple size="4">
                                @php $sourceValues = $scheduler['sources']; @endphp
                                <option value="social_score" @selected(in_array('social_score', $sourceValues, true))>{{ __('socialprofile::messages.metrics.social_score') }}</option>
                                <option value="activity" @selected(in_array('activity', $sourceValues, true))>{{ __('socialprofile::messages.metrics.activity') }}</option>
                                <option value="coins" @selected(in_array('coins', $sourceValues, true))>{{ __('socialprofile::messages.metrics.coins') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('socialprofile::messages.admin.automation.scheduler.rewards') }}</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="reward[social_score]" value="{{ $scheduler['reward']['social_score'] ?? 0 }}">
                                    <small class="text-muted">{{ __('socialprofile::messages.metrics.social_score') }}</small>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.1" class="form-control" name="reward[coins]" value="{{ $scheduler['reward']['coins'] ?? 0 }}">
                                    <small class="text-muted">{{ __('socialprofile::messages.metrics.coins') }}</small>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="reward[activity]" value="{{ $scheduler['reward']['activity'] ?? 0 }}">
                                    <small class="text-muted">{{ __('socialprofile::messages.metrics.activity') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">{{ __('socialprofile::messages.admin.automation.scheduler.last_run', ['date' => $lastSchedulerRun ?? __('socialprofile::messages.admin.automation.scheduler.never')]) }}</small>
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-primary" type="submit">{{ __('socialprofile::messages.admin.automation.scheduler.save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="tab-pane fade @if($tab === 'logs') show active @endif" id="automation-logs">
            <div class="socialprofile-card mb-3">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="logs">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('socialprofile::messages.admin.automation.logs.filter_trigger') }}</label>
                        <select class="form-select" name="trigger">
                            <option value="">{{ __('socialprofile::messages.admin.automation.logs.all_triggers') }}</option>
                            @foreach($triggers as $key => $info)
                                <option value="{{ $key }}" @selected($logFilters['trigger'] === $key)>{{ $info['label'] ?? $key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('socialprofile::messages.admin.automation.logs.filter_status') }}</label>
                        <select class="form-select" name="status">
                            <option value="">{{ __('socialprofile::messages.admin.automation.logs.all_statuses') }}</option>
                            <option value="success" @selected($logFilters['status'] === 'success')>success</option>
                            <option value="error" @selected($logFilters['status'] === 'error')>error</option>
                            <option value="skipped" @selected($logFilters['status'] === 'skipped')>skipped</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-secondary" type="submit">{{ __('socialprofile::messages.admin.automation.logs.filter_submit') }}</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('socialprofile::messages.admin.automation.logs.date') }}</th>
                            <th>{{ __('socialprofile::messages.admin.automation.logs.trigger') }}</th>
                            <th>{{ __('socialprofile::messages.admin.automation.logs.rule') }}</th>
                            <th>{{ __('socialprofile::messages.admin.automation.logs.status') }}</th>
                            <th>{{ __('socialprofile::messages.admin.automation.logs.details') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td>{{ $triggers[$log->trigger_type]['label'] ?? $log->trigger_type }}</td>
                                <td>{{ $log->rule?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge @class([
                                        'bg-success' => $log->status === 'success',
                                        'bg-danger' => $log->status === 'error',
                                        'bg-secondary' => $log->status !== 'success' && $log->status !== 'error',
                                    ])">{{ $log->status }}</span>
                                </td>
                                <td>
                                    @if($log->actions)
                                        <ul class="mb-0 ps-3 small">
                                            @foreach($log->actions as $action)
                                                <li>{{ $action['type'] ?? 'action' }} — {{ $action['summary'] ?? ($action['message'] ?? '') }}</li>
                                            @endforeach
                                        </ul>
                                    @elseif($log->error)
                                        <span class="text-danger small">{{ $log->error }}</span>
                                    @else
                                        <span class="text-muted small">{{ __('socialprofile::messages.admin.automation.logs.no_actions') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($log->rule_id)
                                        <form method="POST" action="{{ route('socialprofile.admin.automation.logs.replay', $log) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('socialprofile::messages.admin.automation.logs.replay') }}</button>
                                        </form>
                                    @else
                                        <span class="text-muted">{{ __('socialprofile::messages.admin.automation.logs.replay_disabled') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('socialprofile::messages.admin.automation.logs.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade @if($tab === 'docs') show active @endif" id="automation-docs">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="socialprofile-card automation-doc">
                        <h4>{{ __('socialprofile::messages.admin.automation.docs.title') }}</h4>
                        @if($documentation)
                            {!! $documentation !!}
                        @else
                            <p class="text-muted">{{ __('socialprofile::messages.admin.automation.docs.empty') }}</p>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="socialprofile-card mb-4">
                        <h5>{{ __('socialprofile::messages.admin.automation.docs.placeholders') }}</h5>
                        <ul class="list-unstyled mb-0 small">
                            @foreach($placeholders as $placeholder => $description)
                                <li><code>{{ $placeholder }}</code> — {{ $description }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="socialprofile-card mb-4">
                        <h5>{{ __('socialprofile::messages.admin.automation.docs.triggers') }}</h5>
                        <ul class="list-unstyled small mb-0">
                            @foreach($triggers as $key => $info)
                                <li><strong>{{ $info['label'] ?? $key }}</strong><br><span class="text-muted">{{ $info['description'] ?? '' }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="socialprofile-card">
                        <h5>{{ __('socialprofile::messages.admin.automation.docs.actions') }}</h5>
                        <ul class="list-unstyled small mb-0">
                            @foreach($actionTypes as $key => $info)
                                <li><strong>{{ $info['label'] ?? $key }}</strong><br><span class="text-muted">{{ $info['description'] ?? '' }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <template id="automation-action-template">
        @php
            $templateIntegrations = $integrations;
        @endphp
        {!! str_replace('__INDEX__', '${index}', view('socialprofile::admin.automation.partials.action-fields', [
            'index' => '__INDEX__',
            'action' => [],
            'integrations' => $integrations,
            'actionTypes' => $actionTypes,
            'roles' => $roles,
        ])->render()) !!}
    </template>
@endonce
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rulesForms = document.querySelectorAll('.automation-rule-form');

        rulesForms.forEach(form => {
            form.querySelectorAll('[data-trigger-select]').forEach(select => {
                select.addEventListener('change', () => updateConditions(select));
                updateConditions(select);
            });
        });

        document.querySelectorAll('.automation-action-add').forEach(button => {
            button.addEventListener('click', () => {
                const container = button.closest('.automation-actions');
                const nextIndex = parseInt(container.dataset.nextIndex, 10) || 0;
                const template = document.getElementById('automation-action-template').innerHTML;
                const html = template.replace(/\$\{index\}/g, nextIndex);
                container.dataset.nextIndex = nextIndex + 1;
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                const block = wrapper.firstElementChild;
                container.querySelector('.automation-actions__items').appendChild(block);
                bindActionBlock(block);
            });
        });

        document.querySelectorAll('.automation-action').forEach(bindActionBlock);
        document.querySelectorAll('.automation-integration-form').forEach(bindIntegrationForm);
    });

    function updateConditions(select) {
        const trigger = select.value;
        const container = select.closest('.automation-rule-form') ?? select.closest('.socialprofile-card');
        container.querySelectorAll('.condition-panel').forEach(panel => {
            panel.style.display = panel.dataset.condition === trigger ? '' : 'none';
        });
    }

    function bindActionBlock(block) {
        block.querySelectorAll('[data-action-type]').forEach(select => {
            select.addEventListener('change', () => updateActionFields(block, select.value));
            updateActionFields(block, select.value);
        });

        const removeButton = block.querySelector('[data-action-remove]');
        if (removeButton) {
            removeButton.addEventListener('click', () => block.remove());
        }
    }

    function updateActionFields(block, type) {
        block.querySelectorAll('.action-fields').forEach(field => {
            field.style.display = field.dataset.actionTypeFields === type ? '' : 'none';
        });
    }

    function bindIntegrationForm(form) {
        const select = form.querySelector('[name=\"type\"]');
        if (!select) {
            return;
        }
        const update = () => {
            const type = select.value;
            form.querySelectorAll('[data-integration-fields]').forEach(group => {
                group.style.display = group.dataset.integrationFields === type ? '' : 'none';
            });
        };
        select.addEventListener('change', update);
        update();
    }
</script>
@endpush
@endsection

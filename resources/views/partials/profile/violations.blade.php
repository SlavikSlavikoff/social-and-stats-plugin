@if($violations->isEmpty())
    <p class="text-muted mb-0">{{ __('socialprofile::messages.profile.modals.empty_violations') }}</p>
@else
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>{{ __('socialprofile::messages.profile.type') }}</th>
                <th>{{ __('socialprofile::messages.profile.reason') }}</th>
                <th>{{ __('socialprofile::messages.profile.points') }}</th>
                <th>{{ __('socialprofile::messages.profile.date') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($violations as $violation)
                <tr>
                    <td>{{ __('socialprofile::messages.violations.types.' . $violation->type) }}</td>
                    <td>{{ $violation->reason }}</td>
                    <td>{{ $violation->points }}</td>
                    <td>{{ $violation->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

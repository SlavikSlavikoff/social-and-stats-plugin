<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\CourtAttachment;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Support\Court\CourtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourtDecisionsController extends Controller
{
    public function __construct(
        protected CourtService $courtService
    ) {
    }

    public function storeAuto(Request $request): RedirectResponse
    {
        $commentMax = (int) (config('socialprofile.court.limits.comment_max') ?? 5000);
        $defaultExecutor = config('socialprofile.court.default_executor', 'site');

        $data = $request->validate([
            'subject' => 'required|string|exists:users,name',
            'template_key' => 'required|string|exists:socialprofile_court_templates,key',
            'comment' => ['nullable', 'string', 'max:'.$commentMax],
            'continued_case_id' => 'nullable|exists:socialprofile_court_cases,id',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'nullable|url|max:2048',
        ]);

        $subject = User::where('name', $data['subject'])->firstOrFail();
        $judge = $request->user();
        $data['executor'] = $defaultExecutor;

        $case = $this->courtService->issueFromTemplate($judge, $subject, $data);
        $this->storeAttachments($case, $data['attachments'] ?? []);

        return redirect()->route('socialprofile.court.judge')
            ->with('status', __('socialprofile::messages.court.flash.issued'));
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $commentMax = (int) (config('socialprofile.court.limits.comment_max') ?? 5000);
        $defaultExecutor = config('socialprofile.court.default_executor', 'site');

        $data = $request->validate([
            'subject' => 'required|string|exists:users,name',
            'comment' => ['required', 'string', 'max:'.$commentMax],
            'continued_case_id' => 'nullable|exists:socialprofile_court_cases,id',
            'punishment' => 'nullable|array',
            'punishment.socialrating' => 'nullable|integer',
            'punishment.activity' => 'nullable|integer',
            'punishment.coins' => 'nullable|integer',
            'punishment.money' => 'nullable|integer',
            'ban.duration' => 'nullable|string',
            'mute.duration' => 'nullable|string',
            'unverify' => 'nullable|boolean',
            'role.role_id' => 'nullable|exists:roles,id',
            'role.duration' => 'nullable|string',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'nullable|url|max:2048',
        ]);

        $subject = User::where('name', $data['subject'])->firstOrFail();
        $judge = $request->user();
        $data['executor'] = $defaultExecutor;

        $case = $this->courtService->issueManual($judge, $subject, $data);
        $this->storeAttachments($case, $data['attachments'] ?? []);

        return redirect()->route('socialprofile.court.judge')
            ->with('status', __('socialprofile::messages.court.flash.issued'));
    }

    protected function storeAttachments(CourtCase $case, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (empty($attachment)) {
                continue;
            }

            CourtAttachment::create([
                'case_id' => $case->id,
                'type' => 'link',
                'path' => $attachment,
            ]);
        }
    }
}

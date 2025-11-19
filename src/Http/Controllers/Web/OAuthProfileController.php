<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthFlowType;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OAuthProfileController extends Controller
{
    public function __construct(
        private readonly OAuthManager $oauthManager,
        private readonly OAuthAccountService $accountService,
    ) {
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_unless($request->user(), 403);
        abort_unless($this->oauthManager->supports($provider), 404);

        $returnUrl = $request->input('redirect', url()->previous() ?: url('/'));
        $request->session()->put('socialprofile.oauth.link_return_url', $returnUrl);

        $authorizationUrl = $this->oauthManager->getAuthorizationUrl($provider, OAuthFlowType::LINK, [
            'user_id' => $request->user()->id,
        ]);

        return redirect()->away($authorizationUrl);
    }

    public function destroy(Request $request, string $provider): RedirectResponse
    {
        abort_unless($request->user(), 403);
        abort_unless($this->oauthManager->supports($provider), 404);

        $this->accountService->unlinkProviderFromUser($provider, $request->user());

        return back()->with('success', __('OAuth аккаунт успешно отвязан.'));
    }
}

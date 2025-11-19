<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthFlowType;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OAuthLoginController extends Controller
{
    public function __construct(private readonly OAuthManager $oauthManager)
    {
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->oauthManager->supports($provider), 404);

        $returnUrl = $request->input('redirect', url()->previous() ?: url('/'));
        $request->session()->put('socialprofile.oauth.login_return_url', $returnUrl);

        $authorizationUrl = $this->oauthManager->getAuthorizationUrl($provider, OAuthFlowType::WEB_LOGIN);

        return redirect()->away($authorizationUrl);
    }
}

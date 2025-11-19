<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthCallbackResult;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Exceptions\OAuthException;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthAccountService;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthFlowType;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\OAuthManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class OAuthCallbackController extends Controller
{
    public function __construct(
        private readonly OAuthManager $oauthManager,
        private readonly OAuthAccountService $accountService,
    ) {
    }

    public function __invoke(Request $request, string $provider)
    {
        abort_unless($this->oauthManager->supports($provider), 404);

        if ($request->filled('error')) {
            return $this->handleProviderError($request, $provider);
        }

        $code = $request->string('code');
        $state = $request->string('state');

        if ($code === '' || $state === '') {
            return $this->redirectWithError($request, 'Не удалось завершить OAuth авторизацию.');
        }

        try {
            $result = $this->oauthManager->handleCallback($provider, $code, $state);
        } catch (OAuthException|InvalidArgumentException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }

        return match ($result->flowType) {
            OAuthFlowType::LINK => $this->handleLinkFlow($request, $result),
            OAuthFlowType::WEB_LOGIN => $this->handleWebLoginFlow($request, $result),
            OAuthFlowType::LAUNCHER_LOGIN => $this->handleLauncherFlow($result),
            default => $this->redirectWithError($request, 'Неизвестный тип OAuth авторизации.'),
        };
    }

    private function handleLinkFlow(Request $request, OAuthCallbackResult $result): RedirectResponse
    {
        $user = $request->user();
        $expectedUserId = $result->context['user_id'] ?? null;

        if ($user === null || $user->id !== $expectedUserId) {
            return $this->redirectWithError($request, 'Сессия авторизации недействительна.');
        }

        $this->accountService->linkProviderToUser($result->provider, $result->user, $user, $result->accessToken);

        return $this->redirectToLinkTarget($request)
            ->with('success', __('OAuth аккаунт успешно привязан.'));
    }

    private function handleWebLoginFlow(Request $request, OAuthCallbackResult $result): RedirectResponse
    {
        $user = $this->accountService->loginWithOAuth($result->user);

        if ($user === null) {
            return $this->redirectToLoginTarget($request)
                ->withErrors(['oauth' => 'Аккаунт ещё не привязан. Войдите через логин и пароль и привяжите OAuth в профиле.']);
        }

        Auth::login($user, true);

        return $this->redirectToLoginTarget($request)
            ->with('success', __('Вы успешно вошли через OAuth.'));
    }

    private function handleLauncherFlow(OAuthCallbackResult $result): View
    {
        $session = $this->accountService->handleLauncherCallback($result);
        $view = config('socialprofile.oauth.launcher.result_view', 'socialprofile::oauth.launcher-result');

        return view($view, [
            'session' => $session,
        ]);
    }

    private function handleProviderError(Request $request, string $provider)
    {
        $message = $request->string('error_description', 'Авторизация отменена')->toString();
        $state = $request->string('state')->toString();

        if ($state !== '') {
            $storedState = $this->oauthManager->resolveState($state);

            if ($storedState !== null && $storedState->flowType === OAuthFlowType::LAUNCHER_LOGIN) {
                $sessionId = $storedState->context['login_session_id'] ?? null;

                if ($sessionId !== null) {
                    $session = $this->accountService->failLauncherSession($sessionId, 'provider_error');

                    $view = config('socialprofile.oauth.launcher.result_view', 'socialprofile::oauth.launcher-result');

                    return view($view, ['session' => $session]);
                }
            }
        }

        return $this->redirectWithError($request, "Провайдер {$provider} вернул ошибку: ".$message);
    }

    private function redirectWithError(Request $request, string $message): RedirectResponse
    {
        if ($request->user()) {
            return $this->redirectToLinkTarget($request)->withErrors(['oauth' => $message]);
        }

        return $this->redirectToLoginTarget($request)->withErrors(['oauth' => $message]);
    }

    private function redirectToLinkTarget(Request $request): RedirectResponse
    {
        $target = $request->session()->pull('socialprofile.oauth.link_return_url', url('/'));

        return redirect()->to($target);
    }

    private function redirectToLoginTarget(Request $request): RedirectResponse
    {
        $target = $request->session()->pull('socialprofile.oauth.login_return_url', url('/'));

        return redirect()->to($target);
    }
}

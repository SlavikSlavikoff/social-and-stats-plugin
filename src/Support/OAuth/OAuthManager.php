<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthCallbackResult;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\StoredState;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Exceptions\OAuthException;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\State\StateStoreInterface;
use InvalidArgumentException;

class OAuthManager
{
    /**
     * @param  array<string, OAuthProviderInterface>  $providers
     */
    public function __construct(
        private readonly StateStoreInterface $stateStore,
        private array $providers = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function getAuthorizationUrl(string $provider, string $flowType, array $context = []): string
    {
        OAuthFlowType::assertValid($flowType);
        $driver = $this->getProvider($provider);
        $state = $this->stateStore->generateAndStore($provider, $flowType, $context);

        return $driver->getAuthorizationUrl($state, $context);
    }

    public function handleCallback(string $provider, string $code, string $state): OAuthCallbackResult
    {
        $driver = $this->getProvider($provider);
        $storedState = $this->resolveState($state);

        if ($storedState === null || $storedState->provider !== $provider) {
            throw new OAuthException('Invalid or expired OAuth state.');
        }

        $token = $driver->getToken($code);
        $user = $driver->getUserInfo($token);

        return new OAuthCallbackResult(
            $provider,
            $storedState->flowType,
            $storedState->context,
            $token,
            $user,
        );
    }

    public function resolveState(string $state): ?StoredState
    {
        return $this->stateStore->get($state);
    }

    public function supports(string $provider): bool
    {
        return array_key_exists($provider, $this->providers);
    }

    public function setProviders(array $providers): void
    {
        $this->providers = $providers;
    }

    private function getProvider(string $provider): OAuthProviderInterface
    {
        if (! $this->supports($provider)) {
            throw new InvalidArgumentException("Unknown OAuth provider [{$provider}].");
        }

        return $this->providers[$provider];
    }
}

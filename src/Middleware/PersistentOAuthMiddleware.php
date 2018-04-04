<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Middleware;

use Sainsburys\Guzzle\Oauth2\AccessToken;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use GuzzleHttp\ClientInterface;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PersistentOAuthMiddleware extends OAuthMiddleware
{
    /** @var SessionInterface */
    protected $session;

    /** @var string */
    protected $clientName;

    /**
     * Create a new Oauth2 subscriber.
     *
     * @param ClientInterface $client
     * @param GrantTypeInterface $grantType
     * @param RefreshTokenGrantTypeInterface $refreshTokenGrantType
     * @param SessionInterface $session
     * @param string $clientName
     */
    public function __construct(
        ClientInterface $client,
        GrantTypeInterface $grantType = null,
        RefreshTokenGrantTypeInterface $refreshTokenGrantType = null,
        SessionInterface $session,
        string $clientName
    ) {
        parent::__construct($client, $grantType, $refreshTokenGrantType);

        $this->session = $session;
        $this->clientName = $clientName;

        $this->restoreTokenFromSession();
    }

    /**
     * Get a new access token.
     *
     * @return AccessToken|null
     */
    protected function acquireAccessToken()
    {
        $token = parent::acquireAccessToken();

        $this->storeTokenInSession($token);

        return $token;
    }

    /**
     * @param AccessToken $token
     */
    protected function storeTokenInSession(AccessToken $token)
    {
        $this->session->start();
        $this->session->set($this->clientName . '_token', [
            'token' => $token->getToken(),
            'type' => $token->getType(),
            'data' => $token->getData(),
        ]);
        $this->session->save();
    }

    protected function restoreTokenFromSession()
    {
        if ($this->session->has($this->clientName . '_token')) {
            $sessionTokenData = $this->session->get($this->clientName . '_token');

            $this->setAccessToken(new AccessToken(
                $sessionTokenData['token'],
                $sessionTokenData['type'],
                $sessionTokenData['data']
            ));
        }
    }
}

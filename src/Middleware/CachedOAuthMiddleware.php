<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Middleware;

use GuzzleHttp\ClientInterface;
use Psr\Cache\InvalidArgumentException;
use Sainsburys\Guzzle\Oauth2\AccessToken;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CachedOAuthMiddleware extends OAuthMiddleware
{
    /**
     * @var AdapterInterface cacheClient
     */
    private $cacheClient;

    /**
     * @var string clientName
     */
    private $clientName;

    /**
     * Create a new Oauth2 subscriber.
     *
     * @param ClientInterface $client
     * @param GrantTypeInterface|null $grantType
     * @param RefreshTokenGrantTypeInterface|null $refreshTokenGrantType
     * @param AdapterInterface $cacheClient
     * @param string $clientName
     */
    public function __construct(
        ClientInterface $client,
        ?GrantTypeInterface $grantType = null,
        ?RefreshTokenGrantTypeInterface $refreshTokenGrantType = null,
        AdapterInterface $cacheClient,
        string $clientName
    ) {
        parent::__construct($client, $grantType, $refreshTokenGrantType);

        $this->cacheClient = $cacheClient;
        $this->clientName = $clientName;
    }

    /**
     * Get a new access token.
     *
     * @throws InvalidArgumentException
     *
     * @return AccessToken|null
     */
    protected function acquireAccessToken()
    {
        $token = parent::acquireAccessToken();

        $this->cacheToken($token);

        return $token;
    }

    /**
     * cacheToken sets the token in the cache adapter
     *
     * @param AccessToken $token
     *
     * @throws InvalidArgumentException
     */
    protected function cacheToken(AccessToken $token)
    {
        $item = $this->cacheClient->getItem(sprintf('oauth.token.%s', $this->clientName));

        $item->set(
            [
                'token' => $token->getToken(),
                'type' => $token->getType(),
                'data' => $token->getData(),
            ]
        );
        $item->tag('oauth');
        $expires = $token->getExpires();

        if ($expires) {
            $item->expiresAt($expires->sub(\DateInterval::createFromDateString('1 minute')));
        }

        $this->cacheClient->saveDeferred($item);
    }

    /**
     * getAccessToken will get the oauth token from the cache if available
     *
     * @throws \Exception
     * @throws InvalidArgumentException
     *
     * @return null|AccessToken
     */
    public function getAccessToken()
    {
        if ($this->accessToken === null) {
            $this->restoreTokenFromCache();
        }

        return parent::getAccessToken();
    }

    /**
     * restoreTokenFromCache
     *
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    protected function restoreTokenFromCache()
    {
        $item = $this->cacheClient->getItem(sprintf('oauth.token.%s', $this->clientName));

        if ($item->isHit()) {
            $tokenData = $item->get();

            $this->setAccessToken(
                new AccessToken(
                    $tokenData['token'],
                    $tokenData['type'],
                    $tokenData['data']
                )
            );
        }
    }
}

<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GrantType;

use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Token\AccessToken;

interface GrantTypeInterface
{
    /**
     * Get the token data returned by the OAuth2 server.
     *
     * @return AccessToken
     */
    public function getToken();

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getConfigByName($name);

    /**
     * @return array
     */
    public function getConfig();
}

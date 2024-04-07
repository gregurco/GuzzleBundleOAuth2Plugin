# Guzzle Bundle OAuth2 Plugin

[![Build Status](https://travis-ci.org/gregurco/GuzzleBundleOAuth2Plugin.svg?branch=master)](https://travis-ci.org/gregurco/GuzzleBundleOAuth2Plugin)
[![Coverage Status](https://coveralls.io/repos/gregurco/GuzzleBundleOAuth2Plugin/badge.svg?branch=master)](https://coveralls.io/r/gregurco/GuzzleBundleOAuth2Plugin)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/eba4f2e6-2c2a-4e92-85b6-c32ab3ac3aa7/mini.png)](https://insight.sensiolabs.com/projects/eba4f2e6-2c2a-4e92-85b6-c32ab3ac3aa7)

This plugin integrates [OAuth2][1] functionality into [Guzzle Bundle][2], a bundle for building RESTful web service clients.

----

## Prerequisites
 - PHP 7.2 or above
 - [Guzzle Bundle][2]
 - [guzzle-oauth2-plugin][3]

## Installation

To install this bundle, run the command below on the command line and you will get the latest stable version from [Packagist][4].

``` bash
composer require gregurco/guzzle-bundle-oauth2-plugin
```

## Usage

### Enable bundle

Find next lines in `src/Kernel.php`:

```php
foreach ($contents as $class => $envs) {
    if (isset($envs['all']) || isset($envs[$this->environment])) {
        yield new $class();
    }
}
```

and replace them by:

```php
foreach ($contents as $class => $envs) {
    if (isset($envs['all']) || isset($envs[$this->environment])) {
        if ($class === \EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundle::class) {
            yield new $class([
                new \Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GuzzleBundleOAuth2Plugin(),
            ]);
        } else {
            yield new $class();
        }
    }
}
```

### Basic configuration

#### With default grant type (client)

``` yaml
# app/config/config.yml

eight_points_guzzle:
    clients:
        api_payment:
            base_url: "http://api.domain.tld"
            
            options:
                auth: oauth2

            # plugin settings
            plugin:
                oauth2:
                    base_uri:       "https://example.com"
                    token_url:      "/oauth/token"
                    client_id:      "test-client-id"
                    client_secret:  "test-client-secret" # optional
                    scope:          "administration"
```

#### With password grant type

``` yaml
# app/config/config.yml

eight_points_guzzle:
    clients:
        api_payment:
            base_url: "http://api.domain.tld"
            
            options:
                auth: oauth2

            # plugin settings
            plugin:
                oauth2:
                    base_uri:       "https://example.com"
                    token_url:      "/oauth/token"
                    client_id:      "test-client-id"
                    username:       "johndoe"
                    password:       "A3ddj3w"
                    scope:          "administration"
                    grant_type:     "Sainsburys\\Guzzle\\Oauth2\\GrantType\\PasswordCredentials"
```

#### With client credentials in body

``` yaml
# app/config/config.yml

eight_points_guzzle:
    clients:
        api_payment:
            base_url: "http://api.domain.tld"
            
            options:
                auth: oauth2

            # plugin settings
            plugin:
                oauth2:
                    base_uri:       "https://example.com"
                    token_url:      "/oauth/token"
                    client_id:      "test-client-id"
                    scope:          "administration"
                    auth_location:  "body"
```

### Options

| Key | Description | Required | Example |
| --- | --- | --- | --- |
| base_uri | URL of oAuth2 server.| yes | https://example.com |
| token_url | The path that will be concatenated with base_uri. <br/>Default: `/oauth2/token`| no | /oauth/token |
| client_id | The client identifier issued to the client during the registration process | yes | s6BhdRkqt3 |
| client_secret | The client secret | no | 7Fjfp0ZBr1KtDRbnfVdmIw |
| username | The resource owner username | for PasswordCredentials grant type | johndoe |
| password | The resource owner password | for PasswordCredentials grant type | A3ddj3w |
| auth_location | The place where to put client_id and client_secret in auth request. <br/>Default: headers. Allowed values: body, headers. | no | body |
| resource | The App ID URI of the web API (secured resource) | no | https://service.contoso.com/ |
| private_key | Path to private key | for JwtBearer grant type | `"%kernel.root_dir%/path/to/private.key"` |
| scope | One or more scope values indicating which parts of the user's account you wish to access | no | administration |
| audience | | no | |
| grant_type | Grant type class path. Class should implement GrantTypeInterface. <br/> Default: `Sainsburys\\Guzzle\\Oauth2\\GrantType\\ClientCredentials` | no | `Sainsburys\\Guzzle\\Oauth2\\GrantType\\PasswordCredentials`<br/>`Sainsburys\\Guzzle\\Oauth2\\GrantType\\AuthorizationCode`<br/>`Sainsburys\\Guzzle\\Oauth2\\GrantType\\JwtBearer` |
| persistent | Token will be stored in session unless grant_type is client credentials; in which case it will be stored in the app cache. <br/> Default: false | no | |
| retry_limit | How many times request will be repeated on failure. <br/> Default: 5 | no | |

See more information about middleware [here][3].

## License

This middleware is licensed under the MIT License - see the LICENSE file for details

[1]: http://www.xml.com/pub/a/2003/12/17/dive.html
[2]: https://github.com/8p/EightPointsGuzzleBundle
[3]: https://github.com/Sainsburys/guzzle-oauth2-plugin
[4]: https://packagist.org/packages/gregurco/guzzle-bundle-oauth2-plugin

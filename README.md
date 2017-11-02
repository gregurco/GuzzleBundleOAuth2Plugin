# Guzzle Bundle OAuth2 Plugin


[![Build Status](https://travis-ci.org/gregurco/GuzzleBundleOAuth2Plugin.svg?branch=master)](https://travis-ci.org/gregurco/GuzzleBundleOAuth2Plugin) [![Coverage Status](https://coveralls.io/repos/gregurco/GuzzleBundleOAuth2Plugin/badge.svg?branch=master)](https://coveralls.io/r/gregurco/GuzzleBundleOAuth2Plugin)

This plugin integrates [OAuth2][1] functionality into Guzzle Bundle, a bundle for building RESTful web service clients.


## Requirements
------------
 - PHP 7.0 or above
 - [Guzzle Bundle][2]
 - [guzzle-oauth2-plugin][3]

 
## Installation
Using [composer][3]:

##### composer.json
``` json
{
    "require": {
        "gregurco/guzzle-bundle-oauth2-plugin": "dev-master"
    }
}
```

##### command line
``` bash
$ composer require gregurco/guzzle-bundle-oauth2-plugin
```

## Usage
### Enable bundle
``` php
# app/AppKernel.php

new EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundle([
    new Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GuzzleBundleOAuth2Plugin(),
])

### Basic configuration
``` yaml
# app/config/config.yml

eight_points_guzzle:
    clients:
        api_payment:
            base_url: "http://api.domain.tld"
            
            auth: oauth2

            # plugin settings
            plugin:
                oauth2:
                    base_uri:       "https://example.com"
                    token_url:      "/oauth/token"
                    username:       "test@example.com"
                    password:       "pa55w0rd"
                    client_id:      "test-client-id"
                    client_secret:  "test-client-secret"
                    scope:          "administration"
```

## License
This middleware is licensed under the MIT License - see the LICENSE file for details

[1]: http://www.xml.com/pub/a/2003/12/17/dive.html
[2]: https://github.com/8p/EightPointsGuzzleBundle
[3]: https://github.com/Sainsburys/guzzle-oauth2-plugin
[4]: https://getcomposer.org/

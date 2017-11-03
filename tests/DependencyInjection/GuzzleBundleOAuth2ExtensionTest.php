<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Test\DependencyInjection;

use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\DependencyInjection\GuzzleBundleOAuth2Extension;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class GuzzleBundleOAuth2ExtensionTest extends TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();

        $extension = new GuzzleBundleOAuth2Extension();
        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('guzzle_bundle_oauth2_plugin.middleware.class'));
        $this->assertEquals(
            OAuthMiddleware::class,
            $container->getParameter('guzzle_bundle_oauth2_plugin.middleware.class')
        );
    }
}

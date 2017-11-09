<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Test;

use EightPoints\Bundle\GuzzleBundle\DependencyInjection\Configuration;
use EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundlePlugin;
use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GuzzleBundleOAuth2Plugin;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use PHPUnit\Framework\TestCase;

class GuzzleBundleOAuth2PluginTest extends TestCase
{
    /** @var GuzzleBundleOAuth2Plugin */
    protected $plugin;

    public function setUp()
    {
        parent::setUp();

        $this->plugin = new GuzzleBundleOAuth2Plugin();
    }

    public function testSubClassesOfPlugin()
    {
        $this->assertInstanceOf(EightPointsGuzzleBundlePlugin::class, $this->plugin);
        $this->assertInstanceOf(Bundle::class, $this->plugin);
    }

    public function testAddConfiguration()
    {
        $arrayNode = new ArrayNodeDefinition('node');

        $this->plugin->addConfiguration($arrayNode);

        $node = $arrayNode->getNode();

        $this->assertFalse($node->isRequired());
        $this->assertTrue($node->hasDefaultValue());
        $this->assertSame(
            [
                'enabled' => false,
                'base_uri' => null,
                'username' => null,
                'password' => null,
                'client_id' => null,
                'client_secret' => null,
                'token_url' => null,
                'scope' => null,
                'resource' => null,
                'auth_location' => 'headers',
                'grant_type' => PasswordCredentials::class,
            ],
            $node->getDefaultValue()
        );
    }

    public function testGetPluginName()
    {
        $this->assertEquals('oauth2', $this->plugin->getPluginName());
    }

    public function testLoad()
    {
        $container = new ContainerBuilder();

        $this->plugin->load([], $container);

        $this->assertTrue($container->hasParameter('guzzle_bundle_oauth2_plugin.middleware.class'));
        $this->assertEquals(
            OAuthMiddleware::class,
            $container->getParameter('guzzle_bundle_oauth2_plugin.middleware.class')
        );
    }

    public function testLoadForClient()
    {
        $handler = new Definition();
        $container = new ContainerBuilder();

        $this->plugin->loadForClient(
            [
                'enabled' => true,
                'base_uri' => 'https://example.com',
                'token_url' => '/oauth/token',
                'username' => 'test@example.com',
                'password' => 'pa55w0rd',
                'client_id' => 'test-client-id',
                'client_secret' => '',
                'scope' => 'administration',
                'resource' => null,
                'auth_location' => 'headers',
                'grant_type' => PasswordCredentials::class,
            ],
            $container, 'api_payment', $handler
        );

        $this->assertTrue($container->hasDefinition('guzzle_bundle_oauth2_plugin.middleware.api_payment'));
        $this->assertCount(2, $handler->getMethodCalls());

        $clientMiddlewareDefinition = $container->getDefinition('guzzle_bundle_oauth2_plugin.middleware.api_payment');
        $this->assertCount(3, $clientMiddlewareDefinition->getArguments());
    }

    /**
     * @dataProvider provideValidConfigurationData
     *
     * @param array $pluginConfiguration
     */
    public function testAddConfigurationWithData(array $pluginConfiguration)
    {
        $config = [
            'eight_points_guzzle' => [
                'clients' => [
                    'test_client' => [
                        'plugin' => [
                            'oauth2' => $pluginConfiguration,
                        ]
                    ]
                ]
            ]
        ];

        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration('eight_points_guzzle', false, [new GuzzleBundleOAuth2Plugin()]), $config);

        $this->assertInternalType('array', $processedConfig);
    }

    /**
     * @return array
     */
    public function provideValidConfigurationData() : array
    {
        return [
            'config is empty' => [[]],
            'plugin is disabled' => [[
                'enabled' => false,
            ]],
            'plugin is enabled' => [[
                'enabled' => true,
            ]],
            'PasswordCredentials in grant_type' => [[
                'grant_type' => PasswordCredentials::class,
            ]],
            'ClientCredentials in grant_type' => [[
                'grant_type' => ClientCredentials::class,
            ]],
            'JwtBearer in grant_type' => [[
                'grant_type' => ClientCredentials::class,
            ]],
            'RefreshToken in grant_type' => [[
                'grant_type' => RefreshToken::class,
            ]],
            'headers in auth_location' => [[
                'auth_location' => 'headers',
            ]],
            'body in auth_location' => [[
                'auth_location' => 'body',
            ]],
        ];
    }

    /**
     * @dataProvider provideInvalidConfigurationData
     *
     * @param array $pluginConfiguration
     */
    public function testAddConfigurationWithInvalidData(array $pluginConfiguration)
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [
            'eight_points_guzzle' => [
                'clients' => [
                    'test_client' => [
                        'plugin' => [
                            'oauth2' => $pluginConfiguration,
                        ]
                    ]
                ]
            ]
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration('eight_points_guzzle', false, [new GuzzleBundleOAuth2Plugin()]), $config);
    }

    /**
     * @return array
     */
    public function provideInvalidConfigurationData() : array
    {
        return [
            'invalid type in grant_type' => [[
                'grant_type' => true,
            ]],
            'invalid class in grant_type' => [[
                'grant_type' => \stdClass::class,
            ]],
            'invalid grant_type' => [[
                'auth_location' => 'somewhere',
            ]],
        ];
    }
}

<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Test;

use EightPoints\Bundle\GuzzleBundle\DependencyInjection\Configuration;
use EightPoints\Bundle\GuzzleBundle\PluginInterface;
use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GuzzleBundleOAuth2Plugin;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\JwtBearer;
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

    public function setUp() : void
    {
        parent::setUp();

        $this->plugin = new GuzzleBundleOAuth2Plugin();
    }

    public function testSubClassesOfPlugin() : void
    {
        $this->assertInstanceOf(PluginInterface::class, $this->plugin);
        $this->assertInstanceOf(Bundle::class, $this->plugin);
    }

    public function testAddConfiguration() : void
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
                'audience' => null,
                'resource' => null,
                'private_key' => null,
                'auth_location' => 'headers',
                'grant_type' => ClientCredentials::class,
                'persistent' => false,
                'retry_limit' => 5,
            ],
            $node->getDefaultValue()
        );
    }

    public function testGetPluginName() : void
    {
        $this->assertEquals('oauth2', $this->plugin->getPluginName());
    }

    public function testLoad() : void
    {
        $container = new ContainerBuilder();

        $this->plugin->load([], $container);

        $this->assertTrue($container->hasParameter('guzzle_bundle_oauth2_plugin.middleware.class'));
        $this->assertEquals(
            OAuthMiddleware::class,
            $container->getParameter('guzzle_bundle_oauth2_plugin.middleware.class')
        );
    }

    public function testLoadForClient() : void
    {
        $handler = new Definition();
        $container = new ContainerBuilder();

        $this->plugin->loadForClient(
            [
                'enabled' => true,
                'base_uri' => 'https://example.com',
                'token_url' => '/oauth/token',
                'username' => null,
                'password' => null,
                'client_id' => 'test-client-id',
                'client_secret' => '',
                'scope' => 'administration',
                'audience' => null,
                'resource' => null,
                'private_key' => null,
                'auth_location' => 'headers',
                'grant_type' => ClientCredentials::class,
                'persistent' => false,
                'retry_limit' => 5,
            ],
            $container, 'api_payment', $handler
        );

        $this->assertTrue($container->hasDefinition('guzzle_bundle_oauth2_plugin.middleware.api_payment'));
        $this->assertCount(2, $handler->getMethodCalls());

        $clientMiddlewareDefinition = $container->getDefinition('guzzle_bundle_oauth2_plugin.middleware.api_payment');
        $this->assertCount(3, $clientMiddlewareDefinition->getArguments());
    }

    public function testLoadForClientWithPrivateKey() : void
    {
        $handler = new Definition();
        $container = new ContainerBuilder();

        $this->plugin->loadForClient(
            [
                'enabled' => true,
                'base_uri' => 'https://example.com',
                'token_url' => '/oauth/token',
                'username' => null,
                'password' => null,
                'client_id' => 'test-client-id',
                'client_secret' => '',
                'scope' => 'administration',
                'audience' => null,
                'resource' => null,
                'private_key' => '/path/to/private.key',
                'auth_location' => 'headers',
                'grant_type' => JwtBearer::class,
                'persistent' => false,
                'retry_limit' => 5,
            ],
            $container, 'api_payment', $handler
        );

        $this->assertTrue($container->hasDefinition('guzzle_bundle_oauth2_plugin.middleware.api_payment'));
        $this->assertCount(2, $handler->getMethodCalls());

        $clientMiddlewareDefinition = $container->getDefinition('guzzle_bundle_oauth2_plugin.middleware.api_payment');
        $this->assertCount(3, $clientMiddlewareDefinition->getArguments());

        $this->assertTrue($container->hasDefinition('guzzle_bundle_oauth2_plugin.private_key.api_payment'));
        $clientMiddlewareDefinition = $container->getDefinition('guzzle_bundle_oauth2_plugin.private_key.api_payment');
        $this->assertCount(1, $clientMiddlewareDefinition->getArguments());
        $this->assertEquals('/path/to/private.key', $clientMiddlewareDefinition->getArgument(0));
    }

    /**
     * @dataProvider provideValidConfigurationData
     *
     * @param array $pluginConfiguration
     */
    public function testAddConfigurationWithData(array $pluginConfiguration) : void
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

        $this->assertIsArray( $processedConfig);
    }

    /**
     * @return array
     */
    public function provideValidConfigurationData() : array
    {
        return [
            'plugin is disabled' => [[
                'enabled' => false,
            ]],
            'plugin is enabled' => [[
                'enabled' => true,
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
            ]],
            'PasswordCredentials in grant_type' => [[
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
                'username' => 'johndoe',
                'password' => 'A3ddj3w',
                'grant_type' => PasswordCredentials::class,
            ]],
            'ClientCredentials in grant_type' => [[
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
                'grant_type' => ClientCredentials::class,
            ]],
            'RefreshToken in grant_type' => [[
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
                'grant_type' => RefreshToken::class,
            ]],
            'JwtBearer in grant_type' => [[
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
                'private_key' => '/path/to/private/key',
                'grant_type' => JwtBearer::class,
            ]],
            'headers in auth_location' => [[
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
                'auth_location' => 'headers',
            ]],
            'body in auth_location' => [[
                'base_uri' => 'https://example.com',
                'client_id' => 's6BhdRkqt3',
                'auth_location' => 'body',
            ]],
        ];
    }

    /**
     * @dataProvider provideInvalidConfigurationData
     *
     * @param array $pluginConfiguration
     * @param string $message
     */
    public function testAddConfigurationWithInvalidData(array $pluginConfiguration, string $message) : void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

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
            'without base_uri' => [
                'config' => [
                    'enabled' => true,
                    'client_id' => 's6BhdRkqt3',
                ],
                'exception message' => 'base_uri is required',
            ],
            'without client_id' => [
                'config' => [
                    'enabled' => true,
                    'base_uri' => 'https://example.com',
                ],
                'exception message' => 'client_id is required',
            ],
            'invalid type in grant_type' => [
                'config' => [
                    'base_uri' => 'https://example.com',
                    'client_id' => 's6BhdRkqt3',
                    'grant_type' => true,
                ],
                'exception message' => sprintf('Use instance of %s in grant_type', GrantTypeInterface::class),
            ],
            'invalid class in grant_type' => [
                'config' => [
                    'base_uri' => 'https://example.com',
                    'client_id' => 's6BhdRkqt3',
                    'grant_type' => \stdClass::class,
                ],
                'exception message' => sprintf('Use instance of %s in grant_type', GrantTypeInterface::class),
            ],
            'invalid auth_location' => [
                'config' => [
                    'base_uri' => 'https://example.com',
                    'client_id' => 's6BhdRkqt3',
                    'auth_location' => 'somewhere',
                ],
                'exception message' => 'Invalid auth_location "somewhere". Allowed values: headers, body.',
            ],
            'PasswordCredentials grant type without username' => [
                'config' => [
                    'base_uri' => 'https://example.com',
                    'client_id' => 's6BhdRkqt3',
                    'password' => 'A3ddj3w',
                    'grant_type' => PasswordCredentials::class,
                ],
                'exception message' => 'username and password are required',
            ],
            'PasswordCredentials grant type without password' => [
                'config' => [
                    'base_uri' => 'https://example.com',
                    'client_id' => 's6BhdRkqt3',
                    'username' => 'johndoe',
                    'grant_type' => PasswordCredentials::class,
                ],
                'exception message' => 'username and password are required',
            ],
            'JwtBearer grant type without private_key' => [
                'config' => [
                    'base_uri' => 'https://example.com',
                    'client_id' => 's6BhdRkqt3',
                    'grant_type' => JwtBearer::class,
                ],
                'exception message' => 'private_key is required',
            ],
        ];
    }
}

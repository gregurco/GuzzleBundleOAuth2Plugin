<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin;


use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\DependencyInjection\GuzzleBundleOAuth2Extension;
use EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundlePlugin;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\ExpressionLanguage\Expression;
use GuzzleHttp\Client;

class GuzzleBundleOAuth2Plugin extends Bundle implements EightPointsGuzzleBundlePlugin
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $extension = new GuzzleBundleOAuth2Extension();
        $extension->load($configs, $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @param string $clientName
     * @param Definition $handler
     */
    public function loadForClient(array $config, ContainerBuilder $container, string $clientName, Definition $handler)
    {
        if ($config['enabled']) {
            $middlewareConfig = [
                PasswordCredentials::CONFIG_USERNAME => $config['username'],
                PasswordCredentials::CONFIG_PASSWORD => $config['password'],
                PasswordCredentials::CONFIG_CLIENT_ID => $config['client_id'],
                PasswordCredentials::CONFIG_CLIENT_SECRET => $config['client_secret'],
                PasswordCredentials::CONFIG_TOKEN_URL => $config['token_url'],
                PasswordCredentials::CONFIG_AUTH_LOCATION => $config['auth_location'],
                PasswordCredentials::CONFIG_RESOURCE => $config['resource'],
                'scope' => $config['scope'],
            ];

            // Define Client
            $oauthClientDefinitionName = sprintf('guzzle_bundle_oauth2_plugin.client.%s', $clientName);
            $oauthClientDefinition = new Definition(Client::class);
            $oauthClientDefinition->addArgument(['base_uri' => $config['base_uri']]);
            $container->setDefinition($oauthClientDefinitionName, $oauthClientDefinition);

            // Define password credentials
            $passwordCredentialsDefinitionName = sprintf('guzzle_bundle_oauth2_plugin.password_credentials.%s', $clientName);
            $passwordCredentialsDefinition = new Definition($config['grant_type']);
            $passwordCredentialsDefinition->addArgument(new Reference($oauthClientDefinitionName));
            $passwordCredentialsDefinition->addArgument($middlewareConfig);
            $container->setDefinition($passwordCredentialsDefinitionName, $passwordCredentialsDefinition);

            // Define refresh token
            $refreshTokenDefinitionName = sprintf('guzzle_bundle_oauth2_plugin.refresh_token.%s', $clientName);
            $refreshTokenDefinition = new Definition(RefreshToken::class);
            $refreshTokenDefinition->addArgument(new Reference($oauthClientDefinitionName));
            $refreshTokenDefinition->addArgument($middlewareConfig);
            $container->setDefinition($refreshTokenDefinitionName, $refreshTokenDefinition);

            //Define middleware
            $oAuth2MiddlewareDefinitionName = sprintf('guzzle_bundle_oauth2_plugin.middleware.%s', $clientName);
            $oAuth2MiddlewareDefinition = new Definition('%guzzle_bundle_oauth2_plugin.middleware.class%');
            $oAuth2MiddlewareDefinition->setArguments([
                new Reference($oauthClientDefinitionName),
                new Reference($passwordCredentialsDefinitionName),
                new Reference($refreshTokenDefinitionName)
            ]);
            $container->setDefinition($oAuth2MiddlewareDefinitionName, $oAuth2MiddlewareDefinition);

            $onBeforeExpression = new Expression(sprintf('service("%s").onBefore()', $oAuth2MiddlewareDefinitionName));
            $onFailureExpression = new Expression(sprintf('service("%s").onFailure(5)', $oAuth2MiddlewareDefinitionName));

            $handler->addMethodCall('push', [$onBeforeExpression]);
            $handler->addMethodCall('push', [$onFailureExpression]);
        }
    }

    /**
     * @param ArrayNodeDefinition $pluginNode
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->canBeEnabled()
            ->validate()
                ->ifTrue(function (array $config) {
                    return $config['enabled'] === true && empty($config['base_uri']);
                })
                ->thenInvalid('base_uri is required')
            ->end()
            ->validate()
                ->ifTrue(function (array $config) {
                    return $config['enabled'] === true && empty($config['client_id']);
                })
                ->thenInvalid('client_id is required')
            ->end()
            ->validate()
                ->ifTrue(function (array $config) {
                    return $config['enabled'] === true &&
                        $config['grant_type'] === PasswordCredentials::class &&
                        (empty($config['username']) || empty($config['password']));
                })
                ->thenInvalid('username and password are required')
            ->end()
            ->children()
                ->scalarNode('base_uri')->defaultNull()->end()
                ->scalarNode('username')->defaultNull()->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('client_id')->defaultNull()->end()
                ->scalarNode('client_secret')->defaultNull()->end()
                ->scalarNode('token_url')->defaultNull()->end()
                ->scalarNode('scope')->defaultNull()->end()
                ->scalarNode('resource')->defaultNull()->end()
                ->scalarNode('auth_location')
                    ->defaultValue('headers')
                    ->validate()
                        ->ifNotInArray(['headers', 'body'])
                        ->thenInvalid('Invalid auth_location %s. Allowed values: headers, body.')
                    ->end()
                ->end()
                ->scalarNode('grant_type')
                    ->defaultValue(ClientCredentials::class)
                    ->validate()
                        ->ifTrue(function ($v) {
                            return !is_subclass_of($v, GrantTypeInterface::class);
                        })
                        ->thenInvalid(sprintf('Use instance of %s in grant_type', GrantTypeInterface::class))
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @return string
     */
    public function getPluginName() : string
    {
        return 'oauth2';
    }
}

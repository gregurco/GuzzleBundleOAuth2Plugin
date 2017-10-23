<?php

namespace Gregurco\Bundle\GuzzleBundleOAuth2Plugin;


use EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundlePlugin;
use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GrantType\PasswordCredentials;
use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\GrantType\RefreshToken;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\ExpressionLanguage\Expression;
use Gregurco\Bundle\GuzzleBundleOAuth2Plugin\Middleware\OAuthMiddleware;
use GuzzleHttp\Client;

class GuzzleBundleOAuth2Plugin extends Bundle implements EightPointsGuzzleBundlePlugin
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {

    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @param string $clientName
     * @param Definition $handler
     */
    public function loadForClient(array $config, ContainerBuilder $container, string $clientName, Definition $handler)
    {
        if ($config['username'] && $config['password']) {
            $middlewareConfig = [
                PasswordCredentials::CONFIG_USERNAME => $config['username'],
                PasswordCredentials::CONFIG_PASSWORD => $config['password'],
                PasswordCredentials::CONFIG_CLIENT_ID => $config['client_id'],
                PasswordCredentials::CONFIG_TOKEN_URL => $config['token_url'],
                'scope' => $config['scope'],
            ];

            // Define Client
            $oauthClientDefinitionName = sprintf('guzzle_bundle_oauth2_plugin.client.%s', $clientName);
            $oauthClientDefinition = new Definition(Client::class);
            $oauthClientDefinition->addArgument(['base_uri' => $config['url']]);
            $container->setDefinition($oauthClientDefinitionName, $oauthClientDefinition);

            // Define password credentials
            $passwordCredentialsDefinitionName = sprintf('guzzle_bundle_oauth2_plugin.password_credentials.%s', $clientName);
            $passwordCredentialsDefinition = new Definition(PasswordCredentials::class);
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
            $oAuth2MiddlewareDefinition = new Definition(OAuthMiddleware::class);
            $oAuth2MiddlewareDefinition->setArguments([new Reference($oauthClientDefinitionName), new Reference($passwordCredentialsDefinitionName), new Reference($refreshTokenDefinitionName)]);
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
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('url')->defaultNull()->end()
                ->scalarNode('username')->defaultNull()->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('client_id')->defaultNull()->end()
                ->scalarNode('token_url')->defaultNull()->end()
                ->scalarNode('scope')->defaultNull()->end()
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

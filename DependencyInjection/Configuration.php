<?php

namespace Alpixel\Bundle\LoggingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('alpixel_logging');

        $rootNode
            ->children()
            ->arrayNode('slack')
            ->children()
            ->scalarNode('token')
            ->defaultValue(false)
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('bot_name')
            ->defaultValue('PHP Error Logger')
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('channel')
            ->defaultValue('alerts')
            ->cannotBeEmpty()
            ->end()
            ->booleanNode('debug')
            ->defaultFalse()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}

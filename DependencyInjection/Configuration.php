<?php

namespace Byscripts\Bundle\I18nRoutingBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('byscripts_i18n_routing');
        $rootNode->children()
            ->booleanNode('autoPrefix')
                ->defaultTrue()
            ->end()
            ->arrayNode('locales')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->beforeNormalization()
                    ->always(function($mixed){
                        if(is_string($mixed)) {
                            return array($mixed => null);
                        } elseif(is_array($mixed) && array_keys($mixed) === range(0, count($mixed) - 1)) {
                            return array_fill_keys($mixed, null);
                        } else {
                            return $mixed;
                        }
                    })
                ->end()
                ->prototype('array')
                    ->children()
                        ->scalarNode('host')->defaultNull()->end()
                        ->scalarNode('prefix')->defaultNull()->end()
        ;

        return $treeBuilder;
    }
}

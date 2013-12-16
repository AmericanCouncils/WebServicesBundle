<?php

namespace AC\WebServicesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ac_web_services');

        $rootNode
            ->children()
                ->arrayNode('paths')
                    ->info('map of path regex matchers to api behavior configuration')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('path')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('default_response_format')->defaultValue('json')->end()
                            ->booleanNode('allow_code_suppression')->defaultFalse()->end()
                            ->booleanNode('include_response_data')->defaultFalse()->end()
                            ->booleanNode('include_exception_data')->defaultFalse()->end()
                            ->booleanNode('allow_jsonp')->defaultTrue()->end()
                            ->arrayNode('http_exception_map')
                                ->useAttributeAsKey('class')
                                ->prototype('array')
                                    ->children()
                                        ->integerNode('code')->isRequired()->end()
                                        ->scalarNode('message')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->variableNode('additional_headers')
                                ->info('A key:value map of http headers to values.')
                                ->defaultValue(array())
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->variableNode('serializable_formats')
                    ->defaultValue(array('xml','yml','json'))
                ->end()
                ->arrayNode('response_format_headers')
                    ->info('map of response formats to arrays of key:val http response headers')
                    ->defaultValue(array())
                    ->prototype('variable')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

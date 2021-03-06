<?php

namespace AC\WebServicesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Note that the default configuration values are stored in a separate config file at `Resources/config/config.defaults.yml`
 **/
class Configuration implements ConfigurationInterface
{
    private $defaults;

    public function __construct()
    {
        $this->defaults = Yaml::parse(__DIR__.'/../Resources/config/config.defaults.yml');
    }

    public function getConfigTreeBuilder()
    {
        $def = $this->defaults;

        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ac_web_services');

        $rootNode
            ->children()
                ->scalarNode('default_fixture_class')->defaultNull()->end()
                ->variableNode('serializable_formats')
                    ->defaultValue($def['serializable_formats'])
                ->end()
                ->arrayNode('response_format_headers')
                    ->info('map of response formats to arrays of key:val http response headers')
                    ->defaultValue($def['response_format_headers'])
                    ->validate()
                        ->always()
                        ->then(function ($val) use ($def) {
                            return array_merge($def['response_format_headers'], $val);
                        })
                    ->end()
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('serializer')
                    ->canBeDisabled()
                    ->children()
                        ->booleanNode('allow_deserialize_into_target')->defaultFalse()->end()
                        ->booleanNode('enable_form_deserialization')->defaultFalse()->end()
                        ->scalarNode('nested_collection_comparison_field')->defaultNull()->end()
                        ->variableNode('nested_collection_comparison_field_map')
                            ->info('A key:value map of assocation paths to field names to use for object comparison.')
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('negotiation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->variableNode('input_format_types')
                            ->defaultValue($def['input_format_types'])
                            ->validate()
                                ->always()
                                ->then(function ($val) use ($def) {
                                    return array_merge($def['input_format_types'], $val);
                                })
                            ->end()
                        ->end()
                        ->variableNode('response_format_priorities')->defaultValue($def['response_format_priorities'])->end()
                        ->variableNode('response_language_priorities')->defaultValue($def['response_language_priorities'])->end()
                        ->variableNode('response_charset_priorities')->defaultValue($def['response_charset_priorities'])->end()
                        ->variableNode('response_encoding_priorities')->defaultValue($def['response_encoding_priorities'])->end()
                        ->variableNode('response_additional_negotiation_formats')->defaultValue($def['response_additional_negotiation_formats'])->end()
                    ->end()
                ->end()
                ->arrayNode('paths')
                    ->info('map of path regex matchers to api behavior configuration')
                    //->canBeDisabled()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('path')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('default_response_format')->defaultValue($def['default_response_format'])->end()
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
            ->end();

        return $treeBuilder;
    }
}

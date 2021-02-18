<?php

namespace Valantic\PimcoreFormsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('valantic_pimcore_forms');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('forms')->arrayPrototype()
                    ->children()
                        ->booleanNode('csrf')->defaultValue(false)->info('Whether to enable CSRF protection for this form')->end()
                        ->scalarNode('method')->defaultValue('POST')->info('HTTP method (POST/GET) to submit this form')->end()
                        ->arrayNode('outputs')->isRequired()->requiresAtLeastOneElement()->arrayPrototype()
                            ->children()
                                ->variableNode('type')
                                    ->cannotBeEmpty()
                                    ->info('The type of this output channel, e.g. log, email, http, data_object')
                                ->end()
                                ->variableNode('options')->defaultValue([])->info('This depends on the output channel')->end()
                            ->end()
                        ->end()
                        ->end()
                        ->arrayNode('fields')->isRequired()->requiresAtLeastOneElement()->arrayPrototype()
                            ->children()
                                ->scalarNode('type')
                                    ->cannotBeEmpty()
                                    ->info('The type of this output channel')
                                    ->example('TextType')
                                ->end()
                                ->variableNode('constraints')->info('Define the Symfony Constraints for this field')->end()
                                ->variableNode('options')->defaultValue([])->info('Any of the valid field options for this FormType')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}

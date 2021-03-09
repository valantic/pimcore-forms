<?php

namespace Valantic\PimcoreFormsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    const SYMFONY_CONSTRAINTS_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';
    const SYMFONY_FORMTYPES_NAMESPACE = 'Symfony\\Component\\Form\\Extension\\Core\\Type\\';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('valantic_pimcore_forms');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('forms')->arrayPrototype()
                    ->children()
                        ->booleanNode('csrf')
                            ->defaultValue(false)
                            ->info('Whether to enable CSRF protection for this form')
                            ->end()
                        ->scalarNode('method')
                            ->defaultValue('POST')
                            ->info('HTTP method (POST/GET) to submit this form')
                                ->validate()
                                ->ifNotInArray(['GET', 'POST'])
                                ->thenInvalid('Must be GET or POST')
                                ->end()
                            ->end()
                    ->append($this->buildOutputsNode())
                    ->append($this->buildFieldsNode())
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }

    protected function buildFieldsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('fields');

        return $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('fields')->isRequired()->requiresAtLeastOneElement()->arrayPrototype()
                ->children()
                    ->scalarNode('type')
                        ->cannotBeEmpty()
                        ->info('The type of this FormType')
                            ->validate()
                            ->ifTrue(function (string $type): bool {
                                return !(class_exists($type) || class_exists(self::SYMFONY_FORMTYPES_NAMESPACE . $type));
                            })
                            ->thenInvalid('Invalid type class found. The type should either be a FQN or a subclass of '.self::SYMFONY_FORMTYPES_NAMESPACE)
                            ->end()
                        ->example('TextType')
                        ->end()
                    ->variableNode('constraints')
                        ->info('Define the Symfony Constraints for this field')
                        ->validate()
                            ->ifTrue(function (array $constraints): bool {
                                $hasError = false;
                                foreach ($constraints as $constraint) {
                                    $classExists = fn(string $name): bool => class_exists($name) || class_exists(self::SYMFONY_CONSTRAINTS_NAMESPACE . $name);
                                    if (is_string($constraint)) {
                                        $hasError = $hasError || !$classExists($constraint);
                                        continue;
                                    }
                                    if (is_array($constraint)) {
                                        $hasError = $hasError || !$classExists(array_keys($constraint)[0]);
                                        continue;
                                    }
                                    $hasError = true;
                                }

                                return $hasError;
                            })
                            ->thenInvalid('Invalid constraint class found. The constraint should either be a FQN or a subclass of ' . self::SYMFONY_CONSTRAINTS_NAMESPACE)
                            ->end()
                        ->end()
                    ->variableNode('options')
                        ->defaultValue([])
                        ->info('Any of the valid field options for this FormType')
                        ->end()
                    ->scalarNode('provider')
                        ->defaultValue(null)
                        ->info('A class to provide the options for this FormType')
                            ->validate()
                            ->ifTrue(fn(?string $name): bool => !class_exists($name) || !in_array(ChoicesInterface::class, class_implements($name), true))
                            ->thenInvalid('Provider class must exist and implement ' . ChoicesInterface::class)
                            ->end()
                        ->end()
                ->end()
            ->end();
    }

    protected function buildOutputsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('outputs');

        return $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('outputs')
                ->isRequired()->requiresAtLeastOneElement()->arrayPrototype()
                ->children()
                    ->variableNode('type')
                        ->cannotBeEmpty()
                        ->info('The type of this output channel, e.g. log, email, http, data_object; or anything implementing ' . OutputInterface::class)
                        ->end()
                    ->variableNode('options')
                        ->defaultValue([])
                        ->info('This depends on the output channel')
                        ->end()
                    ->end()
            ->validate()
                ->ifTrue(function ($config): bool {
                    $hasError = false;
                    if ($config['type'] === 'http') {
                        $hasError = $hasError || (filter_var($config['options']['url'] ?? '', FILTER_VALIDATE_URL) === false);
                    }

                    if ($config['type'] === 'email') {
                        $hasError = $hasError || (filter_var($config['options']['to'] ?? '', FILTER_VALIDATE_EMAIL) === false);
                    }

                    if ($config['type'] === 'data_object') {
                        $hasError = $hasError || !array_key_exists('class', $config['options']) || !array_key_exists('path', $config['options']);
                    }

                    return $hasError;
                })
                ->thenInvalid('There are missing/invalid configuration options')
                ->end()
            ->end();
    }
}

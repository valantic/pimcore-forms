<?php

namespace Valantic\PimcoreFormsBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Valantic\PimcoreFormsBundle\Repository\Configuration as ConfigurationRepository;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ValanticPimcoreFormsExtension extends Extension
{
    /**
     * {@inheritdoc}
     * @param array<mixed> $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(ConfigurationRepository::CONTAINER_TAG, $config);

        $ymlLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $ymlLoader->load('services.yml');
        $ymlLoader->load('transformers.yml');

        $xmlLoader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $xmlLoader->load('liform_services.xml');
        $xmlLoader->load('liform_transformers.xml');
    }
}
<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Valantic\PimcoreFormsBundle\Form\InputHandler\InputHandlerInterface;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Form\RedirectHandler\RedirectHandlerInterface;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ValanticPimcoreFormsExtension extends Extension
{
    public const TAG_OUTPUT = 'valantic.pimcore_forms.output';
    public const TAG_REDIRECT_HANDLER = 'valantic.pimcore_forms.redirect_handler';
    public const TAG_INPUT_HANDLER = 'valantic.pimcore_forms.input_handler';

    /**
     * {@inheritDoc}
     *
     * @param array<mixed> $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(ConfigurationRepository::CONTAINER_TAG, $config);
        $container->registerForAutoconfiguration(OutputInterface::class)->addTag(self::TAG_OUTPUT);
        $container->registerForAutoconfiguration(RedirectHandlerInterface::class)->addTag(self::TAG_REDIRECT_HANDLER);
        $container->registerForAutoconfiguration(InputHandlerInterface::class)->addTag(self::TAG_INPUT_HANDLER);

        $ymlLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $ymlLoader->load('services.yml');
        $ymlLoader->load('transformers.yml');

        $xmlLoader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $xmlLoader->load('liform_services.xml');
        $xmlLoader->load('liform_transformers.xml');
    }
}

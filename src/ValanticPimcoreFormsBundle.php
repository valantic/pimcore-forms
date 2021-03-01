<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Valantic\PimcoreFormsBundle\DependencyInjection\Compiler\ExtensionCompilerPass;
use Valantic\PimcoreFormsBundle\DependencyInjection\Compiler\TransformerCompilerPass;

class ValanticPimcoreFormsBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TransformerCompilerPass());
        $container->addCompilerPass(new ExtensionCompilerPass());
    }

    public function getJsPaths()
    {
        return [
            '/bundles/valanticpimcoreforms/js/pimcore/startup.js',
        ];
    }

    protected function getComposerPackageName(): string
    {
        $composer = file_get_contents(__DIR__ . '/../composer.json');
        if ($composer === false) {
            throw new RuntimeException();
        }

        return json_decode($composer)->name;
    }
}

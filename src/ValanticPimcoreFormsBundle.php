<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Valantic\PimcoreFormsBundle\DependencyInjection\Compiler\ExtensionCompilerPass;
use Valantic\PimcoreFormsBundle\DependencyInjection\Compiler\TransformerCompilerPass;
use Valantic\PimcoreFormsBundle\Installer\Installer;

class ValanticPimcoreFormsBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TransformerCompilerPass());
        $container->addCompilerPass(new ExtensionCompilerPass());
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/valanticpimcoreforms/js/pimcore/startup.js',
        ];
    }

    protected function getComposerPackageName(): string
    {
        $composer = file_get_contents(__DIR__ . '/../composer.json');

        if ($composer === false) {
            throw new \RuntimeException();
        }

        return json_decode($composer, flags: \JSON_THROW_ON_ERROR)->name;
    }

    public function getInstaller(): ?InstallerInterface
    {
        $installer = $this->container?->get(Installer::class);

        if (!$installer instanceof InstallerInterface) {
            return null;
        }

        return $installer;
    }
}

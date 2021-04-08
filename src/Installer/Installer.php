<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Installer;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

class Installer extends AbstractInstaller
{
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    public function isInstalled(): bool
    {
        return file_exists(ConfigurationRepository::EDITOR_STORAGE_FILE);
    }

    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    public function canBeUninstalled(): bool
    {
        return true;
    }

    public function canBeUpdated(): bool
    {
        return true;
    }

    public function install(): void
    {
        if (!file_exists(ConfigurationRepository::EDITOR_STORAGE_DIRECTORY)) {
            mkdir(ConfigurationRepository::EDITOR_STORAGE_DIRECTORY, 0755, true);
        }

        if (!file_exists(ConfigurationRepository::EDITOR_STORAGE_FILE)) {
            touch(ConfigurationRepository::EDITOR_STORAGE_FILE);
        }

        $this->getOutput()->write(sprintf('Please add the following lines to e.g. %s/config/config.yml', PIMCORE_APP_ROOT));
        $this->getOutput()->write('');
        $this->getOutput()->write('imports:');
        $this->getOutput()->write(sprintf("  - { resource: '../..%s/' }", str_replace(PIMCORE_PROJECT_ROOT, '', ConfigurationRepository::EDITOR_STORAGE_DIRECTORY)));
    }
}

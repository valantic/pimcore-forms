<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Repository;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationRepository
{
    final public const CONTAINER_TAG = 'valantic.picmore_forms.config';
    final public const EDITOR_STORAGE_DIRECTORY = PIMCORE_PRIVATE_VAR . '/bundles/valantic-forms';
    final public const EDITOR_STORAGE_FILE = self::EDITOR_STORAGE_DIRECTORY . '/forms.yml';

    public function __construct(
        protected readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function get(): array
    {
        $config = $this->parameterBag->get(self::CONTAINER_TAG);

        if (!is_array($config)) {
            throw new \RuntimeException();
        }

        return $config;
    }
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Repository;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationRepository
{
    public const CONTAINER_TAG = 'valantic.picmore_forms.config';
    public const EDITOR_STORAGE_DIRECTORY = PIMCORE_PRIVATE_VAR . '/bundles/valantic-forms';
    public const EDITOR_STORAGE_FILE = self::EDITOR_STORAGE_DIRECTORY . '/forms.yml';
    protected ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return array<string,mixed>
     */
    public function get(): array
    {
        return $this->parameterBag->get(self::CONTAINER_TAG);
    }
}

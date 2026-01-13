<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Repository;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationRepository
{
    final public const string CONTAINER_TAG = 'valantic.pimcore_forms.config';

    public function __construct(
        protected readonly ParameterBagInterface $parameterBag,
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

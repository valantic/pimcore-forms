<?php

namespace Valantic\PimcoreFormsBundle\Repository;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationRepository
{
    public const CONTAINER_TAG = 'valantic.picmore_forms.config';
    protected ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function get(): array
    {
        return $this->parameterBag->get(self::CONTAINER_TAG);
    }
}

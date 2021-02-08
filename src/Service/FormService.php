<?php

namespace Valantic\PimcoreFormsBundle\Service;

use Limenius\Liform\Liform;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Exception\InvalidFormConfigException;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Repository\Configuration;

class FormService
{
    protected Configuration $configuration;
    protected Builder $builder;
    protected Liform $liform;

    public function __construct(Configuration $configuration, Builder $builder, Liform $liform)
    {
        $this->configuration = $configuration;
        $this->builder = $builder;
        $this->liform = $liform;
    }

    public function build(string $name): FormBuilderInterface
    {
        $config = $this->getConfig($name);
        $form = $this->builder->form($name, $config);

        foreach ($config['fields'] as $name => $definition) {
            $form->add($name, ...$this->builder->field($definition));
        }

        return $form;
    }

    public function json(FormInterface $form): array
    {
        return $this->liform->transform($form);
    }

    public function buildJson(string $name): array
    {
        return $this->json($this->buildForm($name));
    }

    public function buildForm(string $name): FormInterface
    {
        return $this->build($name)->getForm();
    }

    public function errors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true, true) as $error) {
            if (!($error instanceof FormError)) {
                continue;
            }

            $errors[] = [
                'origin' => $error->getOrigin() !== null ? $error->getOrigin()->getName() : null,
                'message' => $error->getMessage(),
            ];
        }

        return $errors;
    }

    protected function getConfig(string $name): array
    {
        $config = $this->configuration->get()['forms'][$name];

        if (empty($config) || !is_array($config)) {
            throw new InvalidFormConfigException($name);
        }

        return $config;
    }
}

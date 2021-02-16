<?php

namespace Valantic\PimcoreFormsBundle\Service;

use Limenius\Liform\Liform;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Exception\InvalidFormConfigException;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;

class FormService
{
    protected ConfigurationRepository $configurationRepository;
    protected Builder $builder;
    protected Liform $liform;
    protected OutputRepository $outputRepository;

    public function __construct(
        ConfigurationRepository $configurationRepository,
        OutputRepository $outputRepository,
        Builder $builder,
        Liform $liform
    )
    {
        $this->configurationRepository = $configurationRepository;
        $this->builder = $builder;
        $this->liform = $liform;
        $this->outputRepository = $outputRepository;
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

    /**
     * @param FormInterface $form
     *
     * @return array<mixed>
     */
    public function json(FormInterface $form): array
    {
        return $this->liform->transform($form);
    }

    /**
     * @param string $name
     *
     * @return array<mixed>
     */
    public function buildJson(string $name): array
    {
        return $this->json($this->buildForm($name));
    }

    public function buildForm(string $name): FormInterface
    {
        return $this->build($name)->getForm();
    }

    /**
     * @param FormInterface $form
     *
     * @return array<array>
     */
    public function errors(FormInterface $form): array
    {
        return $this->errorNormalizer->normalize($form);
    }

    public function outputs(FormInterface $form): bool
    {
        $status = true;

        $outputs = $this->getConfig($form->getName())['outputs'];
        foreach ($outputs as $name => ['type' => $type, 'options' => $options]) {
            $output = $this->outputRepository->get($type);
            $output->initialize($form, $options);
            $status = $output->handle() && $status; // DO NOT SWAP the two arguments!!!
        }

        return $status;
    }

    /**
     * @param string $name
     *
     * @return array<string,mixed>
     */
    protected function getConfig(string $name): array
    {
        $config = $this->configurationRepository->get()['forms'][$name];

        if (empty($config) || !is_array($config)) {
            throw new InvalidFormConfigException($name);
        }

        return $config;
    }
}

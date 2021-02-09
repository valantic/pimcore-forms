<?php

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Symfony\Component\Form\FormInterface;

abstract class AbstractOutput implements OutputInterface
{
    protected FormInterface $form;
    protected array $config;

    public function initialize(FormInterface $form, array $config): void
    {
        $this->form = $form;
        $this->config = $config;
    }
}

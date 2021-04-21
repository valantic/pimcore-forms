<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Symfony\Component\Form\FormInterface;

abstract class AbstractOutput implements OutputInterface
{
    protected string $key;
    protected FormInterface $form;
    /**
     * @var array<string,mixed>
     */
    protected array $config;
    /**
     * @var OutputInterface[]
     */
    protected array $outputHandlers = [];

    public function initialize(string $key, FormInterface $form, array $config): void
    {
        $this->key = $key;
        $this->form = $form;
        $this->config = $config;
    }

    public function setOutputHandlers(array $handlers): void
    {
        $this->outputHandlers = $handlers;
    }
}

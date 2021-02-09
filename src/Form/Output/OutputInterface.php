<?php

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Symfony\Component\Form\FormInterface;

interface OutputInterface
{
    /**
     * @param FormInterface $form
     * @param array<string,mixed> $config
     */
    public function initialize(FormInterface $form, array $config): void;

    public function handle(): bool;

    public static function name(): string;
}

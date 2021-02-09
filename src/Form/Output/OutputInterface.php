<?php

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Symfony\Component\Form\FormInterface;

interface OutputInterface
{
    public function initialize(FormInterface $form, array $config): void;

    public function handle(): bool;

    public static function name(): string;
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Symfony\Component\Form\FormInterface;

interface OutputInterface
{
    /**
     * @param string $key
     * @param FormInterface $form
     * @param array<string,mixed> $config
     */
    public function initialize(string $key, FormInterface $form, array $config): void;

    public function handle(): bool;

    public static function name(): string;
}

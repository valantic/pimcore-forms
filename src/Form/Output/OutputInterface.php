<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;

interface OutputInterface
{
    /**
     * @param string $key
     * @param FormInterface $form
     * @param array<string,mixed> $config
     */
    public function initialize(string $key, FormInterface $form, array $config): void;

    /**
     * @param OutputInterface[] $handlers
     *
     * @return void
     */
    public function setOutputHandlers(array $handlers): void;

    public function handle(OutputResponse $outputResponse): OutputResponse;

    public static function name(): string;
}

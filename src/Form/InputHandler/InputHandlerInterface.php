<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\InputHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface InputHandlerInterface
{
    public function initialize(FormInterface $form, ?Request $request): void;

    public function getAll(): array;
}

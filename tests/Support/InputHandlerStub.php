<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Valantic\PimcoreFormsBundle\Form\InputHandler\InputHandlerInterface;

class InputHandlerStub implements InputHandlerInterface
{
    public function initialize(FormInterface $form, ?Request $request): void
    {
        // Stub implementation
    }

    public function get(): array
    {
        return [];
    }
}

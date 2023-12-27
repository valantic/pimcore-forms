<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\InputHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractInputHandler implements InputHandlerInterface
{
    protected FormInterface $form;
    protected ?Request $request = null;

    public function initialize(FormInterface $form, ?Request $request): void
    {
        $this->form = $form;
        $this->request = $request;
    }
}

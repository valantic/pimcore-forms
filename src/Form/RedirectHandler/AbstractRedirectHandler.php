<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\RedirectHandler;

use Symfony\Component\Form\FormInterface;

abstract class AbstractRedirectHandler implements RedirectHandlerInterface
{
    protected FormInterface $form;
    /**
     * @var array<string,mixed>
     */
    protected array $config;

    public function initialize(FormInterface $form, array $config): void
    {
        $this->form = $form;
        $this->config = $config;
    }
}

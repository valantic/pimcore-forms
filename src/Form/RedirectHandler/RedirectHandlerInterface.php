<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\RedirectHandler;

interface RedirectHandlerInterface
{
    public function onSuccess(): ?string;

    public function onFailure(): ?string;
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support;

use Valantic\PimcoreFormsBundle\Form\RedirectHandler\RedirectHandlerInterface;

class RedirectHandlerStub implements RedirectHandlerInterface
{
    public function onSuccess(): ?string
    {
        return 'https://example.com/success';
    }

    public function onFailure(): ?string
    {
        return 'https://example.com/failure';
    }
}

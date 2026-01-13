<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support;

use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;

class ChoiceProviderStub implements ChoicesInterface
{
    public function choices(): array
    {
        return ['option1' => 'Option 1', 'option2' => 'Option 2'];
    }

    public function choiceLabel(mixed $choice, mixed $key, mixed $value): ?string
    {
        return $choice;
    }

    public function choiceAttribute(mixed $choice, mixed $key, mixed $value): array
    {
        return [];
    }
}

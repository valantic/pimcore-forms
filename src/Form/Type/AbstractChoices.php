<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

abstract class AbstractChoices implements ChoicesInterface
{
    public function choiceLabel(mixed $choice, mixed $key, mixed $value): ?string
    {
        return $key;
    }

    public function choiceAttribute(mixed $choice, mixed $key, mixed $value): array
    {
        return [];
    }
}

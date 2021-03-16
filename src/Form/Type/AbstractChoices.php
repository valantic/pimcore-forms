<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

abstract class AbstractChoices implements ChoicesInterface
{
    public function choiceLabel($choice, $key, $value): ?string
    {
        return $key;
    }

    public function choiceAttribute($choice, $key, $value): array
    {
        return [];
    }
}

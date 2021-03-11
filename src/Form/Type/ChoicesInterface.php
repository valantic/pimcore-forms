<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

interface ChoicesInterface
{
    /**
     * @return array<mixed,mixed>
     */
    public function choices(): array;

    /**
     * @param mixed $choice
     * @param mixed $key
     * @param mixed $value
     *
     * @return string|null
     */
    public function choiceLabel($choice, $key, $value): ?string;

    /**
     * @param mixed $choice
     * @param mixed $key
     * @param mixed $value
     *
     * @return array<string,string|int>|null
     */
    public function choiceAttribute($choice, $key, $value): ?array;
}

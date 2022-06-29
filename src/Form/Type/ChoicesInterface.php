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
    public function choiceLabel(mixed $choice, mixed $key, mixed $value): ?string;

    /**
     * @param mixed $choice
     * @param mixed $key
     * @param mixed $value
     *
     * @return array<string,string|int>
     */
    public function choiceAttribute(mixed $choice, mixed $key, mixed $value): array;
}

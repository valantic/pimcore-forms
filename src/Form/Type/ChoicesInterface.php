<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

interface ChoicesInterface
{
    /**
     * @param mixed $context
     *
     * @return array<mixed,mixed>
     */
    public function choices(mixed $context): array;

    /**
     * @param mixed $choice
     * @param mixed $key
     * @param mixed $value
     * @param mixed $context
     *
     * @return string|null
     */
    public function choiceLabel(mixed $choice, mixed $key, mixed $value, mixed $context): ?string;

    /**
     * @param mixed $choice
     * @param mixed $key
     * @param mixed $value
     * @param mixed $context
     *
     * @return array<string,string|int>
     */
    public function choiceAttribute(mixed $choice, mixed $key, mixed $value, mixed $context): array;
}

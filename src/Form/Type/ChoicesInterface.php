<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

interface ChoicesInterface
{
    /**
     * @return array<mixed,mixed>
     */
    public function choices(): array;

    public function choiceLabel(mixed $choice, mixed $key, mixed $value): ?string;

    /**
     * @return array<string,string|int>
     */
    public function choiceAttribute(mixed $choice, mixed $key, mixed $value): array;
}

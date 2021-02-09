<?php

namespace Valantic\PimcoreFormsBundle\Form\Type;

interface ChoicesInterface
{
    /**
     * @return array<mixed,mixed>
     */
    public function choices(): array;
}

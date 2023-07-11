<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

interface ConfigAwareInterface
{
    /**
     * @param array<mixed> $formConfig
     */
    public function setFieldConfig(array $formConfig): void;

    /**
     * @return array<mixed>
     */
    public function getFieldConfig(): array;

    public function setFormName(string $formName): void;

    public function getFormName(): string;
}

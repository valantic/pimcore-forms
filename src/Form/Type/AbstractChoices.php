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


    public function setFieldConfig(array $formConfig): void
    {
        $this->fieldConfig = $formConfig;
    }

    public function getFieldConfig(): array
    {
        return $this->fieldConfig;
    }

    public function setFormName(string $formName): void
    {
        $this->formName = $formName;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }
}

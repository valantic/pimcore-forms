<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\FormInterface;

class FormAttributeExtension implements ExtensionInterface
{
    /**
     * @param FormInterface $form
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    public function apply(FormInterface $form, array $schema): array
    {
        if (!array_key_exists('attr', $schema)) {
            return $schema;
        }

        $camelCaseKeys = array_map(
            fn (string $key): string => lcfirst(str_replace('-', '', ucwords($key, '-'))), // https://stackoverflow.com/a/2792045
            array_keys($schema['attr'])
        );

        $schema['attr'] = array_combine(
            $camelCaseKeys,
            array_values($schema['attr'])
        );

        return $schema;
    }
}

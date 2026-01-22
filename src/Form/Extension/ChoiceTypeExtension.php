<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;

class ChoiceTypeExtension implements ExtensionInterface
{
    /**
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    public function apply(FormInterface $form, array $schema): array
    {
        if (!$form->getConfig()->getType()->getInnerType() instanceof ChoiceType) {
            return $schema;
        }

        /** @var ChoiceView[] $choices */
        $choices = $form->createView()->vars['choices'];

        foreach ($choices as $key => $choice) {
            $camelCaseKeys = array_map(
                fn (string $key): string => lcfirst(str_replace('-', '', ucwords($key, '-'))), // https://stackoverflow.com/a/2792045
                array_keys($choice->attr),
            );

            $choice->attr = array_combine(
                $camelCaseKeys,
                array_values($choice->attr),
            );
            $choices[$key] = $choice;
        }

        $schema['options']['choices'] = $choices;

        return $schema;
    }
}

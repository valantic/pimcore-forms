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
     * @param FormInterface $form
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

        $schema['options']['enum_attrs'] = array_map(
            fn(ChoiceView $choice): array => $choice->attr,
            $choices
        );

        $schema['options']['choices'] = $choices;

        return $schema;
    }
}
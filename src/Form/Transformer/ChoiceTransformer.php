<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;

/**
 * Adapted to remove translator since we're calling the translator based on the config.
 * Code duplication due to private methods.
 *
 * @see  \Limenius\Liform\Transformer\ChoiceTransformer
 */
class ChoiceTransformer extends \Limenius\Liform\Transformer\ChoiceTransformer
{
    use OverwriteAbstractTransformerTrait;

    #[\Override]
    public function transform(FormInterface $form, array $extensions = [], $widget = null): array
    {
        $formView = $form->createView();

        $choices = [];
        $titles = [];

        foreach ($formView->vars['choices'] as $choiceView) {
            if ($choiceView instanceof ChoiceGroupView) {
                foreach ($choiceView->choices as $choiceItem) {
                    if ($choiceItem instanceof ChoiceView) {
                        $choices[] = $choiceItem->value;
                        $titles[] = $choiceItem->label;
                    }
                }
            } elseif ($choiceView instanceof ChoiceView) {
                $choices[] = $choiceView->value;
                $titles[] = $choiceView->label;
            }
        }

        if ($formView->vars['multiple']) {
            $schema = $this->transformMultiple($form, $choices, $titles);
        } else {
            $schema = $this->transformSingle($form, $choices, $titles);
        }

        return $this->addCommonSpecs($form, $schema, $extensions, $widget);
    }

    /**
     * @return array<string,mixed>
     */
    private function transformSingle(FormInterface $form, $choices, $titles): array
    {
        $formView = $form->createView();

        $schema = [
            'enum' => $choices,
            'enum_titles' => $titles, // For backwards compatibility
            'options' => [
                'enum_titles' => $titles,
            ],
            'type' => 'string',
        ];

        if ($formView->vars['expanded']) {
            $schema['widget'] = 'choice-expanded';
        }

        return $schema;
    }

    /**
     * @return array<string,mixed>
     */
    private function transformMultiple(FormInterface $form, $choices, $titles): array
    {
        $formView = $form->createView();

        $schema = [
            'items' => [
                'type' => 'string',
                'enum' => $choices,
                'enum_titles' => $titles, // For backwards compatibility
                'options' => [
                    'enum_titles' => $titles,
                ],
            ],
            'minItems' => $this->isRequired($form) === true ? 1 : 0,
            'uniqueItems' => true,
            'type' => 'array',
        ];

        if ($formView->vars['expanded']) {
            $schema['widget'] = 'choice-multiple-expanded';
        }

        return $schema;
    }
}

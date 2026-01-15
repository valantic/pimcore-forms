<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Symfony\Component\Form\FormInterface;

trait OverwriteAbstractTransformerTrait
{
    /**
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     *
     * @see \Limenius\Liform\Transformer\AbstractTransformer::addLabel
     */
    protected function addLabel(FormInterface $form, array $schema): array
    {
        $translationDomain = $form->getConfig()->getOption('translation_domain');
        $label = $form->getConfig()->getOption('label');

        if ($label !== null) {
            // translation is handled in \Valantic\PimcoreFormsBundle\Form\Builder::getOptions
            $schema['title'] = $label;
        } else {
            $schema['title'] = $this->translator->trans($form->getName(), [], $translationDomain);
        }

        return $schema;
    }

    /**
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    protected function addAttr(FormInterface $form, array $schema): array
    {
        $attr = $form->getConfig()->getOption('attr');

        if (!empty($attr)) {
            $schema['attr'] = $attr;
        }

        if (is_array($attr) && count($attr) > 0 && array_key_exists('placeholder', $attr)) {
            $translationDomain = $form->getConfig()->getOption('translation_domain');
            $schema['attr']['placeholder'] = $this->translator->trans($attr['placeholder'], [], $translationDomain);
        }

        return $schema;
    }
}

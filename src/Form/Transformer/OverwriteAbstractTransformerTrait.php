<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Symfony\Component\Form\FormInterface;

trait OverwriteAbstractTransformerTrait
{
    /**
     * @param FormInterface $form
     * @param array $schema
     *
     * @return array
     *
     * @see \Limenius\Liform\Transformer\AbstractTransformer::addLabel
     */
    protected function addLabel(FormInterface $form, array $schema): array
    {
        $translationDomain = $form->getConfig()->getOption('translation_domain');
        if ($label = $form->getConfig()->getOption('label')) {
            // translation is handled in \Valantic\PimcoreFormsBundle\Form\Builder::getOptions
            $schema['title'] = $label;
        } else {
            $schema['title'] = $this->translator->trans($form->getName(), [], $translationDomain);
        }

        return $schema;
    }
}

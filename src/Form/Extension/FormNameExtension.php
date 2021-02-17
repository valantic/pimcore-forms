<?php

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\FormInterface;

class FormNameExtension implements ExtensionInterface
{
    /**
     * @param FormInterface $form
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    public function apply(FormInterface $form, array $schema): array
    {
        $schema['name'] = $form->getName();

        return $schema;
    }
}

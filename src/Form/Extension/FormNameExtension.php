<?php

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\FormInterface;

class FormNameExtension implements ExtensionInterface
{
    public function apply(FormInterface $form, array $schema): array
    {
        $schema['name'] = $form->getName();

        return $schema;
    }
}

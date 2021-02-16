<?php

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;

class FormTypeExtension implements ExtensionInterface
{
    public function apply(FormInterface $form, array $schema): array
    {
        if ($form->getConfig()->getType()->getInnerType() instanceof FormType) {
            return $schema;
        }

        $type = get_class($form->getConfig()->getType()->getInnerType());

        $mapping = [
            SubmitType::class => 'button.submit',
        ];

        $schema['form_type'] = $mapping[$type] ?? $type;

        return $schema;
    }
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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

        if ($form->getConfig()->getType()->getInnerType() instanceof FormType) {
            $schema['submitUrl'] = $form->getConfig()->getAction();
        }

        return $schema;
    }
}

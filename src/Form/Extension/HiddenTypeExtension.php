<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;

class HiddenTypeExtension implements ExtensionInterface
{
    /**
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    public function apply(FormInterface $form, array $schema): array
    {
        if (!$form->getConfig()->getType()->getInnerType() instanceof HiddenType) {
            return $schema;
        }

        $schema['value'] = $form->getConfig()->getOption('data');

        return $schema;
    }
}

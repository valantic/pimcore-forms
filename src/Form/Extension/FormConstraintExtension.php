<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

class FormConstraintExtension implements ExtensionInterface
{
    /**
     * @param FormInterface $form
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    public function apply(FormInterface $form, array $schema): array
    {
        $constraints = $form->getConfig()->getOption('constraints');
        if (empty($constraints)) {
            return $schema;
        }
        $schema['constraints'] = array_map(
            fn(Constraint $constraint): array => [
                'type' => str_replace('Symfony\\Component\\Validator\\Constraints\\', '', get_class($constraint)),
                'config' => json_decode(json_encode($constraint), true),
            ],
            $constraints
        );

        return $schema;
    }
}

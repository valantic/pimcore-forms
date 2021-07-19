<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Regex;
use Valantic\PimcoreFormsBundle\DependencyInjection\Configuration;

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

        $schema['constraints'] = [];

        foreach ($constraints as $constraint) {
            /** @var Constraint $data */
            $data = [
                'type' => str_replace(Configuration::SYMFONY_CONSTRAINTS_NAMESPACE, '', get_class($constraint)),
                'config' => json_decode(json_encode($constraint), true),
            ];

            if ($constraint instanceof Regex && !empty($constraint->pattern) && empty($constraint->htmlPattern)) {
                $data['config']['htmlPattern'] = $constraint->getHtmlPattern();
            }

            $schema['constraints'][] = $data;
        }

        return $schema;
    }
}

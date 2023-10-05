<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Limenius\Liform\Transformer\AbstractTransformer;
use Symfony\Component\Form\FormInterface;

class ButtonTransformer extends AbstractTransformer
{
    use OverwriteAbstractTransformerTrait;

    public function transform(FormInterface $form, array $extensions = [], $widget = null): array
    {
        $schema = ['type' => 'string'];

        return $this->addCommonSpecs($form, $schema, $extensions, $widget);
    }
}

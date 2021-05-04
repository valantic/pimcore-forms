<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Limenius\Liform\Transformer\StringTransformer;
use Symfony\Component\Form\FormInterface;

class FileTransformer extends StringTransformer
{
    use OverwriteAbstractTransformerTrait;

    /**
     * {@inheritDoc}
     */
    public function transform(FormInterface $form, array $extensions = [], $widget = null)
    {
        $schema = ['type' => 'file'];

        return $this->addCommonSpecs($form, $schema, $extensions, $widget);
    }
}

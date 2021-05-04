<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Symfony\Component\Form\FormInterface;

class FileTransformer extends StringTransformer
{
    /**
     * {@inheritDoc}
     */
    public function transform(FormInterface $form, array $extensions = [], $widget = null)
    {
        $schema = ['type' => 'file'];

        return $this->addCommonSpecs($form, $schema, $extensions, $widget);
    }
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Transformer;

use Symfony\Component\Form\FormInterface;

class FileTransformer extends StringTransformer
{
    public function transform(FormInterface $form, array $extensions = [], $widget = null): array
    {
        $schema = ['type' => 'file'];

        return $this->addCommonSpecs($form, $schema, $extensions, $widget);
    }
}

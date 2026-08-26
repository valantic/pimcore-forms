<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Traits;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

trait CreatesFormBuilders
{
    /**
     * Creates a basic FormBuilder for testing.
     */
    protected function createFormBuilder(string $name = 'test_form'): FormBuilderInterface
    {
        $validator = Validation::createValidator();
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
        ;

        return $formFactory->createNamedBuilder($name, FormType::class);
    }

    /**
     * Creates a form with submitted data.
     */
    protected function createFormWithData(array $data, string $name = 'test_form'): FormInterface
    {
        $builder = $this->createFormBuilder($name);

        // Add fields based on data keys
        foreach (array_keys($data) as $fieldName) {
            $builder->add($fieldName, TextType::class);
        }

        $form = $builder->getForm();
        $form->submit($data);

        return $form;
    }

    /**
     * Creates a valid submitted form.
     */
    protected function createValidForm(array $data = ['name' => 'John Doe']): FormInterface
    {
        return $this->createFormWithData($data);
    }
}

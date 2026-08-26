<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Valantic\PimcoreFormsBundle\Form\Extension\FormDataExtension;

#[AllowMockObjectsWithoutExpectations]
class FormDataExtensionTest extends TestCase
{
    private FormFactoryInterface $formFactory;
    private FormDataExtension $extension;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactory();
        $this->extension = new FormDataExtension();
    }

    public function testApplyAddsNullDataWhenNoDataSet(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('data', $result);
        $this->assertNull($result['data']);
    }

    public function testApplyAddsDataFromForm(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
            ->add('name', TextType::class)
            ->add('email', TextType::class)
            ->getForm()
        ;

        $nameField = $form->get('name');
        $emailField = $form->get('email');

        $nameSchema = [];
        $emailSchema = [];

        $nameResult = $this->extension->apply($nameField, $nameSchema);
        $emailResult = $this->extension->apply($emailField, $emailSchema);

        $this->assertArrayHasKey('data', $nameResult);
        $this->assertArrayHasKey('data', $emailResult);
        $this->assertEquals('John Doe', $nameResult['data']);
        $this->assertEquals('john@example.com', $emailResult['data']);
    }

    public function testApplyAddsDataForFormWithObject(): void
    {
        $data = new \stdClass();
        $data->field = 'test value';

        $form = $this->formFactory->createBuilder(FormType::class, $data)
            ->add('field', TextType::class, ['property_path' => 'field'])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('test value', $result['data']);
    }

    public function testApplyPreservesExistingSchemaKeys(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [
            'type' => 'string',
            'label' => 'Field Label',
        ];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('string', $result['type']);
        $this->assertEquals('Field Label', $result['label']);
    }
}

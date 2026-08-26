<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Valantic\PimcoreFormsBundle\Form\Extension\FormTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Type\SubheaderType;

#[AllowMockObjectsWithoutExpectations]
class FormTypeExtensionTest extends TestCase
{
    private FormFactoryInterface $formFactory;
    private FormTypeExtension $extension;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactory();
        $this->extension = new FormTypeExtension();
    }

    public function testApplyReturnsSchemaUnchangedForFormType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)->getForm();
        $schema = ['type' => 'object'];

        $result = $this->extension->apply($form, $schema);

        $this->assertEquals($schema, $result);
        $this->assertArrayNotHasKey('formType', $result);
    }

    public function testApplyAddsFormTypeForTextType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('text', $result['formType']);
    }

    public function testApplyMapsChoiceTypeToSelectSingle(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => ['Option 1' => 'opt1', 'Option 2' => 'opt2'],
                'expanded' => false,
                'multiple' => false,
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('select.single', $result['formType']);
    }

    public function testApplyMapsChoiceTypeToSelectMultiple(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => ['Option 1' => 'opt1', 'Option 2' => 'opt2'],
                'expanded' => false,
                'multiple' => true,
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('select.multiple', $result['formType']);
    }

    public function testApplyMapsChoiceTypeToRadio(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => ['Option 1' => 'opt1', 'Option 2' => 'opt2'],
                'expanded' => true,
                'multiple' => false,
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('radio', $result['formType']);
    }

    public function testApplyMapsChoiceTypeToCheckboxes(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => ['Option 1' => 'opt1', 'Option 2' => 'opt2'],
                'expanded' => true,
                'multiple' => true,
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('checkboxes', $result['formType']);
    }

    public function testApplyMapsStandardFormTypes(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('email', EmailType::class)
            ->getForm()
        ;

        $field = $form->get('email');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('email', $result['formType']);
    }

    public function testApplyMapsCustomSubheaderType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('subheader', SubheaderType::class)
            ->getForm()
        ;

        $field = $form->get('subheader');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('formType', $result);
        $this->assertEquals('subheader', $result['formType']);
    }
}

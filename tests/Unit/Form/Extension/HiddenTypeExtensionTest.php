<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Valantic\PimcoreFormsBundle\Form\Extension\HiddenTypeExtension;

#[AllowMockObjectsWithoutExpectations]
class HiddenTypeExtensionTest extends TestCase
{
    private FormFactoryInterface $formFactory;
    private HiddenTypeExtension $extension;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactory();
        $this->extension = new HiddenTypeExtension();
    }

    public function testApplyReturnsSchemaUnchangedForNonHiddenType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = ['type' => 'string'];

        $result = $this->extension->apply($field, $schema);

        $this->assertEquals($schema, $result);
        $this->assertArrayNotHasKey('value', $result);
    }

    public function testApplyAddsValueForHiddenType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('hidden_field', HiddenType::class, [
                'data' => 'secret_value',
            ])
            ->getForm()
        ;

        $field = $form->get('hidden_field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('value', $result);
        $this->assertEquals('secret_value', $result['value']);
    }

    public function testApplyHandlesNullValueForHiddenType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('hidden_field', HiddenType::class)
            ->getForm()
        ;

        $field = $form->get('hidden_field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('value', $result);
        $this->assertNull($result['value']);
    }
}

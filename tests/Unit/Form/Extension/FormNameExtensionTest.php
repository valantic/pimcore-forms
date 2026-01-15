<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Valantic\PimcoreFormsBundle\Form\Extension\FormNameExtension;

#[AllowMockObjectsWithoutExpectations]
class FormNameExtensionTest extends TestCase
{
    private FormFactoryInterface $formFactory;
    private FormNameExtension $extension;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactory();
        $this->extension = new FormNameExtension();
    }

    public function testApplyAddsFormNameToSchema(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, null, ['action' => '/submit'])
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('field', $result['name']);
    }

    public function testApplyAddsSubmitUrlForFormType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, null, ['action' => '/submit'])->getForm();
        $schema = [];

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('submitUrl', $result);
        $this->assertEquals('/submit', $result['submitUrl']);
    }

    public function testApplyDoesNotAddSubmitUrlForNonFormType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, null, ['action' => '/submit'])
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('submitUrl', $result);
    }
}

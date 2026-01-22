<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use Limenius\Liform\Resolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\CompoundTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\StringTransformer;

#[AllowMockObjectsWithoutExpectations]
class CompoundTransformerTest extends TestCase
{
    private CompoundTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;
    private Resolver $resolver;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();
        $this->resolver = new Resolver();

        $this->transformer = new CompoundTransformer($this->translator, $this->resolver);

        // Register transformers for nested fields
        $this->resolver->setTransformer('text', new StringTransformer($this->translator, null));
        $this->resolver->setTransformer('email', new StringTransformer($this->translator, null));
        $this->resolver->setTransformer('form', $this->transformer);
        $this->resolver->setTransformer('compound', $this->transformer);
    }

    public function testTransformNestedForm(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(FormType::class, null, [
                'label' => 'Contact Form',
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
    }

    public function testTransformNestedObjectWithProperties(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(FormType::class, null, [
                'label' => 'User Profile',
            ])
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name'])
            ->add('email', EmailType::class, ['label' => 'Email Address'])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertCount(3, $schema['properties']);
    }

    public function testTransformNestedFormWithCustomLabel(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(FormType::class, null, [
                'label' => 'Address Information',
            ])
            ->add('street', TextType::class, ['label' => 'Street'])
            ->add('city', TextType::class, ['label' => 'City'])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('Address Information', $schema['title']);
    }

    public function testTransformDeeplyNestedObjects(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(FormType::class, null, [
                'label' => 'User',
            ])
            ->add('name', TextType::class)
            ->add('address', FormType::class, [
                'label' => 'Address',
            ])
            ->getForm()
        ;

        $form->get('address')
            ->add('street', TextType::class)
            ->add('city', TextType::class)
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }

    public function testTransformCompoundWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(FormType::class, null, [
                'label' => 'Form with Attrs',
                'attr' => [
                    'class' => 'compound-form',
                    'data-test' => 'nested',
                ],
            ])
            ->add('field1', TextType::class)
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame('compound-form', $schema['attr']['class']);
    }
}

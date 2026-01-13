<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use Limenius\Liform\Resolver;
use Limenius\Liform\Transformer\StringTransformer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\ArrayTransformer;

#[AllowMockObjectsWithoutExpectations]
class ArrayTransformerTest extends TestCase
{
    private ArrayTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;
    private Resolver $resolver;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();
        $this->resolver = new Resolver();

        $this->transformer = new ArrayTransformer($this->translator, $this->resolver);

        // Register transformers for nested fields
        $this->resolver->setTransformer('text', new StringTransformer($this->translator, null));
        $this->resolver->setTransformer('collection', $this->transformer);
    }

    public function testTransformBasicArray(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(CollectionType::class, null, [
                'entry_type' => TextType::class,
                'label' => 'Items',
                'allow_add' => true,
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('array', $schema['type']);
        $this->assertSame('Items', $schema['title']);
    }

    public function testTransformArrayWithNestedArrays(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(CollectionType::class, null, [
                'entry_type' => CollectionType::class,
                'entry_options' => [
                    'entry_type' => TextType::class,
                    'allow_add' => true,
                ],
                'label' => 'Nested Items',
                'allow_add' => true,
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('array', $schema['type']);
        $this->assertSame('Nested Items', $schema['title']);
        $this->assertArrayHasKey('items', $schema);
    }

    public function testTransformArrayWithCustomLabel(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(CollectionType::class, null, [
                'entry_type' => TextType::class,
                'label' => 'Custom Label',
                'allow_add' => true,
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('Custom Label', $schema['title']);
    }

    public function testTransformArrayWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(CollectionType::class, null, [
                'entry_type' => TextType::class,
                'label' => 'Items with Attrs',
                'allow_add' => true,
                'attr' => [
                    'class' => 'collection-field',
                    'data-test' => 'true',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame('collection-field', $schema['attr']['class']);
        $this->assertSame('true', $schema['attr']['data-test']);
    }
}

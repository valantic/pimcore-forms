<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\NumberTransformer;

#[AllowMockObjectsWithoutExpectations]
class NumberTransformerTest extends TestCase
{
    private NumberTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new NumberTransformer($this->translator, null);
    }

    public function testTransformBasicNumber(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(NumberType::class, null, [
                'label' => 'Price',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('number', $schema['type']);
        $this->assertSame('Price', $schema['title']);
    }

    public function testTransformNumberWithDecimalHandling(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(NumberType::class, null, [
                'label' => 'Amount',
                'scale' => 2,
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('number', $schema['type']);
        $this->assertSame('Amount', $schema['title']);
    }

    public function testTransformNumberWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(NumberType::class, null, [
                'label' => 'Percentage',
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01,
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('number', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame(0, $schema['attr']['min']);
        $this->assertSame(100, $schema['attr']['max']);
        $this->assertSame(0.01, $schema['attr']['step']);
    }
}

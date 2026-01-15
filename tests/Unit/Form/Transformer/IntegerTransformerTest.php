<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\IntegerTransformer;

#[AllowMockObjectsWithoutExpectations]
class IntegerTransformerTest extends TestCase
{
    private IntegerTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new IntegerTransformer($this->translator, null);
    }

    public function testTransformBasicInteger(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(IntegerType::class, null, [
                'label' => 'Age',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('integer', $schema['type']);
        $this->assertSame('Age', $schema['title']);
    }

    public function testTransformIntegerWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(IntegerType::class, null, [
                'label' => 'Quantity',
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('integer', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame(1, $schema['attr']['min']);
        $this->assertSame(100, $schema['attr']['max']);
    }
}

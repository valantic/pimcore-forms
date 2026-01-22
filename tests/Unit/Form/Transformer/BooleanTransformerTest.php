<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\BooleanTransformer;

#[AllowMockObjectsWithoutExpectations]
class BooleanTransformerTest extends TestCase
{
    private BooleanTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new BooleanTransformer($this->translator, null);
    }

    public function testTransformBasicBoolean(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(CheckboxType::class, null, [
                'label' => 'Accept Terms',
                'required' => false,
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('boolean', $schema['type']);
        $this->assertSame('Accept Terms', $schema['title']);
    }

    public function testTransformBooleanWithCustomLabel(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(CheckboxType::class, null, [
                'label' => 'Subscribe to Newsletter',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('boolean', $schema['type']);
        $this->assertSame('Subscribe to Newsletter', $schema['title']);
    }

    public function testTransformBooleanWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(CheckboxType::class, null, [
                'label' => 'Enable Feature',
                'attr' => [
                    'class' => 'custom-checkbox',
                    'data-enabled' => 'true',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('boolean', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame('custom-checkbox', $schema['attr']['class']);
        $this->assertSame('true', $schema['attr']['data-enabled']);
    }
}

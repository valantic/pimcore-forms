<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\ButtonTransformer;

#[AllowMockObjectsWithoutExpectations]
class ButtonTransformerTest extends TestCase
{
    private ButtonTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new ButtonTransformer($this->translator, null);
    }

    public function testTransformButton(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(ButtonType::class, null, [
                'label' => 'Click Me',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertSame('Click Me', $schema['title']);
    }

    public function testTransformSubmitButton(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(SubmitType::class, null, [
                'label' => 'Submit Form',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertSame('Submit Form', $schema['title']);
    }
}

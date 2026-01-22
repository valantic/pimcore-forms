<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\StringTransformer;

#[AllowMockObjectsWithoutExpectations]
class StringTransformerTest extends TestCase
{
    private StringTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new StringTransformer($this->translator, null);
    }

    public function testTransformBasicString(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(TextType::class, null, [
                'label' => 'Name',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertSame('Name', $schema['title']);
    }

    public function testTransformStringWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(TextType::class, null, [
                'label' => 'Username',
                'attr' => [
                    'minlength' => 3,
                    'maxlength' => 20,
                    'pattern' => '^[a-zA-Z0-9_]+$',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame(3, $schema['attr']['minlength']);
        $this->assertSame(20, $schema['attr']['maxlength']);
        $this->assertSame('^[a-zA-Z0-9_]+$', $schema['attr']['pattern']);
    }

    public function testTransformStringWithPlaceholder(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(TextType::class, null, [
                'label' => 'Search',
                'attr' => [
                    'placeholder' => 'Enter search term...',
                    'class' => 'search-input',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame('Enter search term...', $schema['attr']['placeholder']);
        $this->assertSame('search-input', $schema['attr']['class']);
    }
}

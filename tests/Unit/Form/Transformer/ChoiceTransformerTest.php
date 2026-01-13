<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\ChoiceTransformer;

#[AllowMockObjectsWithoutExpectations]
class ChoiceTransformerTest extends TestCase
{
    private ChoiceTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new ChoiceTransformer($this->translator, null);
    }

    public function testTransformSingleChoice(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(ChoiceType::class, null, [
                'label' => 'Country',
                'choices' => [
                    'Germany' => 'de',
                    'France' => 'fr',
                    'Spain' => 'es',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertArrayHasKey('enum', $schema);
        $this->assertArrayHasKey('enum_titles', $schema);
        $this->assertContains('de', $schema['enum']);
        $this->assertContains('fr', $schema['enum']);
        $this->assertContains('es', $schema['enum']);
    }

    public function testTransformMultipleChoice(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(ChoiceType::class, null, [
                'label' => 'Languages',
                'multiple' => true,
                'choices' => [
                    'English' => 'en',
                    'German' => 'de',
                    'French' => 'fr',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('array', $schema['type']);
        $this->assertArrayHasKey('items', $schema);
        $this->assertSame('string', $schema['items']['type']);
        $this->assertArrayHasKey('enum', $schema['items']);
        $this->assertTrue($schema['uniqueItems']);
    }

    public function testTransformExpandedChoice(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(ChoiceType::class, null, [
                'label' => 'Gender',
                'expanded' => true,
                'choices' => [
                    'Male' => 'm',
                    'Female' => 'f',
                    'Other' => 'o',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('string', $schema['type']);
        $this->assertSame('choice-expanded', $schema['widget']);
    }

    public function testTransformExpandedMultipleChoice(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(ChoiceType::class, null, [
                'label' => 'Interests',
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    'Sports' => 'sports',
                    'Music' => 'music',
                    'Reading' => 'reading',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('array', $schema['type']);
        $this->assertSame('choice-multiple-expanded', $schema['widget']);
    }

    public function testTransformRequiredMultipleChoice(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(ChoiceType::class, null, [
                'label' => 'Required Skills',
                'multiple' => true,
                'required' => true,
                'choices' => [
                    'PHP' => 'php',
                    'JavaScript' => 'js',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('array', $schema['type']);
        $this->assertArrayHasKey('minItems', $schema);
        $this->assertSame(1, $schema['minItems']);
    }
}

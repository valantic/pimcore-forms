<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Valantic\PimcoreFormsBundle\Form\Extension\ChoiceTypeExtension;

#[AllowMockObjectsWithoutExpectations]
class ChoiceTypeExtensionTest extends TestCase
{
    private FormFactoryInterface $formFactory;
    private ChoiceTypeExtension $extension;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactory();
        $this->extension = new ChoiceTypeExtension();
    }

    public function testApplyReturnsSchemaUnchangedForNonChoiceType(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', TextType::class)
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = ['type' => 'string'];

        $result = $this->extension->apply($field, $schema);

        $this->assertEquals($schema, $result);
        $this->assertArrayNotHasKey('options', $result);
    }

    public function testApplyAddsChoicesToSchema(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => [
                    'Option 1' => 'opt1',
                    'Option 2' => 'opt2',
                ],
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('choices', $result['options']);
        $this->assertIsArray($result['options']['choices']);
    }

    public function testApplyConvertsChoiceAttributesToCamelCase(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => ['Option 1' => 'opt1'],
                'choice_attr' => [
                    'Option 1' => [
                        'data-value' => 'test',
                        'custom-attr' => 'value',
                    ],
                ],
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('choices', $result['options']);

        $choices = $result['options']['choices'];
        $this->assertNotEmpty($choices);

        $firstChoice = reset($choices);
        $this->assertInstanceOf(ChoiceView::class, $firstChoice);

        $this->assertArrayHasKey('dataValue', $firstChoice->attr);
        $this->assertArrayHasKey('customAttr', $firstChoice->attr);
        $this->assertEquals('test', $firstChoice->attr['dataValue']);
        $this->assertEquals('value', $firstChoice->attr['customAttr']);
    }

    public function testApplyHandlesChoicesWithoutAttributes(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => [
                    'Option 1' => 'opt1',
                    'Option 2' => 'opt2',
                    'Option 3' => 'opt3',
                ],
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('choices', $result['options']);
        $this->assertCount(3, $result['options']['choices']);

        foreach ($result['options']['choices'] as $choice) {
            $this->assertInstanceOf(ChoiceView::class, $choice);
        }
    }

    public function testApplyHandlesMultipleChoicesWithMixedAttributes(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class)
            ->add('field', ChoiceType::class, [
                'choices' => [
                    'First' => 'first',
                    'Second' => 'second',
                ],
                'choice_attr' => [
                    'First' => ['data-id' => '1'],
                    'Second' => ['data-priority' => 'high'],
                ],
            ])
            ->getForm()
        ;

        $field = $form->get('field');
        $schema = [];

        $result = $this->extension->apply($field, $schema);

        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('choices', $result['options']);

        $choices = $result['options']['choices'];
        $this->assertCount(2, $choices);

        $firstChoice = reset($choices);
        $this->assertArrayHasKey('dataId', $firstChoice->attr);

        $secondChoice = next($choices);
        $this->assertArrayHasKey('dataPriority', $secondChoice->attr);
    }
}

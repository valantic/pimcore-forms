<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Integration;

use Limenius\Liform\Liform;
use Limenius\Liform\Resolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\ArrayTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\BooleanTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\ChoiceTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\CompoundTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\IntegerTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\NumberTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\StringTransformer;

/**
 * Integration tests for complete form -> JSON schema transformation.
 */
#[AllowMockObjectsWithoutExpectations]
class JsonSchemaGenerationTest extends TestCase
{
    private Liform $liform;
    private Resolver $resolver;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();
        $this->resolver = new Resolver();
        $this->liform = new Liform($this->resolver);

        // Register transformers - ArrayTransformer and CompoundTransformer need resolver
        $this->resolver->setTransformer('text', new StringTransformer($this->translator, null));
        $this->resolver->setTransformer('textarea', new StringTransformer($this->translator, null));
        $this->resolver->setTransformer('email', new StringTransformer($this->translator, null));
        $this->resolver->setTransformer('integer', new IntegerTransformer($this->translator, null));
        $this->resolver->setTransformer('number', new NumberTransformer($this->translator, null));
        $this->resolver->setTransformer('choice', new ChoiceTransformer($this->translator, null));
        $this->resolver->setTransformer('checkbox', new BooleanTransformer($this->translator, null));
        $this->resolver->setTransformer('collection', new ArrayTransformer($this->translator, $this->resolver));
        $this->resolver->setTransformer('form', new CompoundTransformer($this->translator, $this->resolver));
    }

    public function testBasicTextFieldTransformation(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('name', TextType::class, [
                'label' => 'Full Name',
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertSame('string', $schema['properties']['name']['type']);
        $this->assertSame('Full Name', $schema['properties']['name']['title']);
    }

    public function testComplexFormWithMultipleFieldTypes(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('age', IntegerType::class, ['label' => 'Age'])
            ->add('subscribe', CheckboxType::class, ['label' => 'Subscribe'])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertCount(4, $schema['properties']);

        // Check each field type
        $this->assertSame('string', $schema['properties']['name']['type']);
        $this->assertSame('string', $schema['properties']['email']['type']);
        $this->assertSame('integer', $schema['properties']['age']['type']);
        $this->assertSame('boolean', $schema['properties']['subscribe']['type']);
    }

    public function testFormWithAttributes(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'minlength' => 3,
                    'maxlength' => 20,
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertArrayHasKey('properties', $schema);
        $this->assertSame('string', $schema['properties']['username']['type']);
        $this->assertArrayHasKey('attr', $schema['properties']['username']);
        $this->assertSame(3, $schema['properties']['username']['attr']['minlength']);
        $this->assertSame(20, $schema['properties']['username']['attr']['maxlength']);
    }

    public function testChoiceFieldTransformation(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('country', ChoiceType::class, [
                'label' => 'Country',
                'choices' => [
                    'Germany' => 'de',
                    'France' => 'fr',
                    'Spain' => 'es',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertArrayHasKey('properties', $schema);
        $this->assertSame('string', $schema['properties']['country']['type']);
        $this->assertArrayHasKey('enum', $schema['properties']['country']);
        $this->assertContains('de', $schema['properties']['country']['enum']);
        $this->assertContains('fr', $schema['properties']['country']['enum']);
        $this->assertContains('es', $schema['properties']['country']['enum']);
    }

    public function testMultipleChoiceFieldTransformation(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('languages', ChoiceType::class, [
                'label' => 'Languages',
                'multiple' => true,
                'choices' => [
                    'English' => 'en',
                    'German' => 'de',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertArrayHasKey('properties', $schema);
        $this->assertSame('array', $schema['properties']['languages']['type']);
        $this->assertArrayHasKey('items', $schema['properties']['languages']);
        $this->assertSame('string', $schema['properties']['languages']['items']['type']);
    }

    public function testRequiredFieldsInSchema(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('required_field', TextType::class, [
                'label' => 'Required Field',
                'required' => true,
            ])
            ->add('optional_field', TextType::class, [
                'label' => 'Optional Field',
                'required' => false,
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('required_field', $schema['required']);
        $this->assertNotContains('optional_field', $schema['required']);
    }

    public function testSchemaWithIntegerAttributes(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('rating', IntegerType::class, [
                'label' => 'Rating',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                ],
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        $this->assertArrayHasKey('properties', $schema);
        $this->assertSame('integer', $schema['properties']['rating']['type']);
        $this->assertArrayHasKey('attr', $schema['properties']['rating']);
        $this->assertSame(1, $schema['properties']['rating']['attr']['min']);
        $this->assertSame(5, $schema['properties']['rating']['attr']['max']);
    }

    public function testCompleteFormSchemaStructure(): void
    {
        $form = $this->formFactory
            ->createBuilder()
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Age',
                'required' => false,
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
            ])
            ->getForm()
        ;

        $schema = $this->liform->transform($form);

        // Verify top-level structure
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);

        // Verify all fields are present
        $this->assertCount(5, $schema['properties']);
        $this->assertArrayHasKey('firstName', $schema['properties']);
        $this->assertArrayHasKey('lastName', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        $this->assertArrayHasKey('age', $schema['properties']);
        $this->assertArrayHasKey('message', $schema['properties']);

        // Verify required fields
        $this->assertContains('firstName', $schema['required']);
        $this->assertContains('lastName', $schema['required']);
        $this->assertContains('email', $schema['required']);
        $this->assertContains('message', $schema['required']);
        $this->assertNotContains('age', $schema['required']);
    }
}

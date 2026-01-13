<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;
use Valantic\PimcoreFormsBundle\Repository\ChoicesRepository;
use Valantic\PimcoreFormsBundle\Tests\Support\Factories\ConfigurationFactory;

#[AllowMockObjectsWithoutExpectations]
class BuilderTest extends TestCase
{
    private Builder $builder;
    private MockObject $urlGenerator;
    private MockObject $translator;
    private MockObject $formFactory;
    private MockObject $choicesRepository;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->choicesRepository = $this->createMock(ChoicesRepository::class);

        $this->builder = new Builder(
            $this->urlGenerator,
            $this->translator,
            $this->formFactory,
            $this->choicesRepository,
        );
    }

    public function testFormCreatesFormBuilder(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $config['forms']['test_form'];

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->with('test_form', FormType::class, null, ['csrf_protection' => true])
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('valantic_pimcoreforms_form_api', ['name' => 'test_form'])
            ->willReturn('/api/test_form')
        ;

        $result = $this->builder->form('test_form', $formConfig);

        $this->assertInstanceOf(FormBuilderInterface::class, $result);
    }

    public function testFormSetsCorrectMethod(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $config['forms']['test_form'];
        $formConfig['method'] = 'GET';

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->expects($this->once())->method('setMethod')->with('GET')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory->method('createNamedBuilder')->willReturn($mockBuilder);
        $this->urlGenerator->method('generate')->willReturn('/api/test_form');

        $this->builder->form('test_form', $formConfig);
    }

    public function testFieldWithBasicTextFieldReturnsCorrectTypeAndOptions(): void
    {
        $definition = [
            'type' => 'TextType',
            'options' => [
                'label' => 'Name',
                'required' => true,
            ],
            'constraints' => ['NotBlank'],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(TextType::class, $result[0]);
        $this->assertIsArray($result[1]);
        $this->assertArrayHasKey('label', $result[1]);
        $this->assertArrayHasKey('constraints', $result[1]);
    }

    public function testFieldWithTranslationsTranslatesLabel(): void
    {
        $definition = [
            'type' => 'EmailType',
            'options' => [
                'label' => 'form.email_label',
                'required' => true,
            ],
            'constraints' => [],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];
        $formConfig['translate']['field_labels'] = true;

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('form.email_label')
            ->willReturn('Email Address')
        ;

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertEquals('Email Address', $result[1]['label']);
    }

    public function testFieldWithDateTypeAddsSingleTextWidget(): void
    {
        $definition = [
            'type' => 'DateType',
            'options' => [
                'label' => 'Birth Date',
            ],
            'constraints' => [],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertEquals(DateType::class, $result[0]);
        $this->assertEquals('single_text', $result[1]['widget']);
    }

    public function testFieldWithTimeTypeAddsSingleTextWidget(): void
    {
        $definition = [
            'type' => 'TimeType',
            'options' => [
                'label' => 'Time',
            ],
            'constraints' => [],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertEquals(TimeType::class, $result[0]);
        $this->assertEquals('single_text', $result[1]['widget']);
    }

    public function testFieldWithChoiceTypeAndInlineChoicesTranslatesChoices(): void
    {
        $definition = [
            'type' => 'ChoiceType',
            'options' => [
                'label' => 'Country',
                'choices' => [
                    'country.us' => 'us',
                    'country.uk' => 'uk',
                ],
            ],
            'constraints' => [],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];
        $formConfig['translate']['inline_choices'] = true;

        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'country.us' => 'United States',
                    'country.uk' => 'United Kingdom',
                    default => $key,
                };
            })
        ;

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertEquals(ChoiceType::class, $result[0]);
        $this->assertArrayHasKey('choices', $result[1]);
        $this->assertArrayHasKey('United States', $result[1]['choices']);
        $this->assertArrayHasKey('United Kingdom', $result[1]['choices']);
    }

    public function testFieldWithChoiceProviderUsesProviderForChoices(): void
    {
        $mockProvider = $this->createMock(ChoicesInterface::class);
        $mockProvider->method('choices')->willReturn(['Option 1', 'Option 2']);
        $mockProvider->method('choiceLabel')->willReturnCallback(fn ($choice) => $choice);
        $mockProvider->method('choiceAttribute')->willReturn([]);

        $this->choicesRepository
            ->expects($this->once())
            ->method('get')
            ->with('TestChoiceProvider')
            ->willReturn($mockProvider)
        ;

        $definition = [
            'type' => 'ChoiceType',
            'options' => [
                'label' => 'Select Option',
            ],
            'constraints' => [],
            'provider' => 'TestChoiceProvider',
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertEquals(ChoiceType::class, $result[0]);
        $this->assertArrayHasKey('choices', $result[1]);
        $this->assertArrayHasKey('choice_value', $result[1]);
        $this->assertArrayHasKey('choice_label', $result[1]);
        $this->assertArrayHasKey('choice_attr', $result[1]);
    }

    public function testFieldWithMultipleConstraintsBuildsAllConstraints(): void
    {
        $definition = [
            'type' => 'EmailType',
            'options' => [
                'label' => 'Email',
            ],
            'constraints' => ['NotBlank', 'Email'],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertArrayHasKey('constraints', $result[1]);
        $this->assertIsArray($result[1]['constraints']);
        $this->assertCount(2, $result[1]['constraints']);
        $this->assertInstanceOf(NotBlank::class, $result[1]['constraints'][0]);
        $this->assertInstanceOf(Email::class, $result[1]['constraints'][1]);
    }

    public function testFieldWithConstraintOptionsBuildsConstraintWithOptions(): void
    {
        $definition = [
            'type' => 'TextType',
            'options' => [
                'label' => 'Username',
            ],
            'constraints' => [
                ['NotBlank' => ['message' => 'Username is required']],
            ],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertArrayHasKey('constraints', $result[1]);
        $this->assertCount(1, $result[1]['constraints']);
        $this->assertInstanceOf(NotBlank::class, $result[1]['constraints'][0]);
    }

    public function testFieldWithFullyQualifiedClassNameUsesClassName(): void
    {
        $definition = [
            'type' => EmailType::class,
            'options' => [],
            'constraints' => [Email::class],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertEquals(EmailType::class, $result[0]);
        $this->assertInstanceOf(Email::class, $result[1]['constraints'][0]);
    }

    public function testFieldWithNoConstraintsReturnsEmptyConstraints(): void
    {
        $definition = [
            'type' => 'TextType',
            'options' => ['label' => 'Name'],
            'constraints' => [],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertArrayNotHasKey('constraints', $result[1]);
    }

    public function testFieldWithChoiceConstraintInheritsChoicesFromOptions(): void
    {
        $choices = ['Option 1' => 'opt1', 'Option 2' => 'opt2'];

        $definition = [
            'type' => 'ChoiceType',
            'options' => [
                'label' => 'Select',
                'choices' => $choices,
                'multiple' => false,
            ],
            'constraints' => ['Choice'],
            'provider' => null,
        ];

        $formConfig = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $formConfig['forms']['test_form'];

        $result = $this->builder->field('test_form', $definition, $formConfig);

        $this->assertArrayHasKey('constraints', $result[1]);
        $constraint = $result[1]['constraints'][0];
        $this->assertInstanceOf(\Symfony\Component\Validator\Constraints\Choice::class, $constraint);
    }
}

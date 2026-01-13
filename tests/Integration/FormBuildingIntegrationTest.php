<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Repository\ChoicesRepository;
use Valantic\PimcoreFormsBundle\Tests\Support\Factories\ConfigurationFactory;

/**
 * Integration tests for the Config -> Builder -> Form flow.
 */
#[AllowMockObjectsWithoutExpectations]
class FormBuildingIntegrationTest extends TestCase
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

    public function testCompleteFormBuildingFlow(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('contact');
        $formConfig = $config['forms']['contact'];

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator
            ->method('generate')
            ->willReturn('/api/contact')
        ;

        $result = $this->builder->form('contact', $formConfig);

        $this->assertInstanceOf(FormBuilderInterface::class, $result);
    }

    public function testFieldAdditionWithConstraints(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $formConfig = $config['forms']['test_form'];

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->method('createNamedBuilder')
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator->method('generate')->willReturn('/api/test');

        $form = $this->builder->form('test_form', $formConfig);

        // Test that we can get field configuration
        $fieldDefinition = $formConfig['fields']['name'];
        [$type, $options] = $this->builder->field('test_form', $fieldDefinition, $formConfig);

        $this->assertSame(TextType::class, $type);
        $this->assertArrayHasKey('constraints', $options);
    }

    public function testTranslationIntegration(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('translated');
        $formConfig = $config['forms']['translated'];

        $this->translator
            ->expects($this->atLeastOnce())
            ->method('trans')
            ->willReturnCallback(fn ($key) => "translated_{$key}")
        ;

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->method('createNamedBuilder')
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator->method('generate')->willReturn('/api/translated');

        $form = $this->builder->form('translated', $formConfig);

        $fieldDefinition = $formConfig['fields']['name'];
        [$type, $options] = $this->builder->field('translated', $fieldDefinition, $formConfig);

        $this->assertIsArray($options);
    }

    public function testMultipleFieldTypesBuilding(): void
    {
        $config = [
            'forms' => [
                'multi_type' => [
                    'csrf' => false,
                    'method' => 'POST',
                    'translate' => ['field_labels' => false, 'inline_choices' => false],
                    'fields' => [
                        'text_field' => [
                            'type' => 'TextType',
                            'options' => ['label' => 'Text'],
                            'constraints' => [],
                            'provider' => null,
                        ],
                        'email_field' => [
                            'type' => 'EmailType',
                            'options' => ['label' => 'Email'],
                            'constraints' => [],
                            'provider' => null,
                        ],
                    ],
                    'outputs' => [],
                    'redirect_url' => null,
                    'redirect_handler' => null,
                    'input_handler' => null,
                ],
            ],
        ];

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->method('createNamedBuilder')
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator->method('generate')->willReturn('/api/multi');

        $form = $this->builder->form('multi_type', $config['forms']['multi_type']);

        // Test text field
        [$textType, $textOptions] = $this->builder->field(
            'multi_type',
            $config['forms']['multi_type']['fields']['text_field'],
            $config['forms']['multi_type'],
        );
        $this->assertSame(TextType::class, $textType);

        // Test email field
        [$emailType, $emailOptions] = $this->builder->field(
            'multi_type',
            $config['forms']['multi_type']['fields']['email_field'],
            $config['forms']['multi_type'],
        );
        $this->assertSame(EmailType::class, $emailType);
    }

    public function testFormBuilderWithCsrfEnabled(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('csrf_form');
        $formConfig = $config['forms']['csrf_form'];
        $formConfig['csrf'] = true;

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->with('csrf_form', $this->anything(), null, $this->callback(fn ($options) => $options['csrf_protection'] === true))
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator->method('generate')->willReturn('/api/csrf_form');

        $form = $this->builder->form('csrf_form', $formConfig);

        $this->assertInstanceOf(FormBuilderInterface::class, $form);
    }

    public function testFormBuilderWithCsrfDisabled(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('no_csrf_form');
        $formConfig = $config['forms']['no_csrf_form'];
        $formConfig['csrf'] = false;

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->with('no_csrf_form', $this->anything(), null, $this->callback(fn ($options) => $options['csrf_protection'] === false))
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator->method('generate')->willReturn('/api/no_csrf_form');

        $form = $this->builder->form('no_csrf_form', $formConfig);

        $this->assertInstanceOf(FormBuilderInterface::class, $form);
    }

    public function testFormActionUrlGeneration(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('url_test');
        $formConfig = $config['forms']['url_test'];

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->method('setMethod')->willReturnSelf();
        $mockBuilder->expects($this->once())
            ->method('setAction')
            ->with('/api/forms/url_test')
            ->willReturnSelf()
        ;

        $this->formFactory
            ->method('createNamedBuilder')
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('valantic_pimcoreforms_form_api', ['name' => 'url_test'])
            ->willReturn('/api/forms/url_test')
        ;

        $this->builder->form('url_test', $formConfig);
    }

    public function testFormMethodConfiguration(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('method_test');
        $formConfig = $config['forms']['method_test'];
        $formConfig['method'] = 'GET';

        $mockBuilder = $this->createMock(FormBuilderInterface::class);
        $mockBuilder->expects($this->once())
            ->method('setMethod')
            ->with('GET')
            ->willReturnSelf()
        ;
        $mockBuilder->method('setAction')->willReturnSelf();

        $this->formFactory
            ->method('createNamedBuilder')
            ->willReturn($mockBuilder)
        ;

        $this->urlGenerator->method('generate')->willReturn('/api/method_test');

        $this->builder->form('method_test', $formConfig);
    }
}

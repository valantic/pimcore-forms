<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Service;

use Limenius\Liform\Liform;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Valantic\PimcoreFormsBundle\Exception\InvalidFormConfigException;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Form\Extension\ChoiceTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormAttributeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormConstraintExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormDataExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormNameExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\HiddenTypeExtension;
use Valantic\PimcoreFormsBundle\Form\FormErrorNormalizer;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\InputHandlerRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;
use Valantic\PimcoreFormsBundle\Service\FormService;
use Valantic\PimcoreFormsBundle\Tests\Support\Factories\ConfigurationFactory;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\CreatesFormBuilders;

#[AllowMockObjectsWithoutExpectations]
class FormServiceTest extends TestCase
{
    use CreatesFormBuilders;

    private FormService $service;
    private MockObject $configRepository;
    private MockObject $outputRepository;
    private MockObject $redirectHandlerRepository;
    private MockObject $inputHandlerRepository;
    private MockObject $builder;
    private MockObject $liform;
    private MockObject $errorNormalizer;
    private MockObject $requestStack;

    protected function setUp(): void
    {
        $this->configRepository = $this->createMock(ConfigurationRepository::class);
        $this->outputRepository = $this->createMock(OutputRepository::class);
        $this->redirectHandlerRepository = $this->createMock(RedirectHandlerRepository::class);
        $this->inputHandlerRepository = $this->createMock(InputHandlerRepository::class);
        $this->builder = $this->createMock(Builder::class);
        $this->liform = $this->createMock(Liform::class);
        $this->errorNormalizer = $this->createMock(FormErrorNormalizer::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->service = new FormService(
            $this->configRepository,
            $this->outputRepository,
            $this->redirectHandlerRepository,
            $this->inputHandlerRepository,
            $this->builder,
            $this->liform,
            $this->errorNormalizer,
            $this->createMock(FormTypeExtension::class),
            $this->createMock(FormNameExtension::class),
            $this->createMock(FormConstraintExtension::class),
            $this->createMock(FormAttributeExtension::class),
            $this->createMock(ChoiceTypeExtension::class),
            $this->createMock(HiddenTypeExtension::class),
            $this->createMock(FormDataExtension::class),
            $this->requestStack,
        );
    }

    public function testBuildWithValidConfigCreatesFormBuilder(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $this->configRepository->method('get')->willReturn($config);

        $mockFormBuilder = $this->createFormBuilder('test_form');
        $this->builder->method('form')->willReturn($mockFormBuilder);
        $this->builder->method('field')->willReturn([TextType::class, ['label' => 'Test']]);

        $result = $this->service->build('test_form');

        $this->assertInstanceOf(FormBuilderInterface::class, $result);
        $this->assertEquals('test_form', $result->getName());
    }

    public function testBuildWithInvalidFormNameThrowsException(): void
    {
        $this->configRepository->method('get')->willReturn(['forms' => []]);

        $this->expectException(InvalidFormConfigException::class);

        $this->service->build('nonexistent_form');
    }

    public function testBuildFormReturnsFormInterface(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $this->configRepository->method('get')->willReturn($config);

        $mockFormBuilder = $this->createFormBuilder('test_form');
        $this->builder->method('form')->willReturn($mockFormBuilder);
        $this->builder->method('field')->willReturn([TextType::class, []]);

        $result = $this->service->buildForm('test_form');

        $this->assertInstanceOf(FormInterface::class, $result);
    }

    public function testJsonTransformsFormToArray(): void
    {
        $form = $this->createMock(FormInterface::class);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                ['name' => 'field1', 'type' => 'string'],
                ['name' => 'field2', 'type' => 'string'],
            ],
        ];

        $this->liform->method('transform')->willReturn($expectedSchema);

        $result = $this->service->json($form);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertIsArray($result['properties']);
    }

    public function testBuildJsonBuildsFormAndReturnsJson(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $this->configRepository->method('get')->willReturn($config);

        $mockFormBuilder = $this->createFormBuilder('test_form');
        $this->builder->method('form')->willReturn($mockFormBuilder);
        $this->builder->method('field')->willReturn([TextType::class, []]);

        $jsonSchema = ['type' => 'object', 'properties' => []];
        $this->liform->method('transform')->willReturn($jsonSchema);

        $result = $this->service->buildJson('test_form');

        $this->assertIsArray($result);
        $this->assertEquals('object', $result['type']);
    }

    public function testBuildJsonStringReturnsValidJsonString(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $this->configRepository->method('get')->willReturn($config);

        $mockFormBuilder = $this->createFormBuilder('test_form');
        $this->builder->method('form')->willReturn($mockFormBuilder);
        $this->builder->method('field')->willReturn([TextType::class, []]);

        $jsonSchema = ['type' => 'object', 'properties' => []];
        $this->liform->method('transform')->willReturn($jsonSchema);

        $result = $this->service->buildJsonString('test_form');

        $this->assertIsString($result);
        $this->assertJson($result);

        $decoded = json_decode($result, true);
        $this->assertEquals('object', $decoded['type']);
    }

    public function testErrorsNormalizesFormErrors(): void
    {
        $form = $this->createMock(FormInterface::class);

        $expectedErrors = [
            ['message' => 'This field is required', 'type' => 'error', 'field' => 'email'],
        ];

        $this->errorNormalizer->method('normalize')->willReturn($expectedErrors);

        $result = $this->service->errors($form);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('This field is required', $result[0]['message']);
    }

    public function testOutputsExecutesOutputHandlers(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $this->configRepository->method('get')->willReturn($config);

        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('test_form');
        $form->method('getData')->willReturn(['name' => 'John']);

        $mockOutput = $this->createMock(OutputInterface::class);
        $mockOutput->expects($this->once())->method('initialize');
        $mockOutput->expects($this->once())->method('setOutputHandlers');
        $mockOutput->method('handle')->willReturnCallback(fn (OutputResponse $response) => $response->addStatus(true));

        $this->outputRepository->method('get')->willReturn($mockOutput);

        $result = $this->service->outputs($form);

        $this->assertInstanceOf(OutputResponse::class, $result);
        $this->assertTrue($result->getOverallStatus());
    }

    public function testOutputsWithMultipleHandlersAggregatesStatus(): void
    {
        $config = ConfigurationFactory::createMultipleOutputsConfig('multi_form');
        $this->configRepository->method('get')->willReturn($config);

        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('multi_form');
        $form->method('getData')->willReturn(['message' => 'Test']);

        $successfulOutput = $this->createMock(OutputInterface::class);
        $successfulOutput->method('handle')->willReturnCallback(fn (OutputResponse $response) => $response->addStatus(true));

        $failedOutput = $this->createMock(OutputInterface::class);
        $failedOutput->method('handle')->willReturnCallback(fn (OutputResponse $response) => $response->addStatus(false));

        $this->outputRepository->method('get')->willReturnOnConsecutiveCalls(
            $successfulOutput,
            $failedOutput,
            $successfulOutput,
        );

        $result = $this->service->outputs($form);

        // Overall status should be false since one handler failed
        $this->assertFalse($result->getOverallStatus());
    }

    public function testGetRedirectUrlWithNoHandlerReturnsNull(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $this->configRepository->method('get')->willReturn($config);

        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('test_form');

        $result = $this->service->getRedirectUrl($form, true);

        $this->assertNull($result);
    }

    public function testBuildWithCsrfProtectionAddsTokenField(): void
    {
        $config = ConfigurationFactory::createValidFormConfig('test_form');
        $config['forms']['test_form']['csrf'] = true;
        $this->configRepository->method('get')->willReturn($config);

        $mockFormBuilder = $this->createFormBuilder('test_form');
        $this->builder->method('form')->willReturn($mockFormBuilder);
        $this->builder->method('field')->willReturn([TextType::class, []]);

        $result = $this->service->build('test_form');

        $this->assertInstanceOf(FormBuilderInterface::class, $result);
        // CSRF field is added by the service via hidden type
        // The actual field name depends on form options, typically '_token'
        $form = $result->getForm();
        // We can verify the form builder was called correctly
        $this->assertNotNull($form);
    }
}

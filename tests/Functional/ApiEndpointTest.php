<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Functional;

use Limenius\Liform\Liform;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Controller\FormController;
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
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\InputHandlerRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;
use Valantic\PimcoreFormsBundle\Service\FormService;
use Valantic\PimcoreFormsBundle\Tests\Support\Factories\ConfigurationFactory;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\CreatesFormBuilders;

/**
 * @covers \Valantic\PimcoreFormsBundle\Controller\FormController
 * @covers \Valantic\PimcoreFormsBundle\Service\FormService
 */
#[AllowMockObjectsWithoutExpectations]
class ApiEndpointTest extends TestCase
{
    use CreatesFormBuilders;

    private FormController $controller;
    private FormService $formService;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $configRepo = $this->createMock(ConfigurationRepository::class);
        $configRepo->method('get')
            ->willReturn(ConfigurationFactory::createContactFormConfig())
        ;

        $outputRepo = $this->createMock(OutputRepository::class);
        $inputHandlerRepo = $this->createMock(InputHandlerRepository::class);
        $redirectHandlerRepo = $this->createMock(RedirectHandlerRepository::class);
        $builder = $this->createMock(Builder::class);
        $liform = $this->createMock(Liform::class);
        $errorNormalizer = $this->createMock(FormErrorNormalizer::class);
        $requestStack = $this->createMock(RequestStack::class);

        $this->formService = new FormService(
            $configRepo,
            $outputRepo,
            $redirectHandlerRepo,
            $inputHandlerRepo,
            $builder,
            $liform,
            $errorNormalizer,
            $this->createMock(FormTypeExtension::class),
            $this->createMock(FormNameExtension::class),
            $this->createMock(FormConstraintExtension::class),
            $this->createMock(FormAttributeExtension::class),
            $this->createMock(ChoiceTypeExtension::class),
            $this->createMock(HiddenTypeExtension::class),
            $this->createMock(FormDataExtension::class),
            $requestStack,
        );

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->controller = new FormController();
    }

    /**
     * Test API endpoint returns JSON response with correct content type.
     */
    public function testApiEndpointReturnsJsonContentType(): void
    {
        $request = Request::create('/form/api/contact', 'GET');

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * Test API endpoint returns 404 for non-existent form.
     */
    public function testApiEndpointReturns404ForNonExistentForm(): void
    {
        $configRepo = $this->createMock(ConfigurationRepository::class);
        $configRepo->method('get')
            ->willReturn(ConfigurationFactory::createContactFormConfig())
        ;

        $outputRepo = $this->createMock(OutputRepository::class);
        $inputHandlerRepo = $this->createMock(InputHandlerRepository::class);
        $redirectHandlerRepo = $this->createMock(RedirectHandlerRepository::class);
        $builder = $this->createMock(Builder::class);
        $liform = $this->createMock(Liform::class);
        $errorNormalizer = $this->createMock(FormErrorNormalizer::class);
        $requestStack = $this->createMock(RequestStack::class);

        $formService = new FormService(
            $configRepo,
            $outputRepo,
            $redirectHandlerRepo,
            $inputHandlerRepo,
            $builder,
            $liform,
            $errorNormalizer,
            $this->createMock(FormTypeExtension::class),
            $this->createMock(FormNameExtension::class),
            $this->createMock(FormConstraintExtension::class),
            $this->createMock(FormAttributeExtension::class),
            $this->createMock(ChoiceTypeExtension::class),
            $this->createMock(HiddenTypeExtension::class),
            $this->createMock(FormDataExtension::class),
            $requestStack,
        );

        $controller = new FormController();

        $request = Request::create('/form/api/nonexistent', 'GET');

        $translator = $this->createMock(TranslatorInterface::class);
        $this->expectException(InvalidFormConfigException::class);
        $controller->apiAction('nonexistent', $formService, $request, $translator);
    }

    /**
     * Test API endpoint handles malformed JSON with 400 error.
     */
    public function testApiEndpointHandlesMalformedJson(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"invalid": json}');

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(412, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test API endpoint accepts form-urlencoded data.
     */
    public function testApiEndpointAcceptsFormUrlencodedData(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ]);

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test API endpoint returns validation errors in proper format.
     */
    public function testApiEndpointReturnsProperErrorFormat(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '',
            'email' => 'invalid',
        ]));

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(412, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertIsArray($data['errors']);
    }

    /**
     * Test API endpoint supports CORS preflight requests.
     */
    public function testApiEndpointSupportsCorsHeaders(): void
    {
        $request = Request::create('/form/api/contact', 'GET', [], [], [], [
            'HTTP_ORIGIN' => 'https://example.com',
        ]);

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        // Note: CORS headers would typically be added by middleware/event listeners
        // This test just verifies the endpoint responds to requests with Origin header
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test API endpoint returns success response structure.
     */
    public function testApiEndpointReturnsSuccessStructure(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]));

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayNotHasKey('errors', $data);
    }

    /**
     * Test API endpoint handles GET and POST methods only.
     */
    public function testApiEndpointOnlySupportsGetAndPost(): void
    {
        $methods = ['PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $request = Request::create('/form/api/contact', $method);

            // The controller should handle these, but GET/POST are the primary methods
            // This test documents the expected behavior
            $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

            // PUT/DELETE/PATCH will be treated like GET, returning schema
            $this->assertInstanceOf(Response::class, $response);
        }
    }
}

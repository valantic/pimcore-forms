<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;
use Valantic\PimcoreFormsBundle\Controller\FormController;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;
use Valantic\PimcoreFormsBundle\Service\FormService;
use Valantic\PimcoreFormsBundle\Tests\Support\Factories\ConfigurationFactory;
use Valantic\PimcoreFormsBundle\Tests\Support\RedirectHandlerStub;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\CreatesFormServices;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreDocument;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreMail;

/**
 * @covers \Valantic\PimcoreFormsBundle\Controller\FormController
 * @covers \Valantic\PimcoreFormsBundle\Service\FormService
 */
#[AllowMockObjectsWithoutExpectations]
class FormSubmissionFlowTest extends TestCase
{
    use CreatesFormServices;
    use MocksPimcoreDocument;
    use MocksPimcoreMail;

    private FormController $controller;
    private FormService $formService;
    private TranslatorInterface $translator;
    private MockObject $twig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formService = $this->createRealFormService(ConfigurationFactory::createContactFormConfig());
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->twig = $this->createMock(Environment::class);
        $this->twig->method('render')->willReturn('<html><body>form</body></html>');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn ($id) => $id === 'twig');
        $container->method('get')->willReturnCallback(fn ($id) => $id === 'twig' ? $this->twig : null);

        $this->controller = new FormController();
        $this->controller->setContainer($container);
    }

    /**
     * Test complete GET schema flow.
     */
    public function testGetSchemaReturnsJsonSchema(): void
    {
        $request = Request::create('/form/api/contact', 'GET');

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('properties', $data['data']);
    }

    /**
     * Test GET request returns form schema with all fields.
     */
    public function testGetSchemaContainsAllFormFields(): void
    {
        $request = Request::create('/form/api/contact', 'GET');

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);
        $data = json_decode($response->getContent(), true);

        $fieldNames = array_column($data['data']['properties'], 'name');
        $this->assertContains('name', $fieldNames);
        $this->assertContains('email', $fieldNames);
        $this->assertContains('message', $fieldNames);
        $this->assertArrayHasKey('required', $data['data']);
        $this->assertContains('name', $data['data']['required']);
        $this->assertContains('email', $data['data']['required']);
    }

    /**
     * Test POST with valid data returns success.
     */
    public function testPostValidDataReturnsSuccess(): void
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
        $this->assertSame(MessageConstants::MESSAGE_TYPE_SUCCESS, $data['messages'][0]['type']);
    }

    /**
     * Test POST with invalid data returns validation errors.
     */
    public function testPostInvalidDataReturnsValidationErrors(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '',
            'email' => 'invalid-email',
            'message' => '',
        ]));

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(412, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('messages', $data);
        $this->assertNotEmpty($data['messages']);
        $this->assertSame(MessageConstants::MESSAGE_TYPE_ERROR, $data['messages'][0]['type']);
    }

    /**
     * Test POST with missing required fields returns errors.
     */
    public function testPostMissingRequiredFieldsReturnsErrors(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'John Doe',
        ]));

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(412, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('messages', $data);
        $this->assertNotEmpty($data['messages']);
    }

    /**
     * Test CSRF token validation on POST requests.
     */
    public function testPostWithInvalidCsrfTokenReturnsError(): void
    {
        $config = ConfigurationFactory::createContactFormConfig();
        $config['forms']['contact']['csrf'] = true;

        $formService = $this->createRealFormService($config);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = new FormController();

        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
            '_token' => 'invalid_token',
        ]));

        $response = $controller->apiAction('contact', $formService, $request, $translator);

        $this->assertEquals(412, $response->getStatusCode());
    }

    /**
     * Test redirect URL is returned in successful response.
     */
    public function testSuccessResponseContainsRedirectUrl(): void
    {
        $config = ConfigurationFactory::createContactFormConfig();
        $config['forms']['contact']['redirect_handler'] = 'redirect_stub';

        $redirectHandlerRepo = $this->createMock(RedirectHandlerRepository::class);
        $redirectHandlerRepo->method('get')->willReturn(new RedirectHandlerStub());

        $formService = $this->createRealFormService($config, redirectHandlerRepository: $redirectHandlerRepo);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = new FormController();

        $request = Request::create('/form/api/contact', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]));

        $response = $controller->apiAction('contact', $formService, $request, $translator);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertEquals('https://example.com/success', $data['redirectUrl']);
    }

    /**
     * Test HTML action returns rendered form template.
     */
    public function testHtmlActionReturnsFormTemplate(): void
    {
        $request = Request::create('/form/html/contact', 'GET');

        $response = $this->controller->htmlAction('contact', $this->formService);
        $response->prepare($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type') ?? '');
    }

    /**
     * Test Vue.js UI action returns JavaScript application.
     */
    public function testUiActionReturnsVueApplication(): void
    {
        $request = Request::create('/form/ui/contact', 'GET');

        $response = $this->controller->uiAction('contact');
        $response->prepare($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type') ?? '');
    }

    /**
     * Test mail document action returns email template.
     */
    public function testMailDocumentActionReturnsEmailTemplate(): void
    {
        $request = new Request();
        $request->attributes->set('form_contents', '<p>Test content</p>');
        $request->attributes->set('_route', 'test_route');

        $result = $this->controller->mailDocumentAction($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form_contents', $result);
        $this->assertEquals('<p>Test content</p>', $result['form_contents']);
        $this->assertArrayNotHasKey('_route', $result);
    }
}

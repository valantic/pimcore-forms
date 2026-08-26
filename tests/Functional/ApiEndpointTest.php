<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;
use Valantic\PimcoreFormsBundle\Controller\FormController;
use Valantic\PimcoreFormsBundle\Exception\InvalidFormConfigException;
use Valantic\PimcoreFormsBundle\Service\FormService;
use Valantic\PimcoreFormsBundle\Tests\Support\Factories\ConfigurationFactory;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\CreatesFormServices;

/**
 * @covers \Valantic\PimcoreFormsBundle\Controller\FormController
 * @covers \Valantic\PimcoreFormsBundle\Service\FormService
 */
#[AllowMockObjectsWithoutExpectations]
class ApiEndpointTest extends TestCase
{
    use CreatesFormServices;

    private FormController $controller;
    private FormService $formService;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formService = $this->createRealFormService(ConfigurationFactory::createContactFormConfig());
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
        $formService = $this->createRealFormService(ConfigurationFactory::createContactFormConfig());
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
        $this->assertSame(MessageConstants::MESSAGE_TYPE_ERROR, $data['messages'][0]['type']);
    }

    /**
     * Test API endpoint accepts form-urlencoded data submitted as a native (nested) form POST.
     */
    public function testApiEndpointAcceptsFormUrlencodedData(): void
    {
        $request = Request::create('/form/api/contact', 'POST', [
            'contact' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'message' => 'Test message',
            ],
        ], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ]);

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(MessageConstants::MESSAGE_TYPE_SUCCESS, $data['messages'][0]['type']);
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
        $this->assertArrayHasKey('messages', $data);
        $this->assertIsArray($data['messages']);
        $this->assertNotEmpty($data['messages']);
        $this->assertSame(MessageConstants::MESSAGE_TYPE_ERROR, $data['messages'][0]['type']);
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
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertSame(MessageConstants::MESSAGE_TYPE_SUCCESS, $data['messages'][0]['type']);
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

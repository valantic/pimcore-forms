<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Functional;

use Limenius\Liform\Liform;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\Email;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Controller\FormController;
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
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreDocument;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreMail;

/**
 * @covers \Valantic\PimcoreFormsBundle\Controller\FormController
 * @covers \Valantic\PimcoreFormsBundle\Service\FormService
 */
#[AllowMockObjectsWithoutExpectations]
class FormSubmissionFlowTest extends TestCase
{
    use CreatesFormBuilders;
    use MocksPimcoreDocument;
    use MocksPimcoreMail;

    private FormController $controller;
    private FormService $formService;
    private FormFactoryInterface $formFactory;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create form factory with CSRF protection
        $session = new Session(new MockArraySessionStorage());
        $requestStack = new RequestStack();
        $request = new Request();
        $request->setSession($session);
        $requestStack->push($request);

        $csrfTokenManager = new CsrfTokenManager(
            new UriSafeTokenGenerator(),
            new SessionTokenStorage($requestStack),
        );

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($csrfTokenManager))
            ->getFormFactory()
        ;

        // Create repositories
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

        // Create form service
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
        // Create controller
        $this->controller = new FormController();
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
        $this->assertArrayHasKey('schema', $data);
        $this->assertArrayHasKey('properties', $data['schema']);
    }

    /**
     * Test GET request returns form schema with all fields.
     */
    public function testGetSchemaContainsAllFormFields(): void
    {
        $request = Request::create('/form/api/contact', 'GET');

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('name', $data['schema']['properties']);
        $this->assertArrayHasKey('email', $data['schema']['properties']);
        $this->assertArrayHasKey('message', $data['schema']['properties']);
        $this->assertArrayHasKey('required', $data['schema']);
        $this->assertContains('name', $data['schema']['required']);
        $this->assertContains('email', $data['schema']['required']);
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
        $this->assertTrue($data['success']);
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
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertNotEmpty($data['errors']);
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
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test CSRF token validation on POST requests.
     */
    public function testPostWithInvalidCsrfTokenReturnsError(): void
    {
        // Create config with CSRF enabled
        $configRepo = $this->createMock(ConfigurationRepository::class);
        $config = ConfigurationFactory::createContactFormConfig();
        $config['forms']['contact']['csrf'] = true;
        $configRepo->method('get')->willReturn($config);

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
        $configRepo = $this->createMock(ConfigurationRepository::class);
        $config = ConfigurationFactory::createContactFormConfig();
        $config['forms']['contact']['redirectUrl'] = '/thank-you';
        $configRepo->method('get')->willReturn($config);

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
        $this->assertArrayHasKey('redirect', $data);
        $this->assertEquals('/thank-you', $data['redirect']);
    }

    /**
     * Test HTML action returns rendered form template.
     */
    public function testHtmlActionReturnsFormTemplate(): void
    {
        $request = Request::create('/form/html/contact', 'GET');

        $response = $this->controller->htmlAction('contact', $this->formService);

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

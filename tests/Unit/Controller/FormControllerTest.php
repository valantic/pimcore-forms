<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;
use Valantic\PimcoreFormsBundle\Controller\FormController;
use Valantic\PimcoreFormsBundle\Http\ApiResponse;
use Valantic\PimcoreFormsBundle\Model\Message;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Service\FormService;

#[AllowMockObjectsWithoutExpectations]
class FormControllerTest extends TestCase
{
    private FormController $controller;
    private MockObject $formService;
    private MockObject $translator;
    private MockObject $form;
    private MockObject $container;
    private MockObject $twig;

    protected function setUp(): void
    {
        $this->formService = $this->createMock(FormService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        // Setup container with Twig service
        $this->container->method('has')->willReturnCallback(fn ($id) => $id === 'twig');
        $this->container->method('get')->willReturnCallback(fn ($id) => $id === 'twig' ? $this->twig : null);

        $this->controller = new FormController();
        $this->controller->setContainer($this->container);
    }

    // uiAction tests
    public function testUiActionReturnsResponseWithFormName(): void
    {
        $this->twig->method('render')->willReturn('<div>Vue App</div>');

        $response = $this->controller->uiAction('contact_form');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUiActionUsesVueTwigTemplate(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('@ValanticPimcoreForms/vue.html.twig', ['name' => 'test_form'])
            ->willReturn('<div>Vue App</div>')
        ;

        $response = $this->controller->uiAction('test_form');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
    }

    // htmlAction tests
    public function testHtmlActionBuildsFormAndReturnsResponse(): void
    {
        $formView = $this->createMock(FormView::class);
        $this->form->method('createView')->willReturn($formView);

        $this->formService->expects($this->once())
            ->method('buildForm')
            ->with('contact_form')
            ->willReturn($this->form)
        ;

        $this->twig->method('render')->willReturn('<form></form>');

        $response = $this->controller->htmlAction('contact_form', $this->formService);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHtmlActionUsesHtmlTwigTemplate(): void
    {
        $formView = $this->createMock(FormView::class);
        $this->form->method('createView')->willReturn($formView);

        $this->formService->method('buildForm')->willReturn($this->form);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@ValanticPimcoreForms/html.html.twig', $this->anything())
            ->willReturn('<form></form>')
        ;

        $response = $this->controller->htmlAction('newsletter_form', $this->formService);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
    }

    // apiAction tests - GET (schema retrieval)
    public function testApiActionGetReturnsJsonSchema(): void
    {
        $request = Request::create('/api/test_form', 'GET');

        $this->form->method('isSubmitted')->willReturn(false);
        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('buildJson')->willReturn(['type' => 'object', 'properties' => []]);

        $response = $this->controller->apiAction('test_form', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('object', $data['data']['type']);
    }

    // apiAction tests - POST (form submission with valid data)
    public function testApiActionPostWithValidDataReturnsSuccess(): void
    {
        $formData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $request = Request::create('/api/contact', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($formData));

        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(true);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $outputResponse = new OutputResponse();
        $outputResponse->addStatus(true);

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('outputs')->willReturn($outputResponse);
        $this->formService->method('getRedirectUrl')->willReturn(null);

        $this->translator->method('trans')
            ->with('valantic.pimcoreForms.formSubmitSuccess')
            ->willReturn('Form submitted successfully')
        ;

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(1, $data['messages']);
        $this->assertEquals(MessageConstants::MESSAGE_TYPE_SUCCESS, $data['messages'][0]['type']);
    }

    public function testApiActionPostWithValidDataAndRedirectUrl(): void
    {
        $formData = ['email' => 'test@example.com'];
        $request = Request::create('/api/newsletter', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($formData));
        $redirectUrl = '/thank-you';

        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(true);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $outputResponse = new OutputResponse();
        $outputResponse->addStatus(true);

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('outputs')->willReturn($outputResponse);
        $this->formService->method('getRedirectUrl')->willReturn($redirectUrl);

        $this->translator->method('trans')->willReturn('Success');

        $response = $this->controller->apiAction('newsletter', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($redirectUrl, $data['redirectUrl']);
    }

    // apiAction tests - POST (validation errors)
    public function testApiActionPostWithInvalidDataReturnsValidationErrors(): void
    {
        $formData = ['email' => 'invalid-email'];
        $request = Request::create('/api/contact', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($formData));

        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(true);
        $this->form->method('isValid')->willReturn(false);

        $errors = [
            ['type' => MessageConstants::MESSAGE_TYPE_ERROR, 'message' => 'Invalid email format', 'field' => 'email'],
        ];

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('errors')->willReturn($errors);

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(1, $data['messages']);
        $this->assertEquals('Invalid email format', $data['messages'][0]['message']);
    }

    // apiAction tests - Output handler failures
    public function testApiActionPostWithOutputHandlerFailureReturnsError(): void
    {
        $formData = ['message' => 'Test'];
        $request = Request::create('/api/contact', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($formData));

        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(true);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $outputResponse = new OutputResponse();
        $outputResponse->addStatus(false); // Output handler failed

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('outputs')->willReturn($outputResponse);
        $this->formService->method('getRedirectUrl')->willReturn(null);

        $this->translator->method('trans')
            ->with('valantic.pimcoreForms.formSubmitError')
            ->willReturn('Form submission failed')
        ;

        $response = $this->controller->apiAction('contact', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('messages', $data);
        $this->assertEquals(MessageConstants::MESSAGE_TYPE_ERROR, $data['messages'][0]['type']);
        $this->assertEquals('Form submission failed', $data['messages'][0]['message']);
    }

    public function testApiActionPostWithCustomOutputMessages(): void
    {
        $formData = ['data' => 'test'];
        $request = Request::create('/api/form', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($formData));

        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(true);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $outputResponse = new OutputResponse();
        $outputResponse->addStatus(true);
        $customMessage = (new Message())
            ->setType(MessageConstants::MESSAGE_TYPE_INFO)
            ->setMessage('Email sent successfully')
        ;
        $outputResponse->addMessage($customMessage);

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('outputs')->willReturn($outputResponse);
        $this->formService->method('getRedirectUrl')->willReturn(null);

        $response = $this->controller->apiAction('form', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['messages']);
        $this->assertEquals('Email sent successfully', $data['messages'][0]['message']);
    }

    // apiAction tests - JSON parsing
    public function testApiActionHandlesJsonRequestBodyParsing(): void
    {
        $formData = ['field' => 'value'];
        $request = Request::create('/api/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($formData));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($formData)
        ;
        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(false, true);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $outputResponse = new OutputResponse();
        $outputResponse->addStatus(true);

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('outputs')->willReturn($outputResponse);
        $this->formService->method('getRedirectUrl')->willReturn(null);
        $this->translator->method('trans')->willReturn('Success');

        $response = $this->controller->apiAction('test', $this->formService, $request, $this->translator);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testApiActionIgnoresNullJsonRequestBody(): void
    {
        // Test with 'null' as JSON body - this is valid JSON but should not submit the form
        $request = Request::create('/api/test', 'GET', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'null');

        $this->form->expects($this->never())->method('submit');
        $this->form->method('handleRequest')->willReturnSelf();
        $this->form->method('isSubmitted')->willReturn(false);

        $this->formService->method('buildForm')->willReturn($this->form);
        $this->formService->method('buildJson')->willReturn(['type' => 'object']);

        $response = $this->controller->apiAction('test', $this->formService, $request, $this->translator);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    // mailDocumentAction test
    public function testMailDocumentActionFiltersRequestAttributes(): void
    {
        $request = Request::create('/mail-document');
        $request->attributes->set('form_contents', '<h1>Form Data</h1>');
        $request->attributes->set('_route', 'mail_document');
        $request->attributes->set('_controller', 'FormController::mailDocumentAction');
        $request->attributes->set('custom_param', 'value');

        $result = $this->controller->mailDocumentAction($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form_contents', $result);
        $this->assertArrayHasKey('custom_param', $result);
        // Internal Symfony attributes starting with _ should be filtered out
        $this->assertArrayNotHasKey('_route', $result);
        $this->assertArrayNotHasKey('_controller', $result);
    }
}

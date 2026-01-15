<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Examples\CustomOutput;

use App\Form\Output\SlackNotificationOutput;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @covers \App\Form\Output\SlackNotificationOutput
 */
#[AllowMockObjectsWithoutExpectations]
class SlackNotificationOutputTest extends TestCase
{
    private FormFactoryInterface $formFactory;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory = Forms::createFormFactory();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    /**
     * Test successful Slack notification.
     */
    public function testExecuteSendsSlackNotificationSuccessfully(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('json', $options);
                    $this->assertArrayHasKey('timeout', $options);
                    $this->assertEquals(5, $options['timeout']);

                    $json = $options['json'];
                    $this->assertArrayHasKey('text', $json);
                    $this->assertArrayHasKey('attachments', $json);

                    return true;
                }),
            )
            ->willReturn($response)
        ;

        $output = new SlackNotificationOutput($this->httpClient);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('submit', SubmitType::class)
            ->getForm()
        ;

        $form->submit([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello World',
        ]);

        $config = [
            'webhookUrl' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
        ];

        $status = $output->execute($form, $config);

        $this->assertTrue($status->success);
        $this->assertEquals('Slack notification sent successfully', $status->message);
    }

    /**
     * Test missing webhook URL returns error.
     */
    public function testExecuteWithMissingWebhookUrlReturnsError(): void
    {
        $output = new SlackNotificationOutput($this->httpClient);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('name', TextType::class)
            ->getForm()
        ;

        $form->submit(['name' => 'Test']);

        $status = $output->execute($form, []);

        $this->assertFalse($status->success);
        $this->assertEquals('Slack webhook URL is not configured', $status->message);
    }

    /**
     * Test Slack API error returns error status.
     */
    public function testExecuteWithSlackApiErrorReturnsError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);

        $this->httpClient
            ->method('request')
            ->willReturn($response)
        ;

        $output = new SlackNotificationOutput($this->httpClient);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('name', TextType::class)
            ->getForm()
        ;

        $form->submit(['name' => 'Test']);

        $config = [
            'webhookUrl' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
        ];

        $status = $output->execute($form, $config);

        $this->assertFalse($status->success);
        $this->assertStringContainsString('Slack API returned status 500', $status->message);
    }

    /**
     * Test HTTP exception is caught and returned as error.
     */
    public function testExecuteWithHttpExceptionReturnsError(): void
    {
        $this->httpClient
            ->method('request')
            ->willThrowException(new \RuntimeException('Connection failed'))
        ;

        $output = new SlackNotificationOutput($this->httpClient);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('name', TextType::class)
            ->getForm()
        ;

        $form->submit(['name' => 'Test']);

        $config = [
            'webhookUrl' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
        ];

        $status = $output->execute($form, $config);

        $this->assertFalse($status->success);
        $this->assertStringContainsString('Connection failed', $status->message);
    }

    /**
     * Test optional Slack configuration is included.
     */
    public function testExecuteIncludesOptionalSlackConfiguration(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
                $this->callback(function ($options) {
                    $json = $options['json'];
                    $this->assertEquals('#notifications', $json['channel']);
                    $this->assertEquals('Form Bot', $json['username']);
                    $this->assertEquals(':robot_face:', $json['icon_emoji']);

                    return true;
                }),
            )
            ->willReturn($response)
        ;

        $output = new SlackNotificationOutput($this->httpClient);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('name', TextType::class)
            ->getForm()
        ;

        $form->submit(['name' => 'Test']);

        $config = [
            'webhookUrl' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
            'channel' => '#notifications',
            'username' => 'Form Bot',
            'icon' => ':robot_face:',
        ];

        $status = $output->execute($form, $config);

        $this->assertTrue($status->success);
    }

    /**
     * Test form fields are properly formatted in Slack message.
     */
    public function testExecuteFormatsFormFieldsProperly(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
                $this->callback(function ($options) {
                    $json = $options['json'];
                    $fields = $json['attachments'][0]['fields'];

                    // Check that fields are present and formatted
                    $this->assertCount(3, $fields); // name, email, message (submit button excluded)

                    // Check field structure
                    foreach ($fields as $field) {
                        $this->assertArrayHasKey('title', $field);
                        $this->assertArrayHasKey('value', $field);
                        $this->assertArrayHasKey('short', $field);
                    }

                    // Check humanized field names
                    $titles = array_column($fields, 'title');
                    $this->assertContains('Name', $titles);
                    $this->assertContains('Email', $titles);
                    $this->assertContains('Message', $titles);

                    return true;
                }),
            )
            ->willReturn($response)
        ;

        $output = new SlackNotificationOutput($this->httpClient);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('submit', SubmitType::class)
            ->getForm()
        ;

        $form->submit([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello World',
        ]);

        $config = [
            'webhookUrl' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
        ];

        $status = $output->execute($form, $config);

        $this->assertTrue($status->success);
    }
}

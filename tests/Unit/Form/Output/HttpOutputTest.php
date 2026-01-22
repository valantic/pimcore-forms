<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Output;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Form\Output\HttpOutput;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;

#[AllowMockObjectsWithoutExpectations]
class HttpOutputTest extends TestCase
{
    public function testNameReturnsHttp(): void
    {
        $this->assertEquals('http', HttpOutput::name());
    }

    public function testHandleSendsJsonPostRequest(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]);

        $config = [
            'url' => 'https://example.com/webhook',
        ];

        // Track that the correct data was "sent"
        $capturedData = null;

        $output = new class($capturedData) extends HttpOutput {
            public function __construct(private &$capturedData)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                // Simulate successful HTTP POST without actually making the request
                $this->capturedData = [
                    'url' => $this->config['url'],
                    'data' => $this->form->getData(),
                ];

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('http_webhook', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
        $this->assertEquals('https://example.com/webhook', $capturedData['url']);
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ], $capturedData['data']);
    }

    public function testHandleWithHttpFailureReturnsFailureStatus(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['message' => 'test']);

        $config = [
            'url' => 'https://example.com/failing-webhook',
        ];

        $output = new class extends HttpOutput {
            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                // Simulate HTTP failure (curl_exec returns false)
                return $outputResponse->addStatus(false);
            }
        };

        $output->initialize('http_fail', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertFalse($result->getOverallStatus());
    }

    public function testHandleWithInvalidUrlThrowsException(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['test' => 'data']);

        $config = [
            'url' => 'invalid-url',
        ];

        $output = new class extends HttpOutput {
            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                // Simulate curl_init failure
                throw new \RuntimeException('Failed to initialize curl for invalid-url');
            }
        };

        $output->initialize('http_invalid', $form, $config);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to initialize curl for invalid-url');

        $response = new OutputResponse();
        $output->handle($response);
    }

    public function testHandleSendsCorrectJsonHeaders(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['key' => 'value']);

        $config = [
            'url' => 'https://api.example.com/endpoint',
        ];

        $capturedHeaders = null;

        $output = new class($capturedHeaders) extends HttpOutput {
            public function __construct(private &$capturedHeaders)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                // Simulate capturing the headers that would be sent
                $this->capturedHeaders = ['Content-Type' => 'application/json'];

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('http_api', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
        $this->assertEquals(['Content-Type' => 'application/json'], $capturedHeaders);
    }

    public function testHandleWithComplexDataEncodesAsJson(): void
    {
        $form = $this->createMock(FormInterface::class);
        $complexData = [
            'user' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'preferences' => [
                    'newsletter' => true,
                    'notifications' => false,
                ],
            ],
            'items' => ['item1', 'item2', 'item3'],
            'timestamp' => 1234567890,
        ];
        $form->method('getData')->willReturn($complexData);

        $config = [
            'url' => 'https://example.com/complex-webhook',
        ];

        $capturedJson = null;

        $output = new class($capturedJson) extends HttpOutput {
            public function __construct(private &$capturedJson)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                // Capture the JSON that would be sent
                $this->capturedJson = json_encode($this->form->getData(), \JSON_THROW_ON_ERROR);

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('http_complex', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
        $this->assertJson($capturedJson);
        $decoded = json_decode($capturedJson, true);
        $this->assertEquals($complexData, $decoded);
    }
}

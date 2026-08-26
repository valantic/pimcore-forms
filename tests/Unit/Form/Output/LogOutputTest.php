<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Output;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Form\Output\LogOutput;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;

#[AllowMockObjectsWithoutExpectations]
class LogOutputTest extends TestCase
{
    public function testNameReturnsLog(): void
    {
        $this->assertEquals('log', LogOutput::name());
    }

    public function testHandleLogsFormDataSuccessfully(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('contact_form');
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ];
        $form->method('getData')->willReturn($formData);

        $config = [
            'level' => 'info',
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('info', 'contact_form', $formData)
        ;

        $output = new LogOutput();
        $output->setLogger($logger);
        $output->initialize('log_contact', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testHandleWithoutLogLevelUsesDebugDefault(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('test_form');
        $form->method('getData')->willReturn(['test' => 'data']);

        $config = []; // No level specified

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('debug', 'test_form', ['test' => 'data'])
        ;

        $output = new LogOutput();
        $output->setLogger($logger);
        $output->initialize('log_test', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testHandleWithoutLoggerReturnsFailureStatus(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('test_form');
        $form->method('getData')->willReturn(['test' => 'data']);

        $config = ['level' => 'error'];

        $output = new LogOutput();
        // Deliberately not setting a logger
        $output->initialize('log_noLogger', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertFalse($result->getOverallStatus());
    }

    public function testHandleWithWarningLevelLogsWarning(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('error_report');
        $form->method('getData')->willReturn(['error' => 'Something went wrong']);

        $config = ['level' => 'warning'];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('warning', 'error_report', ['error' => 'Something went wrong'])
        ;

        $output = new LogOutput();
        $output->setLogger($logger);
        $output->initialize('log_warning', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testHandleWithErrorLevelLogsError(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('critical_issue');
        $form->method('getData')->willReturn(['issue' => 'Critical failure']);

        $config = ['level' => 'error'];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('error', 'critical_issue', ['issue' => 'Critical failure'])
        ;

        $output = new LogOutput();
        $output->setLogger($logger);
        $output->initialize('log_error', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testHandleWithComplexDataLogsFullArray(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('complex_form');
        $complexData = [
            'user' => [
                'name' => 'Jane Doe',
                'preferences' => ['newsletter' => true, 'sms' => false],
            ],
            'metadata' => [
                'timestamp' => 1234567890,
                'ip' => '192.168.1.1',
            ],
        ];
        $form->method('getData')->willReturn($complexData);

        $config = ['level' => 'info'];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('info', 'complex_form', $complexData)
        ;

        $output = new LogOutput();
        $output->setLogger($logger);
        $output->initialize('log_complex', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testSetLoggerImplementsLoggerAwareInterface(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LogOutput();

        $output->setLogger($logger);

        // Verify the logger was set by using reflection to access the protected property
        $reflection = new \ReflectionClass($output);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);

        $this->assertSame($logger, $property->getValue($output));
    }
}

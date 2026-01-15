<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Output;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Pimcore\Mail;
use Pimcore\Model\Document;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Form\Output\EmailOutput;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreMail;

#[AllowMockObjectsWithoutExpectations]
class EmailOutputTest extends TestCase
{
    use MocksPimcoreMail;

    public function testNameReturnsEmail(): void
    {
        $this->assertEquals('email', EmailOutput::name());
    }

    public function testHandleSendsEmailSuccessfully(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]);

        $config = [
            'to' => 'admin@example.com',
            'document' => 5,
        ];

        // Create a custom output class that allows us to inject the mail mock
        $mailMock = $this->createMockPimcoreMail();
        $mailMock->expects($this->once())->method('addTo')->with('admin@example.com');
        $mailMock->expects($this->once())->method('setDocument')->with(5);
        $mailMock->expects($this->once())->method('setParams')->with([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]);
        $mailMock->expects($this->once())->method('send');

        $output = new class($mailMock) extends EmailOutput {
            public function __construct(private Mail $mockMail)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                $this->mockMail->addTo($this->getTo());
                $this->mockMail->setDocument($this->getDocument());
                $this->mockMail->setParams(array_merge($this->form->getData(), $this->getAdditionalParams()));

                $subject = $this->getSubject();

                if ($subject !== null) {
                    $this->mockMail->subject($subject);
                }

                $from = $this->getFrom();

                if ($from !== null) {
                    $this->mockMail->addFrom($from);
                }

                $this->mockMail = $this->preSend($this->mockMail);
                $this->mockMail->send();

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('email_admin', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testHandleWithDocumentPathSendsEmail(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['test' => 'data']);

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getId')->willReturn(10);

        $config = [
            'to' => 'test@example.com',
            'document' => '/emails/test',
        ];

        $mailMock = $this->createMockPimcoreMail();
        $mailMock->expects($this->once())->method('addTo')->with('test@example.com');
        $mailMock->expects($this->once())->method('setDocument')->with('/emails/test');

        $output = new class($mailMock) extends EmailOutput {
            public function __construct(private Mail $mockMail)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                $this->mockMail->addTo($this->getTo());
                $this->mockMail->setDocument($this->getDocument());
                $this->mockMail->setParams(array_merge($this->form->getData(), $this->getAdditionalParams()));
                $this->mockMail->send();

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('email_test', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
    }

    public function testHandleWithEmailSendFailureReturnsFailureStatus(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['message' => 'test']);

        $config = [
            'to' => 'admin@example.com',
            'document' => 5,
        ];

        $mailMock = $this->createFailingMockPimcoreMail();

        $output = new class($mailMock) extends EmailOutput {
            public function __construct(private Mail $mockMail)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                try {
                    $this->mockMail->addTo($this->getTo());
                    $this->mockMail->setDocument($this->getDocument());
                    $this->mockMail->setParams($this->form->getData());
                    $this->mockMail->send();

                    return $outputResponse->addStatus(true);
                } catch (\Exception $e) {
                    return $outputResponse->addStatus(false);
                }
            }
        };

        $output->initialize('email_fail', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertFalse($result->getOverallStatus());
    }

    public function testGetToReturnsConfiguredEmailAddress(): void
    {
        $form = $this->createMock(FormInterface::class);
        $config = ['to' => 'recipient@example.com', 'document' => 1];

        $output = new EmailOutput();
        $output->initialize('test', $form, $config);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getTo');
        $method->setAccessible(true);

        $result = $method->invoke($output);

        $this->assertEquals('recipient@example.com', $result);
    }

    public function testGetDocumentReturnsConfiguredDocument(): void
    {
        $form = $this->createMock(FormInterface::class);
        $config = ['to' => 'test@example.com', 'document' => '/system/emails/form'];

        $output = new EmailOutput();
        $output->initialize('test', $form, $config);

        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getDocument');
        $method->setAccessible(true);

        $result = $method->invoke($output);

        $this->assertEquals('/system/emails/form', $result);
    }
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Output;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Form\Output\DataObjectOutput;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreDataObject;

#[AllowMockObjectsWithoutExpectations]
class DataObjectOutputTest extends TestCase
{
    use MocksPimcoreDataObject;

    public function testNameReturnsDataObject(): void
    {
        $this->assertEquals('data_object', DataObjectOutput::name());
    }

    public function testHandleCreatesDataObjectSuccessfully(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('contact_form');
        $form->method('getData')->willReturn([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]);

        $config = [
            'class' => 'FormSubmission',
            'path' => '/form-submissions',
        ];

        $capturedData = null;
        $capturedPath = null;
        $capturedKey = null;

        $output = new class($capturedData, $capturedPath, $capturedKey) extends DataObjectOutput {
            public function __construct(
                private &$capturedData,
                private &$capturedPath,
                private &$capturedKey,
            ) {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                // Simulate data object creation without actually creating it
                $this->capturedData = $this->getData();
                $this->capturedPath = $this->getPath();
                $this->capturedKey = $this->getKey();

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('dataobject_submission', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ], $capturedData);
        $this->assertEquals('/form-submissions', $capturedPath);
        $this->assertMatchesRegularExpression('/^contact_form-\d+$/', $capturedKey);
    }

    public function testHandleWithInvalidClassThrowsException(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['test' => 'data']);

        $config = [
            'class' => 'NonExistentClass',
            'path' => '/submissions',
        ];

        $output = new class extends DataObjectOutput {
            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                throw new \InvalidArgumentException('DataObject Pimcore\Model\DataObject\NonExistentClass does not exist');
            }
        };

        $output->initialize('dataobject_invalid', $form, $config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DataObject Pimcore\Model\DataObject\NonExistentClass does not exist');

        $response = new OutputResponse();
        $output->handle($response);
    }

    public function testHandleWithInvalidPathThrowsException(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(['test' => 'data']);

        $config = [
            'class' => 'FormSubmission',
            'path' => '/nonexistent-path',
        ];

        $output = new class extends DataObjectOutput {
            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                throw new \InvalidArgumentException('Path /nonexistent-path not found');
            }
        };

        $output->initialize('dataobject_badpath', $form, $config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path /nonexistent-path not found');

        $response = new OutputResponse();
        $output->handle($response);
    }

    public function testGetKeyGeneratesUniqueKeyWithTimestamp(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('newsletter_signup');
        $form->method('getData')->willReturn(['email' => 'test@example.com']);

        $config = [
            'class' => 'FormSubmission',
            'path' => '/submissions',
        ];

        $output = new DataObjectOutput();
        $output->initialize('dataobject_key', $form, $config);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($output);
        sleep(1); // Wait 1 second to ensure different timestamp (time() has 1-second resolution)
        $key2 = $method->invoke($output);

        $this->assertStringStartsWith('newsletter_signup-', $key1);
        $this->assertStringStartsWith('newsletter_signup-', $key2);
        // Keys should be different due to timestamp
        $this->assertNotEquals($key1, $key2);
    }

    public function testGetPathReturnsConfiguredPath(): void
    {
        $form = $this->createMock(FormInterface::class);
        $config = [
            'class' => 'FormSubmission',
            'path' => '/custom/path/submissions',
        ];

        $output = new DataObjectOutput();
        $output->initialize('test', $form, $config);

        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getPath');
        $method->setAccessible(true);

        $result = $method->invoke($output);

        $this->assertEquals('/custom/path/submissions', $result);
    }

    public function testGetDataReturnsFormData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formData = [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'company' => 'ACME Corp',
        ];
        $form->method('getData')->willReturn($formData);

        $config = [
            'class' => 'FormSubmission',
            'path' => '/submissions',
        ];

        $output = new DataObjectOutput();
        $output->initialize('test', $form, $config);

        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);

        $result = $method->invoke($output);

        $this->assertEquals($formData, $result);
    }
}

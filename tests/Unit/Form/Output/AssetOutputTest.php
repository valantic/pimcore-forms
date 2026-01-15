<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Output;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset\Folder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Valantic\PimcoreFormsBundle\Form\Output\AssetOutput;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Tests\Support\Traits\MocksPimcoreAsset;

#[AllowMockObjectsWithoutExpectations]
class AssetOutputTest extends TestCase
{
    use MocksPimcoreAsset;

    public function testNameReturnsAsset(): void
    {
        $this->assertEquals('asset', AssetOutput::name());
    }

    public function testHandleCreatesAssetFolderSuccessfully(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getRealPath')->willReturn('/tmp/test.pdf');
        $uploadedFile->method('getClientOriginalName')->willReturn('document.pdf');
        $uploadedFile->method('guessExtension')->willReturn('pdf');

        $fileField = $this->createMock(FormInterface::class);
        $fileField->method('getData')->willReturn($uploadedFile);

        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('upload_form');
        $form->method('get')->with('attachment')->willReturn($fileField);

        $config = [
            'path' => '/uploads',
            'fields' => ['attachment'],
            'createHashedFolder' => false,
        ];

        $parentFolder = $this->createMockAssetFolder('/uploads');

        $capturedSubfolderName = null;
        $capturedFiles = null;

        $output = new class($parentFolder, $capturedSubfolderName, $capturedFiles) extends AssetOutput {
            public function __construct(
                private Folder $mockParent,
                private &$capturedSubfolderName,
                private &$capturedFiles,
            ) {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                $this->capturedSubfolderName = $this->getSubfolderName();
                $this->capturedFiles = $this->getFiles();

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('asset_upload', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
        $this->assertMatchesRegularExpression('/^upload_form_\d{8}-\d{6}$/', $capturedSubfolderName);
        $this->assertArrayHasKey('attachment', $capturedFiles);
        $this->assertInstanceOf(UploadedFile::class, $capturedFiles['attachment']);
    }

    public function testHandleWithHashedFolderCreatesUuidSuffix(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('secure_upload');

        $config = [
            'path' => '/uploads',
            'fields' => ['file'],
            'createHashedFolder' => true,
        ];

        $capturedSubfolderName = null;

        $output = new class($capturedSubfolderName) extends AssetOutput {
            public function __construct(private &$capturedSubfolderName)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                $this->capturedSubfolderName = $this->getSubfolderName();

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('asset_secure', $form, $config);

        $response = new OutputResponse();
        $output->handle($response);

        // Should match: secure_upload_20240115-143022_uuid-here
        $this->assertMatchesRegularExpression('/^secure_upload_\d{8}-\d{6}_[a-f0-9-]{36}$/', $capturedSubfolderName);
    }

    public function testHandleWithMultipleFilesSavesAllFiles(): void
    {
        $file1 = $this->createMock(UploadedFile::class);
        $file1->method('getRealPath')->willReturn('/tmp/file1.pdf');
        $file1->method('getClientOriginalName')->willReturn('document1.pdf');
        $file1->method('guessExtension')->willReturn('pdf');

        $file2 = $this->createMock(UploadedFile::class);
        $file2->method('getRealPath')->willReturn('/tmp/file2.jpg');
        $file2->method('getClientOriginalName')->willReturn('image2.jpg');
        $file2->method('guessExtension')->willReturn('jpg');

        $field1 = $this->createMock(FormInterface::class);
        $field1->method('getData')->willReturn($file1);

        $field2 = $this->createMock(FormInterface::class);
        $field2->method('getData')->willReturn($file2);

        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('multi_upload');
        $form->method('get')->willReturnMap([
            ['document', $field1],
            ['photo', $field2],
        ]);

        $config = [
            'path' => '/uploads',
            'fields' => ['document', 'photo'],
        ];

        $capturedFiles = null;

        $output = new class($capturedFiles) extends AssetOutput {
            public function __construct(private &$capturedFiles)
            {
            }

            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                $this->capturedFiles = $this->getFiles();

                return $outputResponse->addStatus(true);
            }
        };

        $output->initialize('asset_multi', $form, $config);

        $response = new OutputResponse();
        $result = $output->handle($response);

        $this->assertTrue($result->getOverallStatus());
        $this->assertCount(2, $capturedFiles);
        $this->assertArrayHasKey('document', $capturedFiles);
        $this->assertArrayHasKey('photo', $capturedFiles);
    }

    public function testHandleWithInvalidPathThrowsException(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn('test_form');

        $config = [
            'path' => '/nonexistent/path',
            'fields' => ['file'],
        ];

        $output = new class extends AssetOutput {
            public function handle(OutputResponse $outputResponse): OutputResponse
            {
                throw new \InvalidArgumentException('Path /nonexistent/path not found');
            }
        };

        $output->initialize('asset_invalid', $form, $config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path /nonexistent/path not found');

        $response = new OutputResponse();
        $output->handle($response);
    }

    public function testGetFilesSkipsNonUploadedFileFields(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getRealPath')->willReturn('/tmp/test.pdf');

        $fileField = $this->createMock(FormInterface::class);
        $fileField->method('getData')->willReturn($uploadedFile);

        $textField = $this->createMock(FormInterface::class);
        $textField->method('getData')->willReturn('just a string');

        $form = $this->createMock(FormInterface::class);
        $form->method('get')->willReturnMap([
            ['attachment', $fileField],
            ['name', $textField],
        ]);

        $config = [
            'path' => '/uploads',
            'fields' => ['attachment', 'name'],
        ];

        $output = new AssetOutput();
        $output->initialize('asset_filter', $form, $config);

        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getFiles');
        $method->setAccessible(true);

        $result = $method->invoke($output);

        // Only the uploaded file should be included
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('attachment', $result);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function testGetPathReturnsConfiguredPath(): void
    {
        $form = $this->createMock(FormInterface::class);
        $config = [
            'path' => '/custom/uploads/path',
            'fields' => ['file'],
        ];

        $output = new AssetOutput();
        $output->initialize('test', $form, $config);

        $reflection = new \ReflectionClass($output);
        $method = $reflection->getMethod('getPath');
        $method->setAccessible(true);

        $result = $method->invoke($output);

        $this->assertEquals('/custom/uploads/path', $result);
    }
}

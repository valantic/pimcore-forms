<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Transformer\FileTransformer;

#[AllowMockObjectsWithoutExpectations]
class FileTransformerTest extends TestCase
{
    private FileTransformer $transformer;
    private MockObject $translator;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->transformer = new FileTransformer($this->translator, null);
    }

    public function testTransformBasicFile(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(FileType::class, null, [
                'label' => 'Upload Document',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('file', $schema['type']);
        $this->assertSame('Upload Document', $schema['title']);
    }

    public function testTransformFileWithCustomLabel(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $form = $this->formFactory
            ->createBuilder(FileType::class, null, [
                'label' => 'Profile Picture',
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('file', $schema['type']);
        $this->assertSame('Profile Picture', $schema['title']);
    }

    public function testTransformFileWithAttributes(): void
    {
        $this->translator
            ->method('trans')
            ->willReturnCallback(fn ($key) => $key)
        ;

        $form = $this->formFactory
            ->createBuilder(FileType::class, null, [
                'label' => 'Attachment',
                'attr' => [
                    'accept' => '.pdf,.doc,.docx',
                    'class' => 'file-input',
                ],
            ])
            ->getForm()
        ;

        $schema = $this->transformer->transform($form);

        $this->assertIsArray($schema);
        $this->assertSame('file', $schema['type']);
        $this->assertArrayHasKey('attr', $schema);
        $this->assertSame('.pdf,.doc,.docx', $schema['attr']['accept']);
        $this->assertSame('file-input', $schema['attr']['class']);
    }
}

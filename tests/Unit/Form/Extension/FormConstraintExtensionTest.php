<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Valantic\PimcoreFormsBundle\Form\Extension\FormConstraintExtension;

#[AllowMockObjectsWithoutExpectations]
class FormConstraintExtensionTest extends TestCase
{
    private FormConstraintExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FormConstraintExtension();
    }

    public function testApplyReturnsSchemaUnchangedWhenNoConstraints(): void
    {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('constraints')->willReturn(null);

        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($formConfig);

        $schema = ['type' => 'string'];

        $result = $this->extension->apply($form, $schema);

        $this->assertEquals($schema, $result);
        $this->assertArrayNotHasKey('constraints', $result);
    }

    public function testApplyAddsConstraintsToSchema(): void
    {
        $constraints = [new NotBlank()];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('constraints')->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($formConfig);

        $schema = [];

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('constraints', $result);
        $this->assertIsArray($result['constraints']);
        $this->assertCount(1, $result['constraints']);
        $this->assertEquals('NotBlank', $result['constraints'][0]['type']);
    }

    public function testApplyHandlesMultipleConstraints(): void
    {
        $constraints = [
            new NotBlank(),
            new Length(min: 5, max: 100),
            new Email(),
        ];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('constraints')->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($formConfig);

        $schema = [];

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('constraints', $result);
        $this->assertCount(3, $result['constraints']);
        $this->assertEquals('NotBlank', $result['constraints'][0]['type']);
        $this->assertEquals('Length', $result['constraints'][1]['type']);
        $this->assertEquals('Email', $result['constraints'][2]['type']);
    }

    public function testApplyIncludesConstraintConfiguration(): void
    {
        $constraints = [new Length(min: 5, max: 100)];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('constraints')->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($formConfig);

        $schema = [];

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('constraints', $result);
        $this->assertArrayHasKey('config', $result['constraints'][0]);
        $this->assertArrayHasKey('min', $result['constraints'][0]['config']);
        $this->assertArrayHasKey('max', $result['constraints'][0]['config']);
        $this->assertEquals(5, $result['constraints'][0]['config']['min']);
        $this->assertEquals(100, $result['constraints'][0]['config']['max']);
    }

    public function testApplyAddsHtmlPatternForRegexConstraint(): void
    {
        $constraints = [new Regex(pattern: '/^[A-Z]/')];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('constraints')->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($formConfig);

        $schema = [];

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('constraints', $result);
        $this->assertEquals('Regex', $result['constraints'][0]['type']);
        $this->assertArrayHasKey('htmlPattern', $result['constraints'][0]['config']);
        $this->assertNotEmpty($result['constraints'][0]['config']['htmlPattern']);
    }

    public function testApplyDoesNotOverwriteExistingHtmlPattern(): void
    {
        $customHtmlPattern = '^[A-Z]$';
        $constraints = [new Regex(pattern: '/^[A-Z]/', htmlPattern: $customHtmlPattern)];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('constraints')->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($formConfig);

        $schema = [];

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('constraints', $result);
        $this->assertEquals('Regex', $result['constraints'][0]['type']);
        $this->assertArrayHasKey('htmlPattern', $result['constraints'][0]['config']);
        $this->assertEquals($customHtmlPattern, $result['constraints'][0]['config']['htmlPattern']);
    }
}

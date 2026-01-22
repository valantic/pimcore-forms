<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Form\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Valantic\PimcoreFormsBundle\Form\Extension\FormAttributeExtension;

#[AllowMockObjectsWithoutExpectations]
class FormAttributeExtensionTest extends TestCase
{
    private FormAttributeExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FormAttributeExtension();
    }

    public function testApplyReturnsSchemaUnchangedWhenNoAttrKey(): void
    {
        $schema = ['type' => 'string'];
        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);

        $result = $this->extension->apply($form, $schema);

        $this->assertEquals($schema, $result);
    }

    public function testApplyConvertsDashedAttributesToCamelCase(): void
    {
        $schema = [
            'attr' => [
                'data-value' => 'test',
                'custom-attribute' => 'value',
            ],
        ];
        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('attr', $result);
        $this->assertArrayHasKey('dataValue', $result['attr']);
        $this->assertArrayHasKey('customAttribute', $result['attr']);
        $this->assertEquals('test', $result['attr']['dataValue']);
        $this->assertEquals('value', $result['attr']['customAttribute']);
        $this->assertArrayNotHasKey('data-value', $result['attr']);
        $this->assertArrayNotHasKey('custom-attribute', $result['attr']);
    }

    public function testApplyHandlesSingleDashInAttribute(): void
    {
        $schema = [
            'attr' => [
                'aria-label' => 'Label',
                'data-id' => '123',
            ],
        ];
        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('ariaLabel', $result['attr']);
        $this->assertArrayHasKey('dataId', $result['attr']);
        $this->assertEquals('Label', $result['attr']['ariaLabel']);
        $this->assertEquals('123', $result['attr']['dataId']);
    }

    public function testApplyHandlesMultipleDashesInAttribute(): void
    {
        $schema = [
            'attr' => [
                'data-some-long-attribute' => 'value',
            ],
        ];
        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('dataSomeLongAttribute', $result['attr']);
        $this->assertEquals('value', $result['attr']['dataSomeLongAttribute']);
    }

    public function testApplyPreservesAttributesWithoutDashes(): void
    {
        $schema = [
            'attr' => [
                'class' => 'form-control',
                'id' => 'my-field',
                'data-value' => 'test',
            ],
        ];
        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);

        $result = $this->extension->apply($form, $schema);

        $this->assertArrayHasKey('class', $result['attr']);
        $this->assertArrayHasKey('id', $result['attr']);
        $this->assertArrayHasKey('dataValue', $result['attr']);
        $this->assertEquals('form-control', $result['attr']['class']);
        $this->assertEquals('my-field', $result['attr']['id']);
        $this->assertEquals('test', $result['attr']['dataValue']);
    }
}

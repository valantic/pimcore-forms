<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\DependencyInjection\Compiler;

use Limenius\Liform\Resolver;
use Limenius\Liform\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Valantic\PimcoreFormsBundle\DependencyInjection\Compiler\TransformerCompilerPass;

#[AllowMockObjectsWithoutExpectations]
class TransformerCompilerPassTest extends TestCase
{
    private TransformerCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new TransformerCompilerPass();
        $this->container = new ContainerBuilder();
    }

    public function testProcessWithNoResolverDefinition(): void
    {
        // Should not throw an exception when resolver is not defined
        $this->compilerPass->process($this->container);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testProcessWithTransformerService(): void
    {
        $resolverDefinition = new Definition(Resolver::class);
        $this->container->setDefinition(Resolver::class, $resolverDefinition);

        $transformerDefinition = new Definition(MockTransformer::class);
        $transformerDefinition->addTag(TransformerCompilerPass::TRANSFORMER_TAG, ['form_type' => 'TextType']);
        $this->container->setDefinition('test.transformer', $transformerDefinition);

        $this->compilerPass->process($this->container);

        $methodCalls = $resolverDefinition->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertSame('setTransformer', $methodCalls[0][0]);
        $this->assertSame('TextType', $methodCalls[0][1][0]);
    }

    public function testProcessWithTransformerServiceWithWidget(): void
    {
        $resolverDefinition = new Definition(Resolver::class);
        $this->container->setDefinition(Resolver::class, $resolverDefinition);

        $transformerDefinition = new Definition(MockTransformer::class);
        $transformerDefinition->addTag(TransformerCompilerPass::TRANSFORMER_TAG, [
            'form_type' => 'ChoiceType',
            'widget' => 'select',
        ]);
        $this->container->setDefinition('test.transformer', $transformerDefinition);

        $this->compilerPass->process($this->container);

        $methodCalls = $resolverDefinition->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertSame('setTransformer', $methodCalls[0][0]);
        $this->assertSame('ChoiceType', $methodCalls[0][1][0]);
        $this->assertSame('select', $methodCalls[0][1][2]);
    }

    public function testProcessThrowsExceptionWhenTransformerDoesNotImplementInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not implement the mandatory');

        $resolverDefinition = new Definition(Resolver::class);
        $this->container->setDefinition(Resolver::class, $resolverDefinition);

        $transformerDefinition = new Definition(\stdClass::class);
        $transformerDefinition->addTag(TransformerCompilerPass::TRANSFORMER_TAG, ['form_type' => 'TextType']);
        $this->container->setDefinition('test.transformer', $transformerDefinition);

        $this->compilerPass->process($this->container);
    }

    public function testProcessThrowsExceptionWhenFormTypeNotSpecified(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not specify the mandatory \'form_type\' option');

        $resolverDefinition = new Definition(Resolver::class);
        $this->container->setDefinition(Resolver::class, $resolverDefinition);

        $transformerDefinition = new Definition(MockTransformer::class);
        $transformerDefinition->addTag(TransformerCompilerPass::TRANSFORMER_TAG);
        $this->container->setDefinition('test.transformer', $transformerDefinition);

        $this->compilerPass->process($this->container);
    }
}

class MockTransformer implements TransformerInterface
{
    public function transform(\Symfony\Component\Form\FormInterface $form, array $extensions = [], ?string $widget = null): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\DependencyInjection\Compiler;

use Limenius\Liform\Liform;
use Limenius\Liform\Transformer\ExtensionInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Valantic\PimcoreFormsBundle\DependencyInjection\Compiler\ExtensionCompilerPass;

#[AllowMockObjectsWithoutExpectations]
class ExtensionCompilerPassTest extends TestCase
{
    private ExtensionCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new ExtensionCompilerPass();
        $this->container = new ContainerBuilder();
    }

    public function testProcessWithNoLiformDefinition(): void
    {
        // Should not throw an exception when Liform is not defined
        $this->compilerPass->process($this->container);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testProcessWithExtensionService(): void
    {
        $liformDefinition = new Definition(Liform::class);
        $this->container->setDefinition(Liform::class, $liformDefinition);

        $extensionDefinition = new Definition(MockExtension::class);
        $extensionDefinition->addTag(ExtensionCompilerPass::EXTENSION_TAG);
        $this->container->setDefinition('test.extension', $extensionDefinition);

        $this->compilerPass->process($this->container);

        $methodCalls = $liformDefinition->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertSame('addExtension', $methodCalls[0][0]);
    }

    public function testProcessWithMultipleExtensions(): void
    {
        $liformDefinition = new Definition(Liform::class);
        $this->container->setDefinition(Liform::class, $liformDefinition);

        $extensionDefinition1 = new Definition(MockExtension::class);
        $extensionDefinition1->addTag(ExtensionCompilerPass::EXTENSION_TAG);
        $this->container->setDefinition('test.extension1', $extensionDefinition1);

        $extensionDefinition2 = new Definition(MockExtension::class);
        $extensionDefinition2->addTag(ExtensionCompilerPass::EXTENSION_TAG);
        $this->container->setDefinition('test.extension2', $extensionDefinition2);

        $this->compilerPass->process($this->container);

        $methodCalls = $liformDefinition->getMethodCalls();
        $this->assertCount(2, $methodCalls);
        $this->assertSame('addExtension', $methodCalls[0][0]);
        $this->assertSame('addExtension', $methodCalls[1][0]);
    }

    public function testProcessThrowsExceptionWhenExtensionDoesNotImplementInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not implement the mandatory');

        $liformDefinition = new Definition(Liform::class);
        $this->container->setDefinition(Liform::class, $liformDefinition);

        $extensionDefinition = new Definition(\stdClass::class);
        $extensionDefinition->addTag(ExtensionCompilerPass::EXTENSION_TAG);
        $this->container->setDefinition('test.extension', $extensionDefinition);

        $this->compilerPass->process($this->container);
    }

    public function testProcessSkipsExtensionWithoutClass(): void
    {
        $liformDefinition = new Definition(Liform::class);
        $this->container->setDefinition(Liform::class, $liformDefinition);

        $extensionDefinition = new Definition();
        $extensionDefinition->addTag(ExtensionCompilerPass::EXTENSION_TAG);
        $this->container->setDefinition('test.extension', $extensionDefinition);

        $this->compilerPass->process($this->container);

        $methodCalls = $liformDefinition->getMethodCalls();
        $this->assertCount(0, $methodCalls);
    }
}

class MockExtension implements ExtensionInterface
{
    public function apply(\Symfony\Component\Form\FormInterface $form, array $schema)
    {
        return $schema;
    }
}

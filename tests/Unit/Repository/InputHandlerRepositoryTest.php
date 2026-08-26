<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Valantic\PimcoreFormsBundle\Exception\Repository\ItemNotFoundInRepositoryException;
use Valantic\PimcoreFormsBundle\Form\InputHandler\InputHandlerInterface;
use Valantic\PimcoreFormsBundle\Repository\InputHandlerRepository;

#[AllowMockObjectsWithoutExpectations]
class InputHandlerRepositoryTest extends TestCase
{
    public function testGetRetrievesInputHandlerByClassName(): void
    {
        $handler = new TestInputHandler1();
        $repository = new InputHandlerRepository([$handler]);

        $retrieved = $repository->get(TestInputHandler1::class);

        $this->assertInstanceOf(InputHandlerInterface::class, $retrieved);
        $this->assertSame($handler, $retrieved);
    }

    public function testGetThrowsExceptionForUnknownHandler(): void
    {
        $this->expectException(ItemNotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Item NonExistentHandler not found in repository');

        $repository = new InputHandlerRepository([]);
        $repository->get('NonExistentHandler');
    }

    public function testRepositoryHandlesMultipleHandlers(): void
    {
        $handler1 = new TestInputHandler1();
        $handler2 = new TestInputHandler2();

        $repository = new InputHandlerRepository([$handler1, $handler2]);

        $retrieved1 = $repository->get(TestInputHandler1::class);
        $retrieved2 = $repository->get(TestInputHandler2::class);

        $this->assertSame($handler1, $retrieved1);
        $this->assertSame($handler2, $retrieved2);
    }

    public function testGetCachesHandlers(): void
    {
        $handler = new TestInputHandler1();
        $repository = new InputHandlerRepository([$handler]);

        $retrieved1 = $repository->get(TestInputHandler1::class);
        $retrieved2 = $repository->get(TestInputHandler1::class);

        $this->assertSame($retrieved1, $retrieved2);
    }
}

class TestInputHandler1 implements InputHandlerInterface
{
    private array $data = [];

    public function initialize(FormInterface $form, ?Request $request): void
    {
        $this->data = ['field1' => 'value1'];
    }

    public function get(): array
    {
        return $this->data;
    }
}

class TestInputHandler2 implements InputHandlerInterface
{
    private array $data = [];

    public function initialize(FormInterface $form, ?Request $request): void
    {
        $this->data = ['field2' => 'value2'];
    }

    public function get(): array
    {
        return $this->data;
    }
}

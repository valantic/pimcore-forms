<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Valantic\PimcoreFormsBundle\Exception\Repository\ItemNotFoundInRepositoryException;
use Valantic\PimcoreFormsBundle\Form\RedirectHandler\RedirectHandlerInterface;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;

#[AllowMockObjectsWithoutExpectations]
class RedirectHandlerRepositoryTest extends TestCase
{
    public function testGetRetrievesRedirectHandlerByClassName(): void
    {
        $handler = new TestRedirectHandler1();
        $repository = new RedirectHandlerRepository([$handler]);

        $retrieved = $repository->get(TestRedirectHandler1::class);

        $this->assertInstanceOf(RedirectHandlerInterface::class, $retrieved);
        $this->assertSame($handler, $retrieved);
    }

    public function testGetThrowsExceptionForUnknownHandler(): void
    {
        $this->expectException(ItemNotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Item NonExistentHandler not found in repository');

        $repository = new RedirectHandlerRepository([]);
        $repository->get('NonExistentHandler');
    }

    public function testRepositoryHandlesMultipleHandlers(): void
    {
        $handler1 = new TestRedirectHandler1();
        $handler2 = new TestRedirectHandler2();

        $repository = new RedirectHandlerRepository([$handler1, $handler2]);

        $retrieved1 = $repository->get(TestRedirectHandler1::class);
        $retrieved2 = $repository->get(TestRedirectHandler2::class);

        $this->assertSame($handler1, $retrieved1);
        $this->assertSame($handler2, $retrieved2);
    }

    public function testGetCachesHandlers(): void
    {
        $handler = new TestRedirectHandler1();
        $repository = new RedirectHandlerRepository([$handler]);

        $retrieved1 = $repository->get(TestRedirectHandler1::class);
        $retrieved2 = $repository->get(TestRedirectHandler1::class);

        $this->assertSame($retrieved1, $retrieved2);
    }
}

class TestRedirectHandler1 implements RedirectHandlerInterface
{
    public function onSuccess(): ?string
    {
        return '/success';
    }

    public function onFailure(): ?string
    {
        return '/failure';
    }
}

class TestRedirectHandler2 implements RedirectHandlerInterface
{
    public function onSuccess(): ?string
    {
        return '/thank-you';
    }

    public function onFailure(): ?string
    {
        return null;
    }
}

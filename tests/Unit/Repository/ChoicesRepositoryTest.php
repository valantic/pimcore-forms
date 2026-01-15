<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Valantic\PimcoreFormsBundle\Exception\Repository\ItemNotFoundInRepositoryException;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;
use Valantic\PimcoreFormsBundle\Repository\ChoicesRepository;

#[AllowMockObjectsWithoutExpectations]
class ChoicesRepositoryTest extends TestCase
{
    public function testGetRetrievesChoiceProviderByClassName(): void
    {
        $provider = new TestChoiceProvider1();
        $repository = new ChoicesRepository([$provider]);

        $retrieved = $repository->get(TestChoiceProvider1::class);

        $this->assertInstanceOf(ChoicesInterface::class, $retrieved);
        $this->assertSame($provider, $retrieved);
    }

    public function testGetThrowsExceptionForUnknownProvider(): void
    {
        $this->expectException(ItemNotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Item NonExistentProvider not found in repository');

        $repository = new ChoicesRepository([]);
        $repository->get('NonExistentProvider');
    }

    public function testRepositoryHandlesMultipleProviders(): void
    {
        $provider1 = new TestChoiceProvider1();
        $provider2 = new TestChoiceProvider2();

        $repository = new ChoicesRepository([$provider1, $provider2]);

        $retrieved1 = $repository->get(TestChoiceProvider1::class);
        $retrieved2 = $repository->get(TestChoiceProvider2::class);

        $this->assertSame($provider1, $retrieved1);
        $this->assertSame($provider2, $retrieved2);
    }

    public function testGetCachesProviders(): void
    {
        $provider = new TestChoiceProvider1();
        $repository = new ChoicesRepository([$provider]);

        $retrieved1 = $repository->get(TestChoiceProvider1::class);
        $retrieved2 = $repository->get(TestChoiceProvider1::class);

        $this->assertSame($retrieved1, $retrieved2);
    }

    public function testRepositoryIteratesOverIterableProviders(): void
    {
        $provider1 = new TestChoiceProvider1();
        $provider2 = new TestChoiceProvider2();

        $iterator = new \ArrayIterator([$provider1, $provider2]);
        $repository = new ChoicesRepository($iterator);

        $retrieved1 = $repository->get(TestChoiceProvider1::class);
        $retrieved2 = $repository->get(TestChoiceProvider2::class);

        $this->assertInstanceOf(ChoicesInterface::class, $retrieved1);
        $this->assertInstanceOf(ChoicesInterface::class, $retrieved2);
    }
}

class TestChoiceProvider1 implements ChoicesInterface
{
    public function choices(): array
    {
        return ['option1' => 'Option 1', 'option2' => 'Option 2'];
    }

    public function choiceLabel(mixed $choice, mixed $key, mixed $value): ?string
    {
        return (string) $value;
    }

    public function choiceAttribute(mixed $choice, mixed $key, mixed $value): array
    {
        return [];
    }
}

class TestChoiceProvider2 implements ChoicesInterface
{
    public function choices(): array
    {
        return ['a' => 'Choice A', 'b' => 'Choice B'];
    }

    public function choiceLabel(mixed $choice, mixed $key, mixed $value): ?string
    {
        return (string) $value;
    }

    public function choiceAttribute(mixed $choice, mixed $key, mixed $value): array
    {
        return ['data-custom' => 'value'];
    }
}

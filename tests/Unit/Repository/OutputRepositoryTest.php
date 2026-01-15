<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Exception\DuplicateOutputException;
use Valantic\PimcoreFormsBundle\Exception\UnknownOutputException;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;

#[AllowMockObjectsWithoutExpectations]
class OutputRepositoryTest extends TestCase
{
    public function testGetRetrievesOutputByName(): void
    {
        $output = new TestOutput1();
        $repository = new OutputRepository([$output]);

        $retrieved = $repository->get('test_output_1');

        $this->assertInstanceOf(OutputInterface::class, $retrieved);
    }

    public function testGetThrowsExceptionForUnknownOutput(): void
    {
        $this->expectException(UnknownOutputException::class);

        $repository = new OutputRepository([]);
        $repository->get('nonexistent');
    }

    public function testGetReturnsClonedInstance(): void
    {
        $output = new TestOutput1();
        $repository = new OutputRepository([$output]);

        $retrieved1 = $repository->get('test_output_1');
        $retrieved2 = $repository->get('test_output_1');

        $this->assertNotSame($retrieved1, $retrieved2);
    }

    public function testAllReturnsAllOutputs(): void
    {
        $output1 = new TestOutput1();
        $output2 = new TestOutput2();

        $repository = new OutputRepository([$output1, $output2]);
        $all = $repository->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('test_output_1', $all);
        $this->assertArrayHasKey('test_output_2', $all);
    }

    public function testIterableToArrayConvertsIterableToArray(): void
    {
        $output = new TestOutput1();
        $repository = new OutputRepository([$output]);

        $result = $repository->iterableToArray([$output]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_output_1', $result);
    }

    public function testIterableToArrayThrowsExceptionForDuplicateNames(): void
    {
        $this->expectException(DuplicateOutputException::class);

        $output1 = new TestOutputDuplicate();
        $output2 = new TestOutputDuplicate2();

        new OutputRepository([$output1, $output2]);
    }
}

class TestOutput1 implements OutputInterface
{
    public static function name(): string
    {
        return 'test_output_1';
    }

    public function initialize(string $key, FormInterface $form, array $config): void
    {
    }

    public function setOutputHandlers(array $handlers): void
    {
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        return $outputResponse;
    }
}

class TestOutput2 implements OutputInterface
{
    public static function name(): string
    {
        return 'test_output_2';
    }

    public function initialize(string $key, FormInterface $form, array $config): void
    {
    }

    public function setOutputHandlers(array $handlers): void
    {
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        return $outputResponse;
    }
}

class TestOutputDuplicate implements OutputInterface
{
    public static function name(): string
    {
        return 'duplicate';
    }

    public function initialize(string $key, FormInterface $form, array $config): void
    {
    }

    public function setOutputHandlers(array $handlers): void
    {
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        return $outputResponse;
    }
}

class TestOutputDuplicate2 implements OutputInterface
{
    public static function name(): string
    {
        return 'duplicate';
    }

    public function initialize(string $key, FormInterface $form, array $config): void
    {
    }

    public function setOutputHandlers(array $handlers): void
    {
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        return $outputResponse;
    }
}

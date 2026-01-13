<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Examples\CustomChoiceProvider;

use App\Form\Choices\DatabaseChoiceProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\Choices\DatabaseChoiceProvider
 */
#[AllowMockObjectsWithoutExpectations]
class DatabaseChoiceProviderTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
    }

    /**
     * Test loading choices from database.
     */
    public function testGetChoicesLoadsFromDatabase(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 1, 'name' => 'Sales'],
            ['id' => 2, 'name' => 'Support'],
            ['id' => 3, 'name' => 'Engineering'],
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('executeQuery')->willReturn($result);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $provider = new DatabaseChoiceProvider(
            $this->connection,
            'departments',
            'id',
            'name',
        );

        $choices = $provider->getChoices();

        $this->assertCount(3, $choices);
        $this->assertEquals(1, $choices['Sales']);
        $this->assertEquals(2, $choices['Support']);
        $this->assertEquals(3, $choices['Engineering']);
    }

    /**
     * Test empty database returns empty choices.
     */
    public function testGetChoicesWithEmptyDatabaseReturnsEmpty(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('executeQuery')->willReturn($result);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $provider = new DatabaseChoiceProvider(
            $this->connection,
            'departments',
            'id',
            'name',
        );

        $choices = $provider->getChoices();

        $this->assertEmpty($choices);
    }

    /**
     * Test database exception returns empty choices.
     */
    public function testGetChoicesWithDatabaseExceptionReturnsEmpty(): void
    {
        $this->connection
            ->method('createQueryBuilder')
            ->willThrowException(new \RuntimeException('Database connection failed'))
        ;

        $provider = new DatabaseChoiceProvider(
            $this->connection,
            'departments',
            'id',
            'name',
        );

        $choices = $provider->getChoices();

        $this->assertEmpty($choices);
    }

    /**
     * Test custom order by column.
     */
    public function testGetChoicesWithCustomOrderBy(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 1, 'name' => 'Sales', 'priority' => 10],
            ['id' => 2, 'name' => 'Support', 'priority' => 5],
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('priority', 'ASC')
            ->willReturnSelf()
        ;
        $qb->method('executeQuery')->willReturn($result);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $provider = new DatabaseChoiceProvider(
            $this->connection,
            'departments',
            'id',
            'name',
            'priority',
        );

        $choices = $provider->getChoices();

        $this->assertCount(2, $choices);
    }

    /**
     * Test choices format matches Symfony expectations.
     */
    public function testGetChoicesReturnsSymfonyFormat(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 'sales', 'name' => 'Sales Department'],
            ['id' => 'support', 'name' => 'Support Team'],
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('executeQuery')->willReturn($result);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $provider = new DatabaseChoiceProvider(
            $this->connection,
            'departments',
            'id',
            'name',
        );

        $choices = $provider->getChoices();

        // Symfony choice format is ['Label' => 'value']
        $this->assertArrayHasKey('Sales Department', $choices);
        $this->assertArrayHasKey('Support Team', $choices);
        $this->assertEquals('sales', $choices['Sales Department']);
        $this->assertEquals('support', $choices['Support Team']);
    }
}

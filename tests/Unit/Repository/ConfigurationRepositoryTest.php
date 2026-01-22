<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

#[AllowMockObjectsWithoutExpectations]
class ConfigurationRepositoryTest extends TestCase
{
    public function testGetReturnsConfigurationArray(): void
    {
        $config = [
            'forms' => [
                'test_form' => [
                    'fields' => [],
                ],
            ],
        ];

        $parameterBag = new ParameterBag([
            ConfigurationRepository::CONTAINER_TAG => $config,
        ]);

        $repository = new ConfigurationRepository($parameterBag);
        $result = $repository->get();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('forms', $result);
        $this->assertEquals($config, $result);
    }

    public function testGetThrowsExceptionWhenConfigIsNotArray(): void
    {
        $this->expectException(\RuntimeException::class);

        $parameterBag = new ParameterBag([
            ConfigurationRepository::CONTAINER_TAG => 'not-an-array',
        ]);

        $repository = new ConfigurationRepository($parameterBag);
        $repository->get();
    }

    public function testGetCachesConfiguration(): void
    {
        $config = ['forms' => []];

        $parameterBag = new ParameterBag([
            ConfigurationRepository::CONTAINER_TAG => $config,
        ]);

        $repository = new ConfigurationRepository($parameterBag);

        $result1 = $repository->get();
        $result2 = $repository->get();

        $this->assertSame($result1, $result2);
    }

    public function testGetWithComplexNestedConfiguration(): void
    {
        $config = [
            'forms' => [
                'contact_form' => [
                    'fields' => [
                        'name' => ['type' => 'text'],
                        'email' => ['type' => 'email'],
                    ],
                    'outputs' => [
                        'email' => ['to' => 'test@example.com'],
                    ],
                ],
            ],
        ];

        $parameterBag = new ParameterBag([
            ConfigurationRepository::CONTAINER_TAG => $config,
        ]);

        $repository = new ConfigurationRepository($parameterBag);
        $result = $repository->get();

        $this->assertEquals($config, $result);
        $this->assertArrayHasKey('contact_form', $result['forms']);
        $this->assertArrayHasKey('fields', $result['forms']['contact_form']);
        $this->assertArrayHasKey('outputs', $result['forms']['contact_form']);
    }
}

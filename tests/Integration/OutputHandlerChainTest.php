<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;
use Valantic\PimcoreFormsBundle\Service\FormService;
use Valantic\PimcoreFormsBundle\Tests\Support\OutputStub;

/**
 * Integration tests for multiple output handlers executing in sequence.
 */
#[AllowMockObjectsWithoutExpectations]
class OutputHandlerChainTest extends TestCase
{
    private MockObject $configRepository;
    private MockObject $outputRepository;
    private MockObject $form;

    protected function setUp(): void
    {
        $this->configRepository = $this->createMock(ConfigurationRepository::class);
        $this->outputRepository = $this->createMock(OutputRepository::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->form->method('getName')->willReturn('test_form');
    }

    public function testAllOutputHandlersSucceed(): void
    {
        $output1 = new OutputStub();
        $output2 = new OutputStub();
        $output3 = new OutputStub();

        $this->configRepository
            ->method('get')
            ->willReturn([
                'forms' => [
                    'test_form' => [
                        'outputs' => [
                            'email' => [
                                'type' => 'email',
                                'options' => ['to' => 'test@example.com'],
                            ],
                            'log' => [
                                'type' => 'log',
                                'options' => [],
                            ],
                            'http' => [
                                'type' => 'http',
                                'options' => ['url' => 'https://example.com/webhook'],
                            ],
                        ],
                    ],
                ],
            ])
        ;

        $this->outputRepository
            ->method('get')
            ->willReturnMap([
                ['email', $output1],
                ['log', $output2],
                ['http', $output3],
            ])
        ;

        $formService = $this->createPartialMock(FormService::class, ['outputs', 'getConfig']);
        $formService->method('getConfig')->willReturn([
            'outputs' => [
                'email' => ['type' => 'email', 'options' => ['to' => 'test@example.com']],
                'log' => ['type' => 'log', 'options' => []],
                'http' => ['type' => 'http', 'options' => ['url' => 'https://example.com/webhook']],
            ],
        ]);

        // Simulate the handler chain
        $response = new OutputResponse();

        $output1->initialize('email', $this->form, ['to' => 'test@example.com']);
        $output2->initialize('log', $this->form, []);
        $output3->initialize('http', $this->form, ['url' => 'https://example.com/webhook']);

        $handlers = ['email' => $output1, 'log' => $output2, 'http' => $output3];

        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $response = $handler->handle($response);
        }

        $this->assertTrue($response->getOverallStatus());
        $this->assertCount(3, $response->getMessages());
    }

    public function testPartialFailureInChain(): void
    {
        $output1 = new OutputStub();
        $output2 = new OutputStub();
        $output3 = new OutputStub();

        // Make the second handler fail
        $output2->setShouldFail(true);

        $response = new OutputResponse();

        $output1->initialize('email', $this->form, []);
        $output2->initialize('log', $this->form, []);
        $output3->initialize('http', $this->form, []);

        $handlers = ['email' => $output1, 'log' => $output2, 'http' => $output3];

        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $response = $handler->handle($response);
        }

        $this->assertFalse($response->getOverallStatus());
        $this->assertCount(3, $response->getMessages());
        $this->assertStringContainsString('failure', (string) $response->getMessages()[1]);
    }

    public function testAllOutputHandlersFail(): void
    {
        $output1 = new OutputStub();
        $output2 = new OutputStub();
        $output3 = new OutputStub();

        // Make all handlers fail
        $output1->setShouldFail(true);
        $output2->setShouldFail(true);
        $output3->setShouldFail(true);

        $response = new OutputResponse();

        $output1->initialize('email', $this->form, []);
        $output2->initialize('log', $this->form, []);
        $output3->initialize('http', $this->form, []);

        $handlers = ['email' => $output1, 'log' => $output2, 'http' => $output3];

        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $response = $handler->handle($response);
        }

        $this->assertFalse($response->getOverallStatus());
        $this->assertCount(3, $response->getMessages());
    }

    public function testSingleOutputHandler(): void
    {
        $output1 = new OutputStub();

        $response = new OutputResponse();

        $output1->initialize('email', $this->form, []);

        $handlers = ['email' => $output1];

        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $response = $handler->handle($response);
        }

        $this->assertTrue($response->getOverallStatus());
        $this->assertCount(1, $response->getMessages());
    }

    public function testOutputResponseStatusAggregation(): void
    {
        $output1 = new OutputStub();
        $output2 = new OutputStub();

        $response = new OutputResponse();

        // First handler succeeds
        $output1->initialize('handler1', $this->form, []);
        $response = $output1->handle($response);
        $this->assertTrue($response->getOverallStatus());

        // Second handler fails
        $output2->setShouldFail(true);
        $output2->initialize('handler2', $this->form, []);
        $response = $output2->handle($response);

        // Overall status should be failure
        $this->assertFalse($response->getOverallStatus());
    }

    public function testOutputHandlerDependencies(): void
    {
        $output1 = new OutputStub();
        $output2 = new OutputStub();
        $output3 = new OutputStub();

        $response = new OutputResponse();

        $output1->initialize('first', $this->form, []);
        $output2->initialize('second', $this->form, []);
        $output3->initialize('third', $this->form, []);

        $handlers = [
            'first' => $output1,
            'second' => $output2,
            'third' => $output3,
        ];

        // Each handler can access other handlers via setOutputHandlers
        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $response = $handler->handle($response);
        }

        // Verify all handlers were executed
        $this->assertCount(3, $response->getMessages());

        // Verify handlers can access each other
        $this->assertSame($handlers, $output1->getOutputHandlers());
        $this->assertSame($handlers, $output2->getOutputHandlers());
        $this->assertSame($handlers, $output3->getOutputHandlers());
    }
}

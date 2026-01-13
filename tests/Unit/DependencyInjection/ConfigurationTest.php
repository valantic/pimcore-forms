<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Valantic\PimcoreFormsBundle\DependencyInjection\Configuration;
use Valantic\PimcoreFormsBundle\Tests\Support\ChoiceProviderStub;
use Valantic\PimcoreFormsBundle\Tests\Support\InputHandlerStub;
use Valantic\PimcoreFormsBundle\Tests\Support\RedirectHandlerStub;

#[AllowMockObjectsWithoutExpectations]
class ConfigurationTest extends TestCase
{
    private Configuration $configuration;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testValidCompleteConfiguration(): void
    {
        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'contact' => [
                        'csrf' => true,
                        'method' => 'POST',
                        'translate' => [
                            'field_labels' => true,
                            'inline_choices' => true,
                        ],
                        'api_error_message_template' => '(%2$s) %1$s',
                        'redirect_handler' => RedirectHandlerStub::class,
                        'input_handler' => InputHandlerStub::class,
                        'outputs' => [
                            [
                                'type' => 'email',
                                'options' => ['to' => 'test@example.com'],
                            ],
                        ],
                        'fields' => [
                            [
                                'type' => 'TextType',
                                'constraints' => ['NotBlank'],
                                'options' => ['label' => 'Name'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertArrayHasKey('forms', $processedConfig);
        $this->assertArrayHasKey('contact', $processedConfig['forms']);
        $this->assertTrue($processedConfig['forms']['contact']['csrf']);
        $this->assertSame('POST', $processedConfig['forms']['contact']['method']);
        $this->assertTrue($processedConfig['forms']['contact']['translate']['field_labels']);
        $this->assertTrue($processedConfig['forms']['contact']['translate']['inline_choices']);
        $this->assertSame('(%2$s) %1$s', $processedConfig['forms']['contact']['api_error_message_template']);
        $this->assertSame(RedirectHandlerStub::class, $processedConfig['forms']['contact']['redirect_handler']);
        $this->assertSame(InputHandlerStub::class, $processedConfig['forms']['contact']['input_handler']);
    }

    public function testDefaultValues(): void
    {
        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'simple' => [
                        'outputs' => [
                            ['type' => 'log', 'options' => []],
                        ],
                        'fields' => [
                            ['type' => 'TextType'],
                        ],
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $form = $processedConfig['forms']['simple'];
        $this->assertTrue($form['csrf'], 'CSRF should default to true');
        $this->assertSame('POST', $form['method'], 'Method should default to POST');
        $this->assertNull($form['api_error_message_template'], 'API error template should default to null');
        $this->assertNull($form['redirect_handler'], 'Redirect handler should default to null');
        $this->assertNull($form['input_handler'], 'Input handler should default to null');
        $this->assertFalse($form['translate']['field_labels'], 'Field labels translation should default to false');
        $this->assertFalse($form['translate']['inline_choices'], 'Inline choices translation should default to false');
        $this->assertSame([], $form['fields'][0]['constraints'], 'Constraints should default to empty array');
        $this->assertSame([], $form['fields'][0]['options'], 'Options should default to empty array');
    }

    public function testMethodValidation(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Must be GET or POST');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'method' => 'PUT',
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testInvalidRedirectHandler(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid redirect handler class found');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'redirect_handler' => \stdClass::class,
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testInvalidInputHandler(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid input handler class found');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'input_handler' => \stdClass::class,
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testInvalidFieldType(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid type class found');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'NonExistentType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testInvalidConstraint(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid constraint class found');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [
                            [
                                'type' => 'TextType',
                                'constraints' => ['NonExistentConstraint'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testInvalidChoiceProvider(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Provider class must exist and implement');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [
                            [
                                'type' => 'ChoiceType',
                                'provider' => \stdClass::class,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testValidChoiceProvider(): void
    {
        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [
                            [
                                'type' => 'ChoiceType',
                                'provider' => ChoiceProviderStub::class,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);
        $this->assertSame(
            ChoiceProviderStub::class,
            $processedConfig['forms']['test']['fields'][0]['provider'],
        );
    }

    public function testEmailOutputRequiresToEmail(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('missing/invalid configuration options');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [
                            ['type' => 'email', 'options' => []],
                        ],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testHttpOutputRequiresUrl(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('missing/invalid configuration options');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [
                            ['type' => 'http', 'options' => []],
                        ],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testDataObjectOutputRequiresClassAndPath(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('missing/invalid configuration options');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [
                            ['type' => 'data_object', 'options' => ['class' => 'MyClass']],
                        ],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testAssetOutputRequiresFieldsAndPath(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('missing/invalid configuration options');

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [
                            ['type' => 'asset', 'options' => ['path' => '/assets']],
                        ],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testFieldsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }

    public function testOutputsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [
            'valantic_pimcore_forms' => [
                'forms' => [
                    'test' => [
                        'outputs' => [],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, $config);
    }
}

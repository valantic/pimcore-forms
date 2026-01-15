<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Valantic\PimcoreFormsBundle\DependencyInjection\ValanticPimcoreFormsExtension;
use Valantic\PimcoreFormsBundle\Form\InputHandler\InputHandlerInterface;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Form\RedirectHandler\RedirectHandlerInterface;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

#[AllowMockObjectsWithoutExpectations]
class ValanticPimcoreFormsExtensionTest extends TestCase
{
    private ValanticPimcoreFormsExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new ValanticPimcoreFormsExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadWithMinimalConfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasParameter(ConfigurationRepository::CONTAINER_TAG));
    }

    public function testLoadSetsConfigurationParameter(): void
    {
        $config = [
            [
                'forms' => [
                    'contact' => [
                        'csrf' => true,
                        'method' => 'POST',
                        'outputs' => [['type' => 'email', 'options' => ['to' => 'test@example.com']]],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $parameter = $this->container->getParameter(ConfigurationRepository::CONTAINER_TAG);
        $this->assertIsArray($parameter);
        $this->assertArrayHasKey('forms', $parameter);
        $this->assertArrayHasKey('contact', $parameter['forms']);
        $this->assertTrue($parameter['forms']['contact']['csrf']);
        $this->assertSame('POST', $parameter['forms']['contact']['method']);
    }

    public function testLoadRegistersOutputInterfaceForAutoconfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $autoconfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(OutputInterface::class, $autoconfiguredInstances);
        $this->assertTrue($autoconfiguredInstances[OutputInterface::class]->hasTag(ValanticPimcoreFormsExtension::TAG_OUTPUT));
    }

    public function testLoadRegistersRedirectHandlerInterfaceForAutoconfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $autoconfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(RedirectHandlerInterface::class, $autoconfiguredInstances);
        $this->assertTrue($autoconfiguredInstances[RedirectHandlerInterface::class]->hasTag(ValanticPimcoreFormsExtension::TAG_REDIRECT_HANDLER));
    }

    public function testLoadRegistersInputHandlerInterfaceForAutoconfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $autoconfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(InputHandlerInterface::class, $autoconfiguredInstances);
        $this->assertTrue($autoconfiguredInstances[InputHandlerInterface::class]->hasTag(ValanticPimcoreFormsExtension::TAG_INPUT_HANDLER));
    }

    public function testLoadRegistersChoicesInterfaceForAutoconfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $autoconfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(ChoicesInterface::class, $autoconfiguredInstances);
        $this->assertTrue($autoconfiguredInstances[ChoicesInterface::class]->hasTag(ValanticPimcoreFormsExtension::TAG_CHOICES));
    }

    public function testLoadWithComplexConfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'form1' => [
                        'csrf' => true,
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                    'form2' => [
                        'csrf' => false,
                        'method' => 'GET',
                        'outputs' => [['type' => 'email', 'options' => ['to' => 'test@example.com']]],
                        'fields' => [['type' => 'EmailType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $parameter = $this->container->getParameter(ConfigurationRepository::CONTAINER_TAG);
        $this->assertIsArray($parameter);
        $this->assertArrayHasKey('forms', $parameter);
        $this->assertCount(2, $parameter['forms']);
        $this->assertArrayHasKey('form1', $parameter['forms']);
        $this->assertArrayHasKey('form2', $parameter['forms']);
        $this->assertTrue($parameter['forms']['form1']['csrf']);
        $this->assertFalse($parameter['forms']['form2']['csrf']);
        $this->assertSame('GET', $parameter['forms']['form2']['method']);
    }

    public function testLoadProcessesConfiguration(): void
    {
        $config = [
            [
                'forms' => [
                    'test' => [
                        'outputs' => [['type' => 'log']],
                        'fields' => [['type' => 'TextType']],
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $parameter = $this->container->getParameter(ConfigurationRepository::CONTAINER_TAG);

        // Verify defaults were applied during processing
        $this->assertTrue($parameter['forms']['test']['csrf']);
        $this->assertSame('POST', $parameter['forms']['test']['method']);
        $this->assertFalse($parameter['forms']['test']['translate']['field_labels']);
        $this->assertFalse($parameter['forms']['test']['translate']['inline_choices']);
    }
}

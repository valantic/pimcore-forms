<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Examples\CustomInputHandler;

use App\Form\InputHandler\QueryStringInputHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Form\InputHandler\QueryStringInputHandler
 */
#[AllowMockObjectsWithoutExpectations]
class QueryStringInputHandlerTest extends TestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory = Forms::createFormFactory();
    }

    /**
     * Test handler pre-populates form fields from query parameters.
     */
    public function testHandlePopulatesFormFieldsFromQueryParams(): void
    {
        $request = Request::create('/contact', 'GET', [
            'utm_source' => 'newsletter',
            'utm_campaign' => 'spring2024',
            'ref' => 'FRIEND123',
        ]);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('source', TextType::class)
            ->add('campaign', TextType::class)
            ->add('referralCode', TextType::class)
            ->getForm()
        ;

        $handler = new QueryStringInputHandler();

        $config = [
            'mapping' => [
                'utm_source' => 'source',
                'utm_campaign' => 'campaign',
                'ref' => 'referralCode',
            ],
        ];

        $handler->handle($request, $form, $config);

        $data = $form->getData();
        $this->assertEquals('newsletter', $data['source']);
        $this->assertEquals('spring2024', $data['campaign']);
        $this->assertEquals('FRIEND123', $data['referralCode']);
    }

    /**
     * Test handler ignores unmapped query parameters.
     */
    public function testHandleIgnoresUnmappedQueryParams(): void
    {
        $request = Request::create('/contact', 'GET', [
            'utm_source' => 'newsletter',
            'other_param' => 'ignored',
        ]);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('source', TextType::class)
            ->getForm()
        ;

        $handler = new QueryStringInputHandler();

        $config = [
            'mapping' => [
                'utm_source' => 'source',
            ],
        ];

        $handler->handle($request, $form, $config);

        $data = $form->getData();
        $this->assertEquals('newsletter', $data['source']);
        $this->assertArrayNotHasKey('other_param', $data);
    }

    /**
     * Test handler skips query params for non-existent form fields.
     */
    public function testHandleSkipsNonExistentFormFields(): void
    {
        $request = Request::create('/contact', 'GET', [
            'utm_source' => 'newsletter',
        ]);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('email', TextType::class)
            ->getForm()
        ;

        $handler = new QueryStringInputHandler();

        $config = [
            'mapping' => [
                'utm_source' => 'nonexistent_field',
            ],
        ];

        $handler->handle($request, $form, $config);

        $data = $form->getData();
        $this->assertArrayNotHasKey('nonexistent_field', $data ?? []);
    }

    /**
     * Test handler with empty mapping does nothing.
     */
    public function testHandleWithEmptyMappingDoesNothing(): void
    {
        $request = Request::create('/contact', 'GET', [
            'utm_source' => 'newsletter',
        ]);

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('source', TextType::class)
            ->getForm()
        ;

        $handler = new QueryStringInputHandler();

        $config = [];

        $handler->handle($request, $form, $config);

        $data = $form->getData();
        $this->assertNull($data);
    }

    /**
     * Test handler merges with existing form data.
     */
    public function testHandleMergesWithExistingFormData(): void
    {
        $request = Request::create('/contact', 'GET', [
            'utm_source' => 'newsletter',
        ]);

        $form = $this->formFactory->createBuilder(FormType::class, [
            'email' => 'john@example.com',
        ])
            ->add('email', TextType::class)
            ->add('source', TextType::class)
            ->getForm()
        ;

        $handler = new QueryStringInputHandler();

        $config = [
            'mapping' => [
                'utm_source' => 'source',
            ],
        ];

        $handler->handle($request, $form, $config);

        $data = $form->getData();
        $this->assertEquals('john@example.com', $data['email']);
        $this->assertEquals('newsletter', $data['source']);
    }
}

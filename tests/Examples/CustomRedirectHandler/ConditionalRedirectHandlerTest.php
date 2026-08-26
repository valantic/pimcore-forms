<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Examples\CustomRedirectHandler;

use App\Form\RedirectHandler\ConditionalRedirectHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Form\RedirectHandler\ConditionalRedirectHandler
 */
#[AllowMockObjectsWithoutExpectations]
class ConditionalRedirectHandlerTest extends TestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory = Forms::createFormFactory();
    }

    /**
     * Test redirect based on field value condition.
     */
    public function testGetRedirectUrlMatchesCondition(): void
    {
        $request = Request::create('/contact', 'POST');

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('inquiry_type', ChoiceType::class, [
                'choices' => ['Sales' => 'sales', 'Support' => 'support'],
            ])
            ->getForm()
        ;

        $form->submit(['inquiry_type' => 'sales']);

        $handler = new ConditionalRedirectHandler();

        $config = [
            'conditions' => [
                ['field' => 'inquiry_type', 'value' => 'sales', 'url' => '/thank-you/sales'],
                ['field' => 'inquiry_type', 'value' => 'support', 'url' => '/thank-you/support'],
            ],
            'defaultUrl' => '/thank-you',
        ];

        $url = $handler->getRedirectUrl($request, $form, true, $config);

        $this->assertEquals('/thank-you/sales', $url);
    }

    /**
     * Test default URL when no conditions match.
     */
    public function testGetRedirectUrlReturnsDefaultWhenNoMatch(): void
    {
        $request = Request::create('/contact', 'POST');

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('inquiry_type', TextType::class)
            ->getForm()
        ;

        $form->submit(['inquiry_type' => 'other']);

        $handler = new ConditionalRedirectHandler();

        $config = [
            'conditions' => [
                ['field' => 'inquiry_type', 'value' => 'sales', 'url' => '/thank-you/sales'],
                ['field' => 'inquiry_type', 'value' => 'support', 'url' => '/thank-you/support'],
            ],
            'defaultUrl' => '/thank-you',
        ];

        $url = $handler->getRedirectUrl($request, $form, true, $config);

        $this->assertEquals('/thank-you', $url);
    }

    /**
     * Test error URL on form submission failure.
     */
    public function testGetRedirectUrlReturnsErrorUrlOnFailure(): void
    {
        $request = Request::create('/contact', 'POST');

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('inquiry_type', TextType::class)
            ->getForm()
        ;

        $form->submit(['inquiry_type' => 'sales']);

        $handler = new ConditionalRedirectHandler();

        $config = [
            'conditions' => [
                ['field' => 'inquiry_type', 'value' => 'sales', 'url' => '/thank-you/sales'],
            ],
            'defaultUrl' => '/thank-you',
            'errorUrl' => '/error',
        ];

        $url = $handler->getRedirectUrl($request, $form, false, $config);

        $this->assertEquals('/error', $url);
    }

    /**
     * Test null return when no default URL configured.
     */
    public function testGetRedirectUrlReturnsNullWhenNoDefault(): void
    {
        $request = Request::create('/contact', 'POST');

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('inquiry_type', TextType::class)
            ->getForm()
        ;

        $form->submit(['inquiry_type' => 'other']);

        $handler = new ConditionalRedirectHandler();

        $config = [
            'conditions' => [
                ['field' => 'inquiry_type', 'value' => 'sales', 'url' => '/thank-you/sales'],
            ],
        ];

        $url = $handler->getRedirectUrl($request, $form, true, $config);

        $this->assertNull($url);
    }

    /**
     * Test case-insensitive string matching.
     */
    public function testGetRedirectUrlMatchesCaseInsensitive(): void
    {
        $request = Request::create('/contact', 'POST');

        $form = $this->formFactory->createBuilder(FormType::class, null)
            ->add('inquiry_type', TextType::class)
            ->getForm()
        ;

        $form->submit(['inquiry_type' => 'SALES']);

        $handler = new ConditionalRedirectHandler();

        $config = [
            'conditions' => [
                ['field' => 'inquiry_type', 'value' => 'sales', 'url' => '/thank-you/sales'],
            ],
            'defaultUrl' => '/thank-you',
        ];

        $url = $handler->getRedirectUrl($request, $form, true, $config);

        $this->assertEquals('/thank-you/sales', $url);
    }
}

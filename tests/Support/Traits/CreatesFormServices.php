<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Traits;

use Limenius\Liform\Liform;
use Limenius\Liform\Resolver;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Form\Extension\ChoiceTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormAttributeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormConstraintExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormDataExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormNameExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\HiddenTypeExtension;
use Valantic\PimcoreFormsBundle\Form\FormErrorNormalizer;
use Valantic\PimcoreFormsBundle\Form\Transformer\ArrayTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\BooleanTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\ChoiceTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\CompoundTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\IntegerTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\NumberTransformer;
use Valantic\PimcoreFormsBundle\Form\Transformer\StringTransformer;
use Valantic\PimcoreFormsBundle\Repository\ChoicesRepository;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\InputHandlerRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;
use Valantic\PimcoreFormsBundle\Service\FormService;
use Valantic\PimcoreFormsBundle\Tests\Support\OutputStub;

/**
 * Wires a FormService backed by real Symfony form/validation/Liform components,
 * so functional tests exercise the actual form-building and schema-generation logic
 * instead of stubbed return values.
 */
trait CreatesFormServices
{
    /**
     * @param array<string,mixed> $config
     */
    protected function createRealFormService(
        array $config,
        ?RequestStack $requestStack = null,
        ?MockObject $outputRepository = null,
        ?MockObject $inputHandlerRepository = null,
        ?MockObject $redirectHandlerRepository = null,
    ): FormService {
        $configRepository = $this->createMock(ConfigurationRepository::class);
        $configRepository->method('get')->willReturn($config);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/form/api/test');

        $choicesRepository = $this->createMock(ChoicesRepository::class);

        if ($outputRepository === null) {
            $outputRepository = $this->createMock(OutputRepository::class);
            $outputRepository->method('get')->willReturn(new OutputStub());
        }

        $requestStack ??= new RequestStack();
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack->push($request);

        $csrfTokenManager = new CsrfTokenManager(
            new UriSafeTokenGenerator(),
            new SessionTokenStorage($requestStack),
        );

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new CsrfExtension($csrfTokenManager))
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()
        ;

        $builder = new Builder($urlGenerator, $translator, $formFactory, $choicesRepository);

        $resolver = new Resolver();
        $liform = new Liform($resolver);
        $resolver->setTransformer('text', new StringTransformer($translator, null));
        $resolver->setTransformer('textarea', new StringTransformer($translator, null));
        $resolver->setTransformer('email', new StringTransformer($translator, null));
        $resolver->setTransformer('integer', new IntegerTransformer($translator, null));
        $resolver->setTransformer('number', new NumberTransformer($translator, null));
        $resolver->setTransformer('choice', new ChoiceTransformer($translator, null));
        $resolver->setTransformer('checkbox', new BooleanTransformer($translator, null));
        $resolver->setTransformer('collection', new ArrayTransformer($translator, $resolver));
        $resolver->setTransformer('form', new CompoundTransformer($translator, $resolver));

        return new FormService(
            $configRepository,
            $outputRepository,
            $redirectHandlerRepository ?? $this->createMock(RedirectHandlerRepository::class),
            $inputHandlerRepository ?? $this->createMock(InputHandlerRepository::class),
            $builder,
            $liform,
            new FormErrorNormalizer($translator, $configRepository),
            new FormTypeExtension(),
            new FormNameExtension(),
            new FormConstraintExtension(),
            new FormAttributeExtension(),
            new ChoiceTypeExtension(),
            new HiddenTypeExtension(),
            new FormDataExtension(),
            $requestStack,
        );
    }
}

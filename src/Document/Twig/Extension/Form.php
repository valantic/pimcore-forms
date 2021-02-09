<?php

namespace Valantic\PimcoreFormsBundle\Document\Twig\Extension;

use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Valantic\PimcoreFormsBundle\Service\FormService;

class Form extends AbstractExtension
{
    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('valantic_form',
                fn(string $name): FormView => $this->formService->buildForm($name)->createView(),
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction('valantic_form_json',
                fn(string $name): string => json_encode($this->formService->buildJson($name)),
                [
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }
}

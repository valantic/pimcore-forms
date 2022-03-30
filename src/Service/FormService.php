<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Service;

use Limenius\Liform\Liform;
use RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Valantic\PimcoreFormsBundle\Exception\InvalidFormConfigException;
use Valantic\PimcoreFormsBundle\Form\Builder;
use Valantic\PimcoreFormsBundle\Form\Extension\ChoiceTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormAttributeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormConstraintExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormNameExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\HiddenTypeExtension;
use Valantic\PimcoreFormsBundle\Form\FormErrorNormalizer;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\InputHandlerRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;

class FormService
{
    protected ConfigurationRepository $configurationRepository;
    protected Builder $builder;
    protected Liform $liform;
    protected OutputRepository $outputRepository;
    protected RedirectHandlerRepository $redirectHandlerRepository;
    protected FormErrorNormalizer $errorNormalizer;
    protected InputHandlerRepository $inputHandlerRepository;
    protected RequestStack $requestStack;

    public function __construct(
        ConfigurationRepository $configurationRepository,
        OutputRepository $outputRepository,
        RedirectHandlerRepository $redirectHandlerRepository,
        InputHandlerRepository $inputHandlerRepository,
        Builder $builder,
        Liform $liform,
        FormErrorNormalizer $errorNormalizer,
        FormTypeExtension $formTypeExtension,
        FormNameExtension $formNameExtension,
        FormConstraintExtension $formConstraintExtension,
        FormAttributeExtension $formAttributeExtension,
        ChoiceTypeExtension $choiceTypeExtension,
        HiddenTypeExtension $hiddenTypeExtension,
        RequestStack $requestStack
    ) {
        $this->builder = $builder;
        $this->configurationRepository = $configurationRepository;
        $this->redirectHandlerRepository = $redirectHandlerRepository;
        $this->inputHandlerRepository = $inputHandlerRepository;
        $this->outputRepository = $outputRepository;
        $this->errorNormalizer = $errorNormalizer;

        $liform->addExtension($formTypeExtension);
        $liform->addExtension($formNameExtension);
        $liform->addExtension($formConstraintExtension);
        $liform->addExtension($formAttributeExtension);
        $liform->addExtension($choiceTypeExtension);
        $liform->addExtension($hiddenTypeExtension);
        $this->liform = $liform;
        $this->requestStack = $requestStack;
    }

    public function build(string $name): FormBuilderInterface
    {
        $config = $this->getConfig($name);
        $form = $this->builder->form($name, $config);

        foreach ($config['fields'] as $fieldName => $definition) {
            $form->add($fieldName, ...$this->builder->field($definition, $config));
        }

        if ($form->getOption('csrf_protection') === true) {
            /** @var CsrfTokenManager $tokenProvider */
            $tokenProvider = $form->getOption('csrf_token_manager');
            $token = $tokenProvider->getToken($name)->getValue();
            $form->add($form->getOption('csrf_field_name'), HiddenType::class, ['data' => $token]);
        }

        $inputHandlerName = $this->getConfig($form->getName())['input_handler'];

        if ($inputHandlerName !== null) {
            $inputHandler = $this->inputHandlerRepository->get($inputHandlerName);
            $inputHandler->initialize($form->getForm(), $this->requestStack->getMasterRequest());
            $inputs = $inputHandler->getAll();

            foreach ($inputs as $fieldName => $value) {
                $field = $form->get($fieldName);
                $field->setData($value);
            }
        }

        return $form;
    }

    /**
     * @param FormInterface $form
     *
     * @return array<mixed>
     */
    public function json(FormInterface $form): array
    {
        $data = $this->liform->transform($form);
        $data['properties'] = array_values($data['properties']);

        return $data;
    }

    /**
     * @param string $name
     *
     * @return array<mixed>
     */
    public function buildJson(string $name): array
    {
        return $this->json($this->buildForm($name));
    }

    public function buildJsonString(string $name): string
    {
        $json = json_encode($this->buildJson($name));
        if ($json === false) {
            throw new RuntimeException();
        }

        return $json;
    }

    public function buildForm(string $name): FormInterface
    {
        return $this->build($name)->getForm();
    }

    /**
     * The returned error message can be customized with a simple sprintf-based template system in your config using:
     * valantic_pimcore_forms.forms.[form_name].api_error_message_template.
     *
     * !!! Important: Be careful with HTML tags inside the template. Support depends on your frontend implementation.
     *
     * Available params:
     *     %1$s = Error message
     *     %2$s = Localized field label
     * Sample for a valid template string: '(%2$s) %1$s'
     * Sample result for example in German: '(Dateiupload) Die Datei ist gross (12MB), die maximal zulässige Grösse beträgt 10MB.'
     *
     * @param FormInterface $form
     *
     * @throws SerializerException
     *
     * @return array<mixed>
     */
    public function errors(FormInterface $form): array
    {
        return $this->errorNormalizer->normalize($form);
    }

    public function outputs(FormInterface $form): bool
    {
        $status = true;

        $outputs = $this->getConfig($form->getName())['outputs'];
        $handlers = [];
        foreach ($outputs as $name => ['type' => $type, 'options' => $options]) {
            $output = $this->outputRepository->get($type);
            $output->initialize($name, $form, $options);
            $handlers[$name] = $output;
        }

        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $status = $handler->handle() && $status; // DO NOT SWAP the two arguments!!!
        }

        return $status;
    }

    public function getRedirectUrl(FormInterface $form, bool $success): ?string
    {
        $handlerName = $this->getConfig($form->getName())['redirect_handler'];

        if ($handlerName === null) {
            return null;
        }

        $handler = $this->redirectHandlerRepository->get($handlerName);

        return $success ? $handler->onSuccess() : $handler->onFailure();
    }

    /**
     * @param string $name
     *
     * @return array<string,mixed>
     */
    protected function getConfig(string $name): array
    {
        $config = $this->configurationRepository->get()['forms'][$name];

        if (empty($config) || !is_array($config)) {
            throw new InvalidFormConfigException($name);
        }

        return $config;
    }
}

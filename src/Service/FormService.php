<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Service;

use Limenius\Liform\Liform;
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
use Valantic\PimcoreFormsBundle\Form\Extension\FormDataExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormNameExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\FormTypeExtension;
use Valantic\PimcoreFormsBundle\Form\Extension\HiddenTypeExtension;
use Valantic\PimcoreFormsBundle\Form\FormErrorNormalizer;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;
use Valantic\PimcoreFormsBundle\Repository\InputHandlerRepository;
use Valantic\PimcoreFormsBundle\Repository\OutputRepository;
use Valantic\PimcoreFormsBundle\Repository\RedirectHandlerRepository;

class FormService
{
    protected Liform $liform;

    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected OutputRepository $outputRepository,
        protected RedirectHandlerRepository $redirectHandlerRepository,
        protected InputHandlerRepository $inputHandlerRepository,
        protected Builder $builder,
        Liform $liform,
        protected FormErrorNormalizer $errorNormalizer,
        FormTypeExtension $formTypeExtension,
        FormNameExtension $formNameExtension,
        FormConstraintExtension $formConstraintExtension,
        FormAttributeExtension $formAttributeExtension,
        ChoiceTypeExtension $choiceTypeExtension,
        HiddenTypeExtension $hiddenTypeExtension,
        FormDataExtension $formDataExtension,
        protected RequestStack $requestStack
    ) {
        $liform->addExtension($formTypeExtension);
        $liform->addExtension($formNameExtension);
        $liform->addExtension($formConstraintExtension);
        $liform->addExtension($formAttributeExtension);
        $liform->addExtension($choiceTypeExtension);
        $liform->addExtension($hiddenTypeExtension);
        $liform->addExtension($formDataExtension);
        $this->liform = $liform;
    }

    public function build(string $name): FormBuilderInterface
    {
        $config = $this->getConfig($name);
        $form = $this->builder->form($name, $config);

        foreach ($config['fields'] as $fieldName => $definition) {
            $form->add($fieldName, ...$this->builder->field($name, $definition, $config));
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
            $inputHandler->initialize($form->getForm(), $this->requestStack->getMainRequest());
            $inputs = $inputHandler->get();

            foreach ($inputs as $fieldName => $data) {
                if (!$form->has($fieldName)) {
                    continue;
                }

                $field = $form->get($fieldName);
                $field->setData($data);
            }
        }

        return $form;
    }

    /**
     * @return array<mixed>
     */
    public function json(FormInterface $form): array
    {
        $data = $this->liform->transform($form);
        $data['properties'] = array_values($data['properties']);

        return $data;
    }

    /**
     * @return array<mixed>
     */
    public function buildJson(string $name): array
    {
        return $this->json($this->buildForm($name));
    }

    public function buildJsonString(string $name): string
    {
        return json_encode($this->buildJson($name), \JSON_THROW_ON_ERROR);
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
     * @throws SerializerException
     *
     * @return array<mixed>
     */
    public function errors(FormInterface $form): array
    {
        return $this->errorNormalizer->normalize($form);
    }

    public function outputs(FormInterface $form): OutputResponse
    {
        $outputResponse = new OutputResponse();

        $outputs = $this->getConfig($form->getName())['outputs'];
        $handlers = [];
        foreach ($outputs as $name => ['type' => $type, 'options' => $options]) {
            $output = $this->outputRepository->get($type);
            $output->initialize($name, $form, $options);
            $handlers[$name] = $output;
        }

        foreach ($handlers as $handler) {
            $handler->setOutputHandlers($handlers);
            $outputResponse = $handler->handle($outputResponse);
        }

        return $outputResponse;
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

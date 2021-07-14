<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Http\ApiResponse;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

class FormErrorNormalizer implements NormalizerInterface
{
    protected TranslatorInterface $translator;
    protected ConfigurationRepository $configurationRepository;

    public function __construct(TranslatorInterface $translator, ConfigurationRepository $configurationRepository)
    {
        $this->translator = $translator;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->convertFormToArray($object);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof FormInterface && $data->isSubmitted() && !$data->isValid();
    }

    /**
     * @param FormInterface $data
     *
     * @return array<int,array<mixed>>
     *
     * @see https://github.com/schmittjoh/serializer/blob/master/src/Handler/FormErrorHandler.php
     */
    protected function convertFormToArray(FormInterface $data): array
    {
        $errors = [];
        $formErrorMessageTemplate = $this->configurationRepository->get()['forms'][$data->getName()]['form_config']['api_error_message_template'] ?? null;

        foreach ($data->getErrors() as $error) {
            /** @var FormError $error */
            $errors[] = $this->buildErrorEntry($error, $formErrorMessageTemplate);
        }

        foreach ($data->all() as $child) {
            if ($child instanceof FormInterface) {
                foreach ($child->getErrors() as $error) {
                    $errors[] = $this->buildErrorEntry($error, $formErrorMessageTemplate);
                }
            }
        }

        return array_values(array_filter($errors));
    }

    protected function buildErrorEntry(FormError $error, ?string $customErrorMessageTemplate = null): array
    {
        $message = $this->getErrorMessage($error);
        $label = (is_string($error->getOrigin()->getConfig()->getOption('label')))
            ? $error->getOrigin()->getConfig()->getOption('label')
            : '';

        if (!empty($label)) {
            if (null !== $this->translator) {
                $label = $this->translator->trans($label);
            }
        } else {
            // Don't use template based system because we have no $label value.
            // Probably a general form exception like (invalid CSRF token) and not a form field validation error
            $customErrorMessageTemplate = null;
        }

        // TODO possible optimization -> regex check for valid template string
        if (!empty($customErrorMessageTemplate)) {
            $message = sprintf($customErrorMessageTemplate, $message, $label);
        }

        return [
            'message' => $message,
            'type' => ApiResponse::MESSAGE_TYPE_ERROR,
            'field' => $error->getOrigin()->getName(),
            'label' => $label
        ];
    }

    /**
     * @param FormError $error
     *
     * @return string|null
     *
     * @see https://github.com/schmittjoh/serializer/blob/master/src/Handler/FormErrorHandler.php
     */
    protected function getErrorMessage(FormError $error): ?string
    {
        if (null === $this->translator) {
            return $error->getMessage();
        }

        if (null !== $error->getMessagePluralization()) {
            if ($this->translator instanceof TranslatorInterface) {
                return $this->translator->trans($error->getMessageTemplate(), ['%count%' => $error->getMessagePluralization()] + $error->getMessageParameters(), 'validators');
            }

            return $this->translator->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters(), 'validators');
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters(), 'validators');
    }
}

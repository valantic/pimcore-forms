<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

class FormErrorNormalizer implements NormalizerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly ConfigurationRepository $configurationRepository
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        return $this->convertFormToArray($object);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof FormInterface && $data->isSubmitted() && !$data->isValid();
    }

    /**
     * @return array<int,array<mixed>>
     *
     * @see https://github.com/schmittjoh/serializer/blob/master/src/Handler/FormErrorHandler.php
     */
    protected function convertFormToArray(FormInterface $data): array
    {
        $errors = [];
        $formErrorMessageTemplate = $this->configurationRepository->get()['forms'][$data->getName()]['api_error_message_template'];

        foreach ($data->getErrors() as $error) {
            /** @var FormError $error */
            if ($error instanceof FormError) {
                $errors[] = $this->buildErrorEntry($error, $formErrorMessageTemplate);
            }
        }

        // TODO: possible optimization to catch all nested errors
        foreach ($data->all() as $child) {
            if ($child instanceof FormInterface) {
                foreach ($child->getErrors() as $error) {
                    if ($error instanceof FormErrorIterator) {
                        foreach ($error as $childError) {
                            if ($childError instanceof FormError) {
                                $errors[] = $this->buildErrorEntry($childError, $formErrorMessageTemplate);
                            }
                        }
                    } else {
                        $errors[] = $this->buildErrorEntry($error, $formErrorMessageTemplate);
                    }
                }
            }
        }

        return array_values(array_filter($errors));
    }

    /**
     * @param string|null $customErrorMessageTemplate
     *
     * @return array<string,mixed>
     */
    protected function buildErrorEntry(FormError $error, ?string $customErrorMessageTemplate = null): array
    {
        $message = $this->getErrorMessage($error);
        $label = ($error->getOrigin() instanceof FormInterface && is_string($error->getOrigin()->getConfig()->getOption('label')))
            ? $error->getOrigin()->getConfig()->getOption('label')
            : '';

        if (!empty($label)) {
            $label = $this->translator->trans($label);
        } else {
            // Don't use template based system because we have no $label value.
            // Probably a general form exception like (invalid CSRF token) and not a form field validation error
            $customErrorMessageTemplate = null;
        }

        if (!empty($customErrorMessageTemplate)) {
            $message = sprintf($customErrorMessageTemplate, $message, $label);
        }

        return [
            'message' => $message,
            'type' => MessageConstants::MESSAGE_TYPE_ERROR,
            'field' => $error->getOrigin() instanceof FormInterface ? $error->getOrigin()->getName() : '',
            'label' => $label,
        ];
    }

    /**
     * @return string|null
     *
     * @see https://github.com/schmittjoh/serializer/blob/master/src/Handler/FormErrorHandler.php
     */
    protected function getErrorMessage(FormError $error): ?string
    {
        if (null !== $error->getMessagePluralization()) {
            return $this->translator->trans($error->getMessageTemplate(), ['%count%' => $error->getMessagePluralization()] + $error->getMessageParameters(), 'validators');
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters(), 'validators');
    }
}

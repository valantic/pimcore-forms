<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormErrorNormalizer implements NormalizerInterface
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
     * @return array<string,array<mixed>>
     *
     * @see https://github.com/schmittjoh/serializer/blob/master/src/Handler/FormErrorHandler.php
     */
    protected function convertFormToArray(FormInterface $data): array
    {
        $errors = [];

        $errors['form'] = [];
        foreach ($data->getErrors() as $error) {
            /** @var FormError $error */
            $errors['_form'][$error->getOrigin() !== null ? $error->getOrigin()->getName() : null] = $this->getErrorMessage($error);
        }

        foreach ($data->all() as $child) {
            if ($child instanceof FormInterface) {
                $childErrors = [];

                foreach ($child->getErrors() as $error) {
                    $childErrors[] = $this->getErrorMessage($error);
                }

                $errors[$child->getName()] = $childErrors;
            }
        }

        return array_filter($errors);
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

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\DependencyInjection\Configuration;
use Valantic\PimcoreFormsBundle\Form\Type\ConfigAwareInterface;
use Valantic\PimcoreFormsBundle\Repository\ChoicesRepository;

class Builder
{
    public function __construct(
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly TranslatorInterface $translator,
        protected readonly FormFactoryInterface $formFactory,
        protected readonly ChoicesRepository $choicesRepository,
    ) {
    }

    /**
     * @param array<string,mixed> $config
     */
    public function form(string $name, array $config): FormBuilderInterface
    {
        $builder = $this->formFactory
            ->createNamedBuilder($name, FormType::class, null, [
                'csrf_protection' => $config['csrf'],
            ])
        ;

        $builder->setMethod($config['method']);
        $builder->setAction($this->urlGenerator->generate('valantic_pimcoreforms_form_api', ['name' => $name]));

        return $builder;
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed> $formConfig
     *
     * @return array{class-string<FormTypeInterface>,array}
     */
    public function field(string $formName, array $definition, array $formConfig): array
    {
        $options = $this->getOptions($formName, $definition, $formConfig);

        $constraints = $this->getConstraints($definition, $options);

        if (!empty($constraints)) {
            $options['constraints'] = $constraints;
        }

        return [$this->getType($definition['type']), $options];
    }

    protected function getConstraintClass(string $name): string
    {
        if (!str_contains($name, '\\')) {
            return sprintf('%s%s', Configuration::SYMFONY_CONSTRAINTS_NAMESPACE, $name);
        }

        return $name;
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    protected function getType(string $name): string
    {
        if (!str_contains($name, '\\')) {
            $type = sprintf('%s%s', Configuration::SYMFONY_FORMTYPES_NAMESPACE, $name);
        } else {
            $type = $name;
        }

        return $type;
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed> $formConfig
     *
     * @return array<mixed>
     */
    protected function getOptions(string $formName, array $definition, array $formConfig): array
    {
        $options = $definition['options'];

        if (!empty($formConfig['translate']['field_labels']) && !empty($options['label'])) {
            $options['label'] = $this->translator->trans($options['label']);
        }

        if (in_array($this->getType($definition['type']), [DateType::class, TimeType::class], true)) {
            $options['widget'] ??= 'single_text';
        }

        if ($this->getType($definition['type']) === ChoiceType::class) {
            if (
                empty($definition['provider'])
                && !empty($formConfig['translate']['inline_choices'])
            ) {
                // Attribute(s) are matched via label hence both the actual label
                // and the "attribute label" need to be translated.
                foreach (['choices', 'choice_attr'] as $key) {
                    if (!array_key_exists($key, $definition['options'])) {
                        continue;
                    }

                    $options[$key] = array_combine(
                        array_map(
                            fn (string $key): string => $this->translator->trans($key),
                            array_keys($definition['options'][$key]),
                        ),
                        $definition['options'][$key],
                    );
                }
            }

            if (!empty($definition['provider']) && is_string($definition['provider'])) {
                $choices = $this->choicesRepository->get($definition['provider']);

                if ($choices instanceof ConfigAwareInterface) {
                    $choices->setFormName($formName);
                    $choices->setFieldConfig($formConfig);
                }

                $options['choices'] = $choices->choices();
                $options['choice_value'] = fn ($a) => $a;
                $options['choice_label'] = fn ($choice, $key, $value) => $choices->choiceLabel($choice, $key, $value);
                $options['choice_attr'] = fn ($choice, $key, $value) => $choices->choiceAttribute($choice, $key, $value);
            }
        }

        return $options;
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<mixed> $options
     *
     * @return array<mixed>
     */
    protected function getConstraints(array $definition, array $options): array
    {
        $constraints = [];

        foreach ($definition['constraints'] as $constraint) {
            $className = null;
            $payload = null;

            if (is_string($constraint)) {
                $className = $this->getConstraintClass($constraint);
            } else {
                $className = $this->getConstraintClass((string) array_keys($constraint)[0]);
                $payload = array_values($constraint)[0];
            }

            if ($className === Choice::class) {
                $payload['choices'] ??= $options['choices'];
                $payload['multiple'] ??= $options['multiple'] ?? false;
            }

            $constraints[] = new $className($payload);
        }

        return $constraints;
    }
}

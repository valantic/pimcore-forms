<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\DependencyInjection\Configuration;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;

class Builder
{
    protected ContainerInterface $container;
    protected UrlGeneratorInterface $urlGenerator;
    protected TranslatorInterface $translator;

    public function __construct(
        ContainerInterface $container,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator
    ) {
        $this->container = $container;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    /**
     * @param string $name
     * @param array<string,mixed> $config
     *
     * @return FormBuilderInterface
     */
    public function form(string $name, array $config): FormBuilderInterface
    {
        /** @var FormBuilderInterface $builder */
        $builder = $this->container->get('form.factory')
            ->createNamedBuilder($name, FormType::class, null, [
                'csrf_protection' => $config['csrf'],
            ]);

        $builder->setMethod($config['method']);
        $builder->setAction($this->urlGenerator->generate('valantic_pimcoreforms_form_api', ['name' => $name]));

        return $builder;
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed> $formConfig
     *
     * @return array{string,array}
     */
    public function field(array $definition, array $formConfig): array
    {
        $options = $this->getOptions($definition, $formConfig);

        $constraints = $this->getConstraints($definition, $options);

        if (!empty($constraints)) {
            $options['constraints'] = $constraints;
        }

        return [$this->getType($definition['type']), $options];
    }

    protected function getConstraintClass(string $name): string
    {
        if (strpos($name, '\\') === false) {
            return sprintf('%s%s', Configuration::SYMFONY_CONSTRAINTS_NAMESPACE, $name);
        }

        return $name;
    }

    protected function getType(string $name): string
    {
        if (strpos($name, '\\') === false) {
            return sprintf('%s%s', Configuration::SYMFONY_FORMTYPES_NAMESPACE, $name);
        }

        return $name;
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed> $formConfig
     *
     * @return array<mixed>
     */
    protected function getOptions(array $definition, array $formConfig): array
    {
        $options = $definition['options'];

        if ($formConfig['translate']['field_labels'] && !empty($options['label'])) {
            $options['label'] = $this->translator->trans($options['label']);
        }

        if (in_array($this->getType($definition['type']), [DateType::class, TimeType::class], true)) {
            $options['widget'] ??= 'single_text';
        }
        if ($this->getType($definition['type']) === ChoiceType::class) {
            if (
                empty($definition['provider'])
                && $formConfig['translate']['inline_choices']
                && array_key_exists('choices', $definition['options'])
            ) {
                $options['choices'] = array_combine(
                    array_map(
                        fn(string $key): string => $this->translator->trans($key),
                        array_keys($definition['options']['choices'])
                    ),
                    $definition['options']['choices']
                );
            }
            if (!empty($definition['provider']) && is_string($definition['provider'])) {
                /** @var ChoicesInterface $choices */
                $choices = $this->container->get($definition['provider']);
                $options['choices'] = $choices->choices();
                $options['choice_label'] = fn($choice, $key, $value) => $choices->choiceLabel($choice, $key, $value);
                $options['choice_attr'] = fn($choice, $key, $value) => $choices->choiceAttribute($choice, $key, $value);
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

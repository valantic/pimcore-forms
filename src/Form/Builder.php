<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Valantic\PimcoreFormsBundle\DependencyInjection\Configuration;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;

class Builder
{
    protected ContainerInterface $container;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(ContainerInterface $container, UrlGeneratorInterface $urlGenerator)
    {
        $this->container = $container;
        $this->urlGenerator = $urlGenerator;
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
     *
     * @return array{string,array}
     */
    public function field(array $definition): array
    {
        $options = $this->getOptions($definition);

        if (array_key_exists('constraints', $definition)) {
            $constraints = [];
            foreach ($definition['constraints'] as $constraint) {
                if (is_string($constraint)) {
                    $constraintClass = $this->getConstraintClass($constraint);
                    $constraints[] = new $constraintClass();
                    continue;
                }

                $constraintClass = $this->getConstraintClass((string) array_keys($constraint)[0]);
                $constraints[] = new $constraintClass(array_values($constraint)[0]);
            }
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
     *
     * @return array<mixed>
     */
    protected function getOptions(array $definition): array
    {
        $options = $definition['options'];

        if ($this->getType($definition['type']) === ChoiceType::class && array_key_exists('provider', $definition) && is_string($definition['provider'])) {
            /** @var ChoicesInterface $choices */
            $choices = $this->container->get($definition['provider']);
            $options['choices'] = $choices->choices();
            $options['choice_label'] = fn($choice, $key, $value) => $choices->choiceLabel($choice, $key, $value);
            $options['choice_attr'] = fn($choice, $key, $value) => $choices->choiceAttribute($choice, $key, $value);
        }

        return $options;
    }
}

<?php

namespace Valantic\PimcoreFormsBundle\Form;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;
use Valantic\PimcoreFormsBundle\Repository\Configuration;

class Builder
{
    protected Configuration $configuration;
    protected FormBuilderInterface $formBuilder;
    protected ContainerInterface $container;

    public function __construct(Configuration $configuration, ContainerInterface $container)
    {
        $this->configuration = $configuration;
        $this->container = $container;
        $this->formBuilder = $this->container->get('form.factory')->createBuilder(FormType::class);;
    }

    public function get(string $name): FormBuilderInterface
    {
        $config = $this->configuration->get()['forms'][$name];
        $builder = $this->formBuilder;
        foreach ($config['fields'] as $name => $definition) {
            $builder->add($name, ...$this->getField($definition));
        }

        return $builder;
    }

    protected function getField(array $definition): array
    {
        $options = $this->getOptions($definition);
        $options['label'] = $definition['label'] ?? null;
        dump($options);

        return [$this->getType($definition['type']), $options];
    }

    protected function getType(string $type): string
    {
        return sprintf('Symfony\\Component\\Form\\Extension\\Core\\Type\\%s', $type);
    }

    protected function getOptions(array $definition): array
    {
        switch ($this->getType($definition['type'])) {
            case ChoiceType::class:
                if (is_array($definition['choices'])) {
                    return ['choices' => $definition['choices']];
                }
                /** @var ChoicesInterface $choices */
                $choices = $this->container->get($definition['choices']);

                return ['choices' => $choices->choices()];
                break;
            default:

                return [];
        }
    }
}

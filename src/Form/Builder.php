<?php

namespace Valantic\PimcoreFormsBundle\Form;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Valantic\PimcoreFormsBundle\Form\Type\ChoicesInterface;
use Valantic\PimcoreFormsBundle\Service\FormService;

class Builder
{
    protected ContainerInterface $container;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(ContainerInterface $container, UrlGeneratorInterface $urlGenerator)
    {
        $this->container = $container;
        $this->urlGenerator = $urlGenerator;
    }

    public function form(string $name, array $config): FormBuilderInterface
    {
        /** @var FormBuilderInterface $builder */
        $builder = $this->container->get('form.factory')
            ->createBuilder(FormType::class, null, [
                'csrf_protection' => $config['csrf'],
            ]);

        $builder->setMethod($config['method']);
        $builder->setAction($this->urlGenerator->generate('valantic_pimcoreforms_form_form'));
        $builder->add('' . FormService::INPUT_FORM_NAME . '', HiddenType::class, ['data' => $name]);

        return $builder;
    }

    public function field(array $definition): array
    {
        $options = $this->getOptions($definition);
        $options['label'] = $definition['label'] ?? null;

        if (array_key_exists('constraints', $definition)) {
            $constraints = [];
            foreach ($definition['constraints'] as $constraint) {
                if (is_string($constraint)) {
                    $constraintClass = 'Symfony\\Component\\Validator\\Constraints\\' . $constraint;
                    $constraints[] = new $constraintClass();
                    continue;
                }
                $constraintClass = 'Symfony\\Component\\Validator\\Constraints\\' . array_keys($constraint)[0];
                $constraints[] = new $constraintClass(array_values($constraint)[0]);
            }
            $options['constraints'] = $constraints;
        }

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
            default:

                return [];
        }
    }
}

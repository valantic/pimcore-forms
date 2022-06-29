<?php

declare(strict_types=1);

/*
 * This file is part of the Limenius\LiformBundle package.
 *
 * (c) Limenius <https://github.com/Limenius/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Valantic\PimcoreFormsBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Limenius\Liform\Transformer\TransformerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Nacho Martín <nacho@limenius.com>
 */
class TransformerCompilerPass implements CompilerPassInterface
{
    public const TRANSFORMER_TAG = 'liform.transformer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Limenius\Liform\Resolver')) {
            return;
        }

        $resolver = $container->getDefinition('Limenius\Liform\Resolver');

        foreach ($container->findTaggedServiceIds(self::TRANSFORMER_TAG) as $id => $attributes) {
            $transformer = $container->getDefinition($id);

            $transformerClass = $transformer->getClass();

            if (empty($transformerClass)) {
                continue;
            }

            $implements = class_implements($transformerClass);

            if ($implements === false) {
                continue;
            }
            if (!isset($implements[TransformerInterface::class])) {
                throw new InvalidArgumentException(sprintf("The service %s was tagged as a '%s' but does not implement the mandatory %s", $id, self::TRANSFORMER_TAG, TransformerInterface::class));
            }

            foreach ($attributes as $attribute) {
                if (!isset($attribute['form_type'])) {
                    throw new InvalidArgumentException(sprintf("The service %s was tagged as a '%s' but does not specify the mandatory 'form_type' option.", $id, self::TRANSFORMER_TAG));
                }

                $widget = $attribute['widget'] ?? null;

                $resolver->addMethodCall('setTransformer', [$attribute['form_type'], new Reference($id), $widget]);
            }
        }
    }
}

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

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Nacho Martín <nacho@limenius.com>
 */
class ExtensionCompilerPass implements CompilerPassInterface
{
    final public const EXTENSION_TAG = 'liform.extension';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Limenius\Liform\Liform')) {
            return;
        }

        $liform = $container->getDefinition('Limenius\Liform\Liform');

        foreach (array_keys($container->findTaggedServiceIds(self::EXTENSION_TAG)) as $id) {
            $extension = $container->getDefinition($id);

            $extensionClass = $extension->getClass();

            if (empty($extensionClass)) {
                continue;
            }

            $implements = class_implements($extensionClass);

            if ($implements === false) {
                continue;
            }

            if (!isset($implements[ExtensionInterface::class])) {
                throw new \InvalidArgumentException(sprintf("The service %s was tagged as a '%s' but does not implement the mandatory %s", $id, self::EXTENSION_TAG, ExtensionInterface::class));
            }

            $liform->addMethodCall('addExtension', [$extension]);
        }
    }
}

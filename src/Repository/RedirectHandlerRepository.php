<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Repository;

use Valantic\PimcoreFormsBundle\Exception\UnknownRedirectHandlerException;
use Valantic\PimcoreFormsBundle\Form\RedirectHandler\RedirectHandlerInterface;

class RedirectHandlerRepository
{
    /**
     * @var array<string,RedirectHandlerInterface>
     */
    protected array $redirectHandlers;

    /**
     * @param iterable<RedirectHandlerInterface> $redirectHandlers
     */
    public function __construct(iterable $redirectHandlers)
    {
        $this->redirectHandlers = $this->iterableToArray($redirectHandlers);
    }

    public function get(string $key): RedirectHandlerInterface
    {
        if (str_starts_with($key, '\\')) {
            $key = substr($key, 1);
        }

        if (!array_key_exists($key, $this->redirectHandlers)) {
            throw new UnknownRedirectHandlerException($key);
        }

        return clone $this->redirectHandlers[$key];
    }

    /**
     * @param iterable<object> $iterables
     *
     * @return array<string,RedirectHandlerInterface>
     */
    public function iterableToArray(iterable $iterables): array
    {
        $arr = [];

        foreach ($iterables as $iterable) {
            if (!($iterable instanceof RedirectHandlerInterface)) {
                continue;
            }

            $name = get_class($iterable);
            $arr[$name] = $iterable;
        }

        return $arr;
    }
}

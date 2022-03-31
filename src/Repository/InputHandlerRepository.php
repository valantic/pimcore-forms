<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Repository;

use Valantic\PimcoreFormsBundle\Exception\UnknownInputHandlerException;
use Valantic\PimcoreFormsBundle\Form\InputHandler\InputHandlerInterface;

class InputHandlerRepository
{
    /**
     * @var array<string,InputHandlerInterface>
     */
    protected array $inputHandlers;

    /**
     * @param iterable<InputHandlerInterface> $inputHandlers
     */
    public function __construct(iterable $inputHandlers)
    {
        $this->inputHandlers = $this->iterableToArray($inputHandlers);
    }

    public function get(string $key): InputHandlerInterface
    {
        if (strpos($key, '\\') === 0) {
            $key = substr($key, 1);
        }

        if (!array_key_exists($key, $this->inputHandlers)) {
            throw new UnknownInputHandlerException($key);
        }

        return clone $this->inputHandlers[$key];
    }

    /**
     * @param iterable<object> $iterables
     *
     * @return array<string,InputHandlerInterface>
     */
    public function iterableToArray(iterable $iterables): array
    {
        $arr = [];

        foreach ($iterables as $iterable) {
            if (!($iterable instanceof InputHandlerInterface)) {
                continue;
            }

            $name = get_class($iterable);
            $arr[$name] = $iterable;
        }

        return $arr;
    }
}

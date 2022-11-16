<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Repository;

use Valantic\PimcoreFormsBundle\Exception\DuplicateOutputException;
use Valantic\PimcoreFormsBundle\Exception\UnknownOutputException;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;

class OutputRepository
{
    /**
     * @var array<string,OutputInterface>
     */
    protected array $outputs;

    /**
     * @param iterable<OutputInterface> $outputs
     */
    public function __construct(iterable $outputs)
    {
        $this->outputs = $this->iterableToArray($outputs);
    }

    /**
     * @return OutputInterface[]
     */
    public function all(): array
    {
        return $this->outputs;
    }

    public function get(string $key): OutputInterface
    {
        if (!array_key_exists($key, $this->outputs)) {
            throw new UnknownOutputException($key);
        }

        return clone $this->outputs[$key];
    }

    /**
     * @param iterable<object> $iterables
     *
     * @return array<string,OutputInterface>
     */
    public function iterableToArray(iterable $iterables): array
    {
        $arr = [];

        $names = [];

        foreach ($iterables as $iterable) {
            if (!($iterable instanceof OutputInterface)) {
                continue;
            }

            $name = $iterable::name();
            $names[] = $name;
            $arr[$name] = $iterable;
        }

        if (count(array_unique($names)) !== count($names)) {
            throw new DuplicateOutputException(array_keys(array_filter(array_count_values($names), fn (int $count): bool => $count > 1)));
        }

        return $arr;
    }
}

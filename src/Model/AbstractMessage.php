<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Model;

use Iterator;

/**
 * @implements Iterator<string, mixed>
 */
abstract class AbstractMessage implements \JsonSerializable, \Iterator, \Stringable
{
    protected ?string $position = null;

    public function __toString(): string
    {
        return (string) json_encode($this->jsonSerialize(), \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->arraySerialize();
    }

    public function valid(): bool
    {
        return in_array($this->position, $this->validKeys(), true);
    }

    public function next(): void
    {
        $keys = $this->validKeys();
        $pos = array_search($this->position, $keys, true);

        $this->position = $keys[(int) $pos + 1] ?? null;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->{$this->position};
    }

    public function rewind(): void
    {
        $this->position = $this->validKeys()[0];
    }

    /**
     * @return array<mixed>
     */
    public function arraySerialize(): array
    {
        $data = [];

        foreach ($this->requiredAttributes() as $attribute) {
            if (!isset($this->{$attribute})) {
                throw new \RuntimeException();
            }
            $data[$attribute] = $this->{$attribute};
        }

        foreach ($this->optionalAttributes() as $attribute) {
            if (!isset($this->{$attribute})) {
                continue;
            }

            $data[$attribute] = $this->{$attribute};
        }

        return $data;
    }

    public function key(): ?string
    {
        return $this->position;
    }

    /**
     * @return array<mixed>
     */
    protected function validKeys(): array
    {
        return array_keys($this->arraySerialize());
    }

    /**
     * @return array<mixed>
     */
    abstract protected function requiredAttributes(): array;

    /**
     * @return array<mixed>
     */
    abstract protected function optionalAttributes(): array;
}

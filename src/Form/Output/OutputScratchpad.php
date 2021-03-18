<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

class OutputScratchpad
{
    /**
     * @var array<string,array<mixed>>
     */
    protected static array $scratchpad = [];

    /**
     * @param string $key
     * @param array<mixed> $payload
     */
    public static function set(string $key, array $payload): void
    {
        self::$scratchpad[$key] = $payload;
    }

    /**
     * @param string $key
     *
     * @return array<mixed>
     */
    public static function get(string $key): array
    {
        return self::$scratchpad[$key] ?? [];
    }
}

<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    public const MESSAGE_TYPE_SUCCESS = 'success';
    public const MESSAGE_TYPE_ERROR = 'error';
    public const MESSAGE_TYPE_WARNING = 'warning';
    public const MESSAGE_TYPE_INFO = 'info';
    public const MESSAGE_TYPES = [
        self::MESSAGE_TYPE_SUCCESS,
        self::MESSAGE_TYPE_ERROR,
        self::MESSAGE_TYPE_WARNING,
        self::MESSAGE_TYPE_INFO,
    ];

    /**
     * @param string|array<mixed>|null $data
     * @param array<string,mixed>|array<array<string,mixed>> $messages
     * @param int $status
     * @param array<string,mixed> $headers
     * @param bool $isJson
     */
    public function __construct($data = null, array $messages = [], int $status = self::HTTP_OK, array $headers = [], bool $isJson = false)
    {
        // messages needs to be an array of messages
        // for convenience, a single message can be passed
        if (array_key_exists('type', $messages)) {
            $messages = [$messages];
        }

        parent::__construct([
            'data' => $data,
            'messages' => $messages ?: [],
        ], $status, $headers, $isJson);
    }
}

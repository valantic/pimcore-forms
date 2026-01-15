<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    /**
     * @param string|array<mixed>|null $data
     * @param array<string,mixed>|array<array<string,mixed>> $messages
     * @param array<string,mixed> $headers
     */
    public function __construct(
        $data = null,
        array $messages = [],
        int $status = self::HTTP_OK,
        ?string $redirectUrl = null,
        array $headers = [],
        bool $isJson = false,
    ) {
        // messages needs to be an array of messages
        // for convenience, a single message can be passed
        if (array_key_exists('type', $messages)) {
            $messages = [$messages];
        }

        parent::__construct([
            'data' => $data,
            'messages' => $messages ?: [],
            'redirectUrl' => $redirectUrl,
        ], $status, $headers, $isJson);
    }
}

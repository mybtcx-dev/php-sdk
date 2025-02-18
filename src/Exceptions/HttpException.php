<?php

declare(strict_types=1);

namespace Banxa\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class HttpException extends RuntimeException implements Throwable
{
    public const TOO_MANY_REQUESTS_EXCEPTION_MESSAGE = 'Too many requests.';
    public const FORBIDDEN_EXCEPTION_MESSAGE = 'Forbidden.';

    /**
     * @param ResponseInterface $response
     * @return HttpException
     */
    public static function badRequest(ResponseInterface $response): HttpException
    {
        return new self(self::getErrorTitle($response), 400);
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    protected static function getErrorTitle(ResponseInterface $response): string
    {
        $contents = $response->getBody()->getContents();
        $decoded = json_decode($contents, true);

        try {
            if (!empty($decoded['errors']['title']) && !empty($decoded['errors']['code'])) {
                return $decoded['errors']['title'] . ' (Code: ' . $decoded['errors']['code'] . ')';
            }
        } catch (Throwable $e) {
            throw new HttpException('An unexpected error occurred', 500);
        }
        return $contents;
    }

    /**
     * @param ResponseInterface $response
     * @return HttpException
     */
    public static function unauthorized(ResponseInterface $response): HttpException
    {
        return new self(self::getErrorTitle($response), 401);
    }

    /**
     * @return HttpException
     */
    public static function forbidden(): HttpException
    {
        return new self(self::FORBIDDEN_EXCEPTION_MESSAGE, 403);
    }

    /**
     * @throws Exception
     */
    public static function validationError(ResponseInterface $response): HttpException
    {
        return new self(self::parseResponseAsJson($response), 422);
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private static function parseResponseAsJson(ResponseInterface $response): string
    {
        $contents = $response->getBody()->getContents();
        $decoded = json_decode($contents, true);
        return json_encode(array_key_exists('errors', $decoded)
            ? $decoded['errors']
            : $contents);
    }

    /**
     * @return HttpException
     */
    public static function tooManyRequests(): HttpException
    {
        return new self(self::TOO_MANY_REQUESTS_EXCEPTION_MESSAGE, 429);
    }
}

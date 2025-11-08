<?php

namespace Monstrex\Ave\Exceptions;

use Exception;

/**
 * Base exception class for all Ave exceptions.
 *
 * All Ave-specific exceptions should extend this class to ensure
 * consistent handling by the Ave exception handler middleware.
 */
abstract class AveException extends Exception
{
    /**
     * HTTP status code for this exception.
     * Default is 500 (Internal Server Error).
     */
    protected int $statusCode = 500;

    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * Get the HTTP status code for this exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

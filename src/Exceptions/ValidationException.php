<?php

namespace Monstrex\Ave\Exceptions;

class ValidationException extends AveException
{
    protected array $errors = [];

    public function __construct(string $message = 'Validation failed', array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->statusCode = $code;
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function withErrors(array $errors): self
    {
        return new self('Validation failed', $errors);
    }
}

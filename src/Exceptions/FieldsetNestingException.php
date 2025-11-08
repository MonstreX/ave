<?php

namespace Monstrex\Ave\Exceptions;

class FieldsetNestingException extends AveException
{
    protected int $statusCode = 422;

    public function __construct(string $message = "Fieldset cannot contain nested Fieldset fields", int $code = 422)
    {
        parent::__construct($message, $code);
    }

    public static function notAllowed(): self
    {
        return new self(
            'Fieldset nesting is not allowed. A Fieldset cannot contain another Fieldset in its schema.'
        );
    }
}
